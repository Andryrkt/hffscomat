<?php

namespace App\Mapper\Atelier\Dit\Soumission;

use App\Dto\Atelier\Dit\soumission\DitRiSoumisAValidationDto;

class DitRiSoumisAValidationMapper
{
    public static function map(DitRiSoumisAValidationDto $dto): array
    {
        return array_map(function ($item) use ($dto) {
            return [
                'numero_dit' => $dto->numeroDit,
                'numero_or' => $dto->numeroOr,
                'date_soumission' => $dto->dateSoumission,
                'numero_soumission' => $dto->numeroSoumission,
                'heuresoumission' => $dto->heureSoumission,
                'numeroitv' => $item,
                'code_societe' => $dto->codeSociete
            ];
        }, $dto->itvCoches);
    }
}
