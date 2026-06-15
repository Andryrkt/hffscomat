<?php

namespace App\Service\atelier\dit\soumission\Devis;

use App\Constants\atelier\dit\soumission\Devis\ConstantStatutDevis;
use App\Dto\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationDto;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Service\historiqueOperation\atelier\dit\Devis\HistoriqueOperationDEVService;
use App\Service\SessionManagerService;

class DevisValidationService
{
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
}
