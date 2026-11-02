<?php

namespace App\Mapper\Atelier\CVA;

use App\Dto\Atelier\CVA\OrCsaATraiterDto;


class OrCsaATraiterListeMapper
{
    public function map(array $data): array
    {
        return array_map(function ($item) {
            $dto = new OrCsaATraiterDto();
        }, $data);
    }
}
