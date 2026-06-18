<?php

namespace App\Service\Atelier\Dit\Validation;

use App\Dto\Atelier\Dit\DitDto;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use App\Service\SessionManagerService;

class ValidationDit
{
    public function validation(DitDto $dto)
    {
        // Bloqueé si:
        // si le materiel est mise au rebut (mmat_afect = 'CAS') ou si l'utilisateur n'a pas renseigner les champs sur les information du materiel (idMateriel, n°Parc, n°serie)  
        if (empty($dto->idMateriel)) {
            $message = 'Échec lors de la création de la DIT... Impossible de récupérer les informations du matériel. il peut être mis au rebut.';
            $this->sendNotification($message, '-', false);
            return true; // Validation failed
        }

        // si le DIT est EXTERNE et les champs nom et  numéro du client n'est pas renseigner
        if ($dto->internetExterne === "EXTERNE" && empty($dto->nomClient) && empty($dto->numeroClient)) {
            $message = 'Échec lors de la création de la DIT... Impossible de récupérer les informations du client.';
            $this->sendNotification($message, '-', false);
            return true; // Validation failed
        }
    }

    private function getSessionService()
    {
        global $container;
        return $container->get(SessionManagerService::class);
    }

    private function getHistoriqueService(): HistoriqueOperationDITService
    {
        global $container;
        return $container->get(HistoriqueOperationDITService::class);
    }

    /**
     * Envoie une notification via le service d'historique.
     *
     * @param string $message     Le message à envoyer.
     * @param string $numeroDit Le numéro de dit concerné.
     * @param bool   $success     Indique si l'opération a réussi.
     */
    private function sendNotification(
        string $message,
        string $numeroDit,
        bool $success
    ): void {

        $criteria = (array)$this->getSessionService()->get('criteria_for_excel_dit_liste');
        $nomInputSearch = 'dit_liste'; // initialistion de nom de chaque champ ou input
        $this->getHistoriqueService()->sendNotificationSoumission(
            $message,
            $numeroDit,
            'liste_devis_neg',
            $success,
            $criteria,
            $nomInputSearch
        );
    }
}
