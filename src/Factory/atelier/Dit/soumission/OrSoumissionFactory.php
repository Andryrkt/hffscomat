<?php

namespace App\Factory\atelier\Dit\soumission;

use App\Dto\atelier\dit\soumission\OrSoumissionDto;

class OrSoumissionFactory
{

    public function initialisation(string $numDit, string $numOr, string $codeSociete): OrSoumissionDto
    {
        $dto = new OrSoumissionDto();
        $dto->numeroDit = $numDit;
        $dto->numeroOR = $numOr;
        $dto->codeSociete = $codeSociete;
        return $dto;
    }
}
