<?php

namespace App\Service\atelier\dit\soumission\Facture;

use App\Dto\atelier\dit\soumission\DitFactureSoumisAValidationDto;
use App\Service\historiqueOperation\atelier\dit\Facture\HistoriqueOperationFACService;
use App\Service\SessionManagerService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class FactureValidationService
{
    private const FILE_FIELD_NAME = 'pieceJoint01';
    private const FILENAME_PATTERN = '/^(FACTURE CESSION|FACTURE-BON DE LIVRAISON|AVOIR|A V O I R)_(\d+)_(\d+)_(\d+)\\.pdf$/';
    private const TYPE_FACTURE_VENTE = [200, 201, 202, 203, 204, 205, 206, 207, 208, 209];

    private function getSessionService()
    {
        global $container;
        return $container->get(SessionManagerService::class);
    }

    private function getHistoriqueService(): HistoriqueOperationFACService
    {
        global $container;
        return $container->get(HistoriqueOperationFACService::class);
    }

    /**
     * Envoie une notification via le service d'historique.
     *
     * @param string $message     Le message à envoyer.
     * @param string $numeroOr Le numéro de devis concerné.
     * @param bool   $success     Indique si l'opération a réussi.
     * @param array|null $structuredParams Tableau structuré des paramètres de recherche (session).
     * @param string $paramPrefix Name de l'input de recherche.
     * @param array $routeParams  Paramètres de la route.
     * @param array|null $queryParams  Paramètres de la requête.
     */
    private function sendNotificationOR(
        string $message,
        string $numeroDit,
        bool $success
    ): void {
        $criteria = (array)$this->getSessionService()->get('criteria_for_excel_dit_liste');
        $nomInputSearch = 'dit_search'; // initialistion de nom de chaque champ ou input
        $this->getHistoriqueService()->sendNotificationSoumission(
            $message,
            $numeroDit,
            'dit_liste',
            $success,
            $criteria,
            $nomInputSearch
        );
    }

    public function validateAvantAffichageForm(DitFactureSoumisAValidationDto $dto): bool
    {
        // vérifier si le numéro or existe pour le DIT
        if (empty($dto->numeroOr)) {
            $message = "Le DIT n'a pas encore de numéro OR";
            $this->sendNotificationOR($message, $dto->numeroDit, false);
            return true;
        }

        return false;
    }

    public function validateApresSoumissionForm(FormInterface $form, DitFactureSoumisAValidationDto $dto): bool
    {
        // Vérifie si un fichier a été soumis
        if (!$this->isFileSubmitted($form, self::FILE_FIELD_NAME)) {
            $message = "Aucun fichier n'a été soumis.";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        $file = $form->get(self::FILE_FIELD_NAME)->getData();
        $fileName = $file->getClientOriginalName();
        // Vérifie si le nom du fichier correspond au pattern attendu (S'assurer que c'est bien un OR qui soit soumis)
        if (!$this->matchPattern($fileName, self::FILENAME_PATTERN)) {
            $message = "Le nom du fichier soumis n'est pas conforme au format attendu. Reçu: " . $fileName;
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        // Vérifie si le numéro de Facture dans le nom du fichier correspond au numéro de dit attendu (S'assurer que le Facture envoyé corresponde à la ligne de facture utilisé pour la soumission dans l'intranet)
        if (!$this->matchNumberAfterUnderscore($fileName, $dto->numeroFact)) {
            $message = "Le numéro de facture dans le nom du fichier ($fileName) ne correspond pas à l'OR du formulaire ( $dto->numeroOr)";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        //Vérifie si le fichier soumis correspond à la facture de l'OR
        if (empty($dto->infoFac) && !(in_array($dto->infoFac[0]['typeor'], self::TYPE_FACTURE_VENTE) && $dto->infoFac[0]['qterea'] < 0)) {
            $message = "la facture ne correspond pas à l'OR";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        // vérifier si tous les rapport d'intervention du facture est déjà soumis
        if ($dto->estRi) {
            $message = "La facture ne correspond pas ou correspond partiellement à un rapport d'intervention.";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        return false;
    }


    /**
     * Vérifie si un fichier a été soumis dans un champ de formulaire donné
     * 
     * @param FormInterface $form Le formulaire à vérifier
     * @param string $fieldName Le nom du champ de fichier à vérifier
     * @return bool true si un fichier a été soumis, false sinon
     */
    protected function isFileSubmitted(FormInterface $form, string $fieldName): bool
    {
        if (!$form->has($fieldName)) {
            return false;
        }

        $file = $form->get($fieldName)->getData();

        return $file instanceof UploadedFile;
    }

    /**
     * Vérifie si une chaîne de caractères correspond à un pattern regex
     * 
     * @param string|null $subject La chaîne de caractères à tester
     * @param string $pattern Le pattern regex à utiliser pour la correspondance
     * @return bool true si la chaîne correspond au pattern, false sinon
     */
    protected function matchPattern(?string $subject, string $pattern): bool
    {
        if ($subject === null) {
            return false;
        }
        return preg_match($pattern, $subject) === 1;
    }

    /**
     * Extrait un numéro après un underscore (_) dans une chaîne et le compare à une valeur attendue
     * 
     * Cette méthode est utilisée pour valider que le numéro dans un nom de fichier
     * correspond au numéro attendu (ex: "Ordre de réparation_123_456.pdf" avec expectedNumber "123")
     * 
     * @param string $subject La chaîne de caractères contenant le numéro à extraire
     * @param string $expectedNumber Le numéro attendu pour la comparaison
     * @return bool true si le numéro extrait correspond au numéro attendu, false sinon
     */
    protected function matchNumberAfterUnderscore(string $subject, string $expectedNumber): bool
    {
        // Trouve la première séquence de chiffres qui suit un underscore
        if (preg_match('/_(\d+)/', $subject, $matches)) {
            // $matches[1] contient les chiffres capturés
            $extractedNumber = $matches[1];
            return $extractedNumber === (string) $expectedNumber;
        }

        return false; // Aucun numéro trouvé après un underscore
    }
}
