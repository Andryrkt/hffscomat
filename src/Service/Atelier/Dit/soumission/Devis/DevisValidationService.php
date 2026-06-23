<?php

namespace App\Service\atelier\dit\soumission\Devis;

use App\Constants\atelier\dit\soumission\Devis\ConstantStatutDevis;
use App\Dto\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationDto;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Service\historiqueOperation\Atelier\Dit\Devis\HistoriqueOperationDEVService;
use App\Service\SessionManagerService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class DevisValidationService
{
    private const FILE_FIELD_NAME = 'pieceJoint01';
    private const FILENAME_PATTERN = '/^(QUOTATION)_(\d+)_(\d+)_(\d+)\\.pdf$/';

    private function getSessionService()
    {
        global $container;
        return $container->get(SessionManagerService::class);
    }

    private function getHistoriqueService(): HistoriqueOperationDEVService
    {
        global $container;
        return $container->get(HistoriqueOperationDEVService::class);
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

    public function validateAvantAffichageForm(DitDevisSoumisAValidationDto $dto): bool
    {
        //vérifier si le numero Devis existe pour le DIT
        if (empty($dto->numeroDevis)) {
            $message = "Echec , ce DIT n'a pas de numéro devis";
            $this->sendNotificationOR($message, $dto->numeroDit, false);
            return true;
        }

        $ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();
        $nbPieceSortieMagasin = $ditDevisSoumisAValidationModel->recupNbPieceMagasin($dto->numeroDevis, $dto->codeSociete);
        $nbPieceSortieMagasinDejaSoumi = $ditDevisSoumisAValidationModel->recupNbPieceMagasinDejaSoumi($dto->numeroDevis, $dto->codeSociete);
        $statutDevis = $ditDevisSoumisAValidationModel->findStatutDevisSelonNumDevis($dto->numeroDevis, $dto->codeSociete);
        $infoDit = $ditDevisSoumisAValidationModel->recupInfoDit($dto->numeroDit, $dto->numeroDevis, $dto->codeSociete);

        $numClientIps = $ditDevisSoumisAValidationModel->recupNumeroClientIps($dto->numeroDevis, $dto->codeSociete);
        $numDitIps = $ditDevisSoumisAValidationModel->recupNumDitIps($dto->numeroDevis, $dto->codeSociete);
        $servDebiteurIps = $ditDevisSoumisAValidationModel->recupServDebiteur($dto->numeroDevis, $dto->codeSociete);


        // verifier si n° dit ips <> n° dit intranet
        if ($numDitIps !== $infoDit['numero_demande_dit']) {
            if ($numClientIps !== $infoDit['numero_client']) {
                $message = "Erreur lors de la soumission, Impossible de soumettre le devis . . . le numero DIT dans IPS ne correspond pas à la DIT ";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }
        }
        // verifie si le n° client dans IPS est different du n° client dans intranet
        if ($numClientIps !== $infoDit['numero_client']) {
            $message = "Erreur lors de la soumission, Impossible de soumettre le devis . . . Veuillez vérifier le client car le client sur la DIT est différent de celui du devis ";
            $this->sendNotificationOR($message, $dto->numeroDevis, false);
            return true;
        }

        // verifie si le service debiteur n'est pas vide
        if ($servDebiteurIps !== '' && $servDebiteurIps !== null) {
            $message = "Erreur lors de la soumission, Impossible de soumettre le devis . . . le service débiteur n'est pas vide";
            $this->sendNotificationOR($message, $dto->numeroDevis, false);
            return true;
        }


        if ($dto->type === 'VP') {

            $uneDevisEstDejaValide = $ditDevisSoumisAValidationModel->recupDevisValide($dto->numeroDevis, $dto->codeSociete);
            $montantIps = $ditDevisSoumisAValidationModel->getMontantItv($dto->numeroDevis, $dto->codeSociete);
            $montantIrium = $ditDevisSoumisAValidationModel->recupMontantItvIrium($dto->numeroDevis, $dto->codeSociete);

            // verifie si statut devi prix réfuseé magasin, pas de nouvelle ligne et les montants ne change pas
            if ($statutDevis === ConstantStatutDevis::PRIX_REFUSE_MAGASIN && $nbPieceSortieMagasin === $nbPieceSortieMagasinDejaSoumi && (abs((float)$montantIps - (float)$montantIrium) < PHP_FLOAT_EPSILON)) {
                $message = "Le prix a été déjà vérifié ... Veuillez soumettre à validation à l'atelier";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }

            // verifie s'il n'y a pas de pièce magasin
            if ($nbPieceSortieMagasin === 0) {
                $message = "Pas de vérification à faire par le magasin";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }

            // verifie si une devis soumis est déjà validé
            if ($uneDevisEstDejaValide !== 0) {
                $message = "Une version du devis est déjà validé ";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                $this->getSessionService()->set('devis_version_valide', 'OK');
                $this->getSessionService()->set('message', $message);
                return true;
            }

            // verifie si une devie est déjà soumis et en cours de vérification
            if ($statutDevis === ConstantStatutDevis::PRIX_A_CONFIRMER) {
                $message = "Erreur lors de la soumission, Impossible de soumettre le devis  . . . un devis est déjà en cours de vérification";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }

            // verification si la reparation est réalise par WS PSSR (que pour les pièces)
            if ($infoDit["reparation_realise"] === "WS PSSR (que pour les pièces)") {
                $message = "Erreur lors de la soumission, Impossible de soumettre le devis  . . . l'atelier est 'WS PSSR (que pour les pièces)'";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }
        } else {
            $estPremierSoumission = $ditDevisSoumisAValidationModel->estPremierSoumission($dto->numeroDevis, $dto->codeSociete);

            // verifie si avec pièce magasin ET premier soumission
            if ($nbPieceSortieMagasin !== 0 && $estPremierSoumission) {
                $message = "Merci de passer le devis à validation au magasin";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }

            // verifie si (statut devis est prix refusé ou prix a confirmer ou Demande refusée par le PM) ET nouvelle reference ajoutée
            if (in_array($statutDevis, ConstantStatutDevis::STATUT_A_PASSER_AU_VARIFICATION_PRIX) && $nbPieceSortieMagasin > $nbPieceSortieMagasinDejaSoumi) {
                $message = "Merci de repasser la soumission du devis au magasin pour vérification";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }

            // verifie si le devis est statué "PRix à confirmer"
            if ($statutDevis === ConstantStatutDevis::PRIX_A_CONFIRMER) {
                $message = "Erreur lors de la soumission, Impossible de soumettre le devis  . . . le devis est encore en cours de vérification";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }

            // verifie si le devis est statué "à valider atelier"
            if ($statutDevis === ConstantStatutDevis::A_VALIDER_ATELIER) {
                $message = "Erreur lors de la soumission, Impossible de soumettre le devis  . . . un devis est déjà en cours de validation";
                $this->sendNotificationOR($message, $dto->numeroDevis, false);
                return true;
            }
        }

        return false;
    }

    public function validateApresSoumission(FormInterface $form, DitDevisSoumisAValidationDto $dto): bool
    {
         // Vérifie si un fichier a été soumis
        if (!$this->isFileSubmitted($form, self::FILE_FIELD_NAME)) {
            $message = "Aucun fichier n'a été soumis.";
            $this->sendNotificationOR($message, $dto->numeroDevis, false);
            return true;
        }

        $file = $form->get(self::FILE_FIELD_NAME)->getData();
        $fileName = $file->getClientOriginalName();
        //Vérifie si le nom du fichier correspond au pattern attendu (S'assurer que c'est bien un OR qui soit soumis)
        if (!$this->matchPattern($fileName, self::FILENAME_PATTERN)) {
            $message = "Le nom du fichier soumis n'est pas conforme au format attendu. Reçu: " . $fileName;
            $this->sendNotificationOR($message, $dto->numeroDevis, false);
            return true;
        }

        // Vérifie si le numéro du devis dans le nom du fichier correspond au numéro de dit attendu (S'assurer que le Facture envoyé corresponde à la ligne de facture utilisé pour la soumission dans l'intranet)
        if (!$this->matchNumberAfterUnderscore($fileName, $dto->numeroDevis)) {
            $message = "Le numéro de facture dans le nom du fichier ($fileName) ne correspond pas à l'OR du formulaire ( $dto->numeroDevis)";
            $this->sendNotificationOR($message, $dto->numeroDevis, false);
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
