<?php

namespace App\Service\Atelier\Dit\soumission\AcBc;

use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Service\historiqueOperation\Atelier\Dit\Bc\HistoriqueOperationBCService;
use Doctrine\ORM\EntityManagerInterface;

class AcBcValidationService
{
    private HistoriqueOperationBCService $historiqueOperation;

    public function __construct(EntityManagerInterface $em)
    {
        $this->historiqueOperation = new HistoriqueOperationBCService($em);
    }

    /** 
     * Vérifier si la soumission est valide avant l'affichage du formulaire
     * 
     * @param ?AccuseReceptionDto $accuseReceptionDto
     * @param string $numDit
     * 
     * @return bool
     */
    public function isValidAvantAffichageForm(?AccuseReceptionDto $accuseReceptionDto, string $numDit): bool
    {
        $isValid = true;
        $message = "";

        if (!$accuseReceptionDto) {
            $isValid = false;
            $message = "L'information du devis est vide ou le statut n'est pas 'Validé atelier' pour le DIT n° {$numDit}";
        }

        if ($accuseReceptionDto->interneExterne === "INTERNE") {
            $isValid = false;
            $message = "Le DIT n° {$numDit} est interne";
        }

        if (!$isValid) $this->historiqueOperation->sendNotificationCreation("Erreur lors de la soumission, Impossible de soumettre le BC . . . $message", $numDit, 'dit_liste');

        return $isValid;
    }
}
