<?php

namespace App\Factory\Atelier\Dit\soumission\AcBc;

use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;

class AccuseReceptionFactory
{
    /** 
     * Retourne le Dto correspondant aux infos de devis
     * 
     * @param array{numero_dit:string,numero_devis:string,statut_devis:string,date_soumission:string,montant:string,devise:string,interne_externe:string,numero_client:string} $data infos de devis
     * 
     * @return ?AccuseReceptionDto
     */
    public function hydrate(array $data): ?AccuseReceptionDto
    {
        if (empty($data)) return null;

        $dto = new AccuseReceptionDto;

        $dto->devise         = $data["devise"];
        $dto->numeroDit      = $data["numero_dit"];
        $dto->numeroDevis    = $data["numero_devis"];
        $dto->interneExterne = $data["interne_externe"];
        $dto->statutDevis    = $data["statut_devis"];
        $dto->numeroClient   = $data["numero_client"];
        $dto->montantDevis   = (float) $data["montant"];
        $dto->dateDevis      = new \DateTime($data["date_soumission"]);

        return $dto;
    }
}
