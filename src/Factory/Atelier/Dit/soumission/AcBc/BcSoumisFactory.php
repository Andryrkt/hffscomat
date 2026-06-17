<?php

namespace App\Factory\Atelier\Dit\soumission\AcBc;

use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Dto\Atelier\Dit\soumission\AcBc\BcSoumisDto;

class BcSoumisFactory
{
    /** 
     * Retourne le Dto correspondant à l'accusé de réception
     * 
     * @param AccuseReceptionDto $accuseReceptionDto
     * @param int                $numeroVersionMaxBcSoumis
     * 
     * @return BcSoumisDto
     */
    public function hydrate(AccuseReceptionDto $accuseReceptionDto, int $numeroVersionMaxBcSoumis): BcSoumisDto
    {
        $dto = new BcSoumisDto;

        $dto->numeroDit      = $accuseReceptionDto->numeroDit;
        $dto->numeroDevis    = $accuseReceptionDto->numeroDevis;
        $dto->numeroBc       = $accuseReceptionDto->numeroBc;
        $dto->dateBc         = $accuseReceptionDto->dateBc;
        $dto->dateDevis      = $accuseReceptionDto->dateDevis;
        $dto->montantDevis   = $accuseReceptionDto->montantDevis;
        $dto->codeSociete    = $accuseReceptionDto->codeSociete;
        $dto->numeroVersion  = $numeroVersionMaxBcSoumis + 1;
        $dto->statut         = 'Soumis à validation';

        return $dto;
    }
}
