<?php

namespace App\Factory\Atelier\Dit\soumission;

use App\Dto\Atelier\Dit\soumission\DitRiSoumisAValidationDto;
use App\Model\Atelier\Dit\Soumission\DitRiSoumisAValidationModel;
use App\Service\security\SecurityService;

class DitRiSoumisAValidationFactory
{
    public function initialisation(string $numDit, SecurityService $securityService): DitRiSoumisAValidationDto
    {
        $ditRiSoumisAValidationModel = new DitRiSoumisAValidationModel();
        $dto = new DitRiSoumisAValidationDto();
        $dto->numeroDit = $numDit;
        $dto->codeSociete = $securityService->getCodeSocieteUser();
        $dto->numeroOr = $ditRiSoumisAValidationModel->recupNumeroOr($dto->numeroDit, $dto->codeSociete);
        $dto->itvDejaSoumis = $ditRiSoumisAValidationModel->findItvDejaSoumis($dto->numeroOr, $dto->codeSociete);
        $dto->itvAfficher = $ditRiSoumisAValidationModel->recupInterventionOr($dto->numeroOr, $dto->itvDejaSoumis, $dto->codeSociete);

        return $dto;
    }
}
