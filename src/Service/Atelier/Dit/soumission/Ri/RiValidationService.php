<?php

namespace App\Service\atelier\dit\soumission\Ri;

use App\Dto\Atelier\Dit\soumission\DitRiSoumisAValidationDto;
use App\Service\historiqueOperation\atelier\dit\Ri\HistoriqueOperationRIService;
use App\Service\SessionManagerService;

class RiValidationService
{
    private function getSessionService()
    {
        global $container;
        return $container->get(SessionManagerService::class);
    }

    private function getHistoriqueService(): HistoriqueOperationRIService
    {
        global $container;
        return $container->get(HistoriqueOperationRIService::class);
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

    public function validateAvantAffichageForm(DitRiSoumisAValidationDto $dto): bool
    {
        // vérifier si le numéro or existe pour le DIT
        if (empty($dto->numeroOr)) {
            $message = "Le DIT n'a pas encore de numéro OR";
            $this->sendNotificationOR($message, $dto->numeroDit, false);
            return true;
        }

        return false;
    }

    public function validateApresSoumissionForm(DitRiSoumisAValidationDto $dto): bool
    {
        // verifier si certaines interventions ont déjà été soumises
        if ($dto->estSoumis) {
            $message = "Erreur lors de la soumission RI, car certaines interventions ont déjà fait l'objet d'une soumission dans DocuWare.";
            $this->sendNotificationOR($message, $dto->numeroDit, false);
            return true;
        }

        // verifier si le numero ITV n'existe pas pour le numero OR
        if ($dto->existe) {
            $message = "Erreur lors de la soumission RI, car certaines interventions n'ont pas encore été validées dans DocuWare.";
            $this->sendNotificationOR($message, $dto->numeroDit, false);
            return true;
        }

        return false;
    }
}
