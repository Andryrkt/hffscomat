<?php

namespace App\Service\atelier\dit\soumission\ORs;

use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\historiqueOperation\Atelier\Dit\ORs\HistoriqueOperationORService;
use App\Service\SessionManagerService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ValidationService
{


    private const FILE_FIELD_NAME = 'pieceJoint01';
    private const FILENAME_PATTERN = '/^(Ordre de réparation)_(\d+)_(\d+)_(\d+)\\.pdf$/';

    private function getSessionService()
    {
        global $container;
        return $container->get(SessionManagerService::class);
    }

    private function getHistoriqueService(): HistoriqueOperationORService
    {
        global $container;
        return $container->get(HistoriqueOperationORService::class);
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
        string $numeroOr,
        bool $success
    ): void {

        $criteria = (array)$this->getSessionService()->get('criteria_for_excel_dit_liste');

        $nomInputSearch = 'dit_search'; // initialistion de nom de chaque champ ou input
        $this->getHistoriqueService()->sendNotificationSoumission(
            $message,
            $numeroOr,
            'dit_liste',
            $success,
            $criteria,
            $nomInputSearch
        );
    }

    public function validateAvantAffichageForm(OrSoumissionDto $dto): bool
    {
        // vérifier si le numéro or existe pour le DIT
        if (empty($dto->numeroOr)) {
            $message = "Le DIT n'a pas encore de numéro OR";
            $this->sendNotificationOR($message, $dto->numeroDit, false);
            return true;
        }

        // vérifier si le catégorie de la DIT est DAILY CHECK et le type de l'OR est 930 sinon bloqué
        if ($dto->idCategorieDemande === 10 && $dto->typeOr !== 930) {
            $message = "Merci de vérifier l'OR car le type de l'OR ne correspond pas à la DIT rattaché qui est un DAILY CHECK";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        return false;
    }

    /**
     * Valide le fichier soumis pour la validation de OR
     * 
     * Cette méthode vérifie :
     * - Si un fichier a été soumis
     * - Si le nom du fichier correspond au format attendu (Ordre de réparation_XXX_XXX_XXX.pdf)
     * - Si le numéro de OR dans le nom du fichier correspond au numéro attendu
     * 
     * @param FormInterface $form Le formulaire contenant le fichier à valider
     * @param string|null $remoteUrl L'URL distante du fichier soumis (optionnel, utilisé si le fichier est soumis via une URL)
     * @param OrSoumissionDto $dto 
     * @return bool true si le fichier est valide, false sinon
     */
    public function validateSubmittedFile(FormInterface $form, ?string $remoteUrl = null, OrSoumissionDto $dto): bool
    {
        //  Verifie si la date du premier soummision est inferieur au date u jour
        if ($this->premierSoumissionDatePlanningInferieurDateDuJour($dto->numeroOr, $dto->codeSociete, $dto->nmbrOr_soumis)) {
            $message = "Impossible de soumettre l’OR, la date de planning est déjà dépassée ";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }
        //  Verifie le nombre d'agence de service est plus 1
        if ($dto->existeNumclient) {
            $message = "La soumission n'a pas pu être effectuée car le client rattaché à l'OR est introuvable ";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }
        //  Verifie le nombre d'agent de service est plus 1
        if ($dto->countAgServDebit > 1) {
            $message = "Echec de la soumission de l'OR . . . un OR a plusieurs service débiteur ";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        //  Verifie si la statut est bloquer
        if ($dto->statut === 'bloquer') {
            $message = "Echec de la soumission de l'OR . . . un OR est déjà en cours de validation ";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }
        //  Verifie si la refClient est vide
        if ($dto->refClient) {
            $message = "Echec de la soumission car la référence client est vide.";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }
        //  Validation des positions valides 
        if ($dto->isValidPosition) {
            $message = "Echec de la soumission de l'OR, la position de l'OR est parmis 'FC', 'FE', 'CP', 'ST'";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }
        //  Verification si agence debiteur est dans Irium et IPS sont egale 
        if ($dto->isAgenceIriumInIPS) {
            $message = "Echec de la soumission car l'agence / service débiteur de l'OR ne correspond pas à l'agence / service de la DIT";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }
        // Verifie si tous les intervention sont plannifiées
        if ($dto->isVerifiedDatePlanning) {
            $message = "Echec de la soumission car il existe une ou plusieurs interventions non planifiées dans l'OR";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }
        // Verifie si id_materiel_Ips correspond id_materiel_Irium 
        if ($dto->estIdMaterielDifferent) {
            $message = "Echec de la soumission car le materiel de l'OR ne correspond pas au materiel de la DIT";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        // Vérifie si un fichier a été soumis
        if (!$remoteUrl && !$this->isFileSubmitted($form, self::FILE_FIELD_NAME)) {
            $message = "Aucun fichier n'a été soumis.";
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        $file = $form->get(self::FILE_FIELD_NAME)->getData();

        if ($remoteUrl) {
            $parts = explode("/", $remoteUrl);
            $fileName = end($parts); // récupère le dernier élément du tableau
        } else {
            $fileName = $file->getClientOriginalName();
        }

        // Vérifie si le nom du fichier correspond au pattern attendu (S'assurer que c'est bien un OR qui soit soumis)
        if (!$this->matchPattern($fileName, self::FILENAME_PATTERN)) {
            $message = "Le nom du fichier soumis n'est pas conforme au format attendu. Reçu: " . $fileName;
            $this->sendNotificationOR($message, $dto->numeroOr, false);
            return true;
        }

        // Vérifie si le numéro de OR dans le nom du fichier correspond au numéro de dit attendu (S'assurer que le OR envoyé corresponde à la ligne de OR utilisé pour la soumission dans l'intranet)
        if (!$this->matchNumberAfterUnderscore($fileName, $dto->numeroOr)) {
            $message = "Le numéro de l'OR dans le nom du fichier ($fileName) ne correspond pas au OR du formulaire ( $dto->numeroOr )";
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

    private function premierSoumissionDatePlanningInferieurDateDuJour(string $numOr, string $codeSociete, int $nmbrOr_soumis): bool
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();

        $nbrPieceMagasin = $ditOrsoumisAValidationModel->recupNbPieceMagasin($numOr, $codeSociete); //nombre de piece magasin

        if ((int)$nmbrOr_soumis <= 0 && (int)$nbrPieceMagasin <= 0) { // si pas encore soumis et pas de piece magasin
            $numItvs = $ditOrsoumisAValidationModel->getNumItv($numOr, $codeSociete);
            $dateDuJour = new \DateTime('now');
            foreach ($numItvs as $numItv) {
                $datePlannig1 = $ditOrsoumisAValidationModel->recupDatePlanningOR1($numOr, $numItv, $codeSociete);
                $datePlannig2 = $ditOrsoumisAValidationModel->recupDatePlanningOR2($numOr, $numItv, $codeSociete);
                $datePlanning = empty($datePlannig1) ? new \DateTime($datePlannig2[0]['dateplanning2']) : new \DateTime($datePlannig1[0]['dateplanning1']);
                if ($datePlanning->format('Y-m-d') < $dateDuJour->format('Y-m-d')) { // date planning est inférieure à la date du jour
                    return true;
                }
            }
            return false;
        } else {
            return false;
        }
    }
}
