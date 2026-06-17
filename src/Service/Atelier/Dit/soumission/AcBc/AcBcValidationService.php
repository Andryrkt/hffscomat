<?php

namespace App\Service\Atelier\Dit\soumission\AcBc;

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
     * @param array{numero_dit:string,numero_devis:string,statut_devis:string,date_soumission:string,montant:string,devise:string,interne_externe:string,numero_client:string} $infoDevis
     * @param string $numDit
     * 
     * @return bool
     */
    public function isValidAvantAffichageForm(array $infoDevis, string $numDit): bool
    {
        $isValid = true;
        $message = "";

        if (empty($infoDevis)) {
            $isValid = false;
            $message = "L'information du devis est vide ou le statut n'est pas 'Validé atelier' pour le DIT n° {$numDit}";
        }

        if ($infoDevis["interne_externe"] === "INTERNE") {
            $isValid = false;
            $message = "Le DIT n° {$numDit} est interne";
        }

        if (!$isValid) $this->historiqueOperation->sendNotificationCreation("Erreur lors de la soumission, Impossible de soumettre le BC . . . $message", $numDit, 'dit_index');

        return $isValid;
    }
}
