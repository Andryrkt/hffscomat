<?php

namespace App\Mapper\Atelier\OrBc;

use App\Dto\Atelier\OrBc\CmdeATraitrerAtelierDto;

class CmdeATraiterAtelierListeMapper
{
    public function map(array $data): array
    {
        return array_map(function ($item) {
            $dto = new CmdeATraitrerAtelierDto();
            $dto->type_doc      = $item['type_doc'] ?? null;
            $dto->numero_doc    = $item['numero_doc'] ?? null;
            $dto->numero_ligne  = $item['numero_ligne'] ?? null;
            $dto->constructeur  = $item['constructeur'] ?? null;
            $dto->reference     = $item['reference'] ?? null;
            $dto->designation   = $item['designation'] ?? null;
            $dto->qte_demandee  = (float) ($item['qte_demandee'] ?? 0);
            $dto->qte_reliquat  = (float) ($item['qte_reliquat'] ?? 0);
            $dto->qte_reservee  = (float) ($item['qte_reservee'] ?? 0);

            if (!empty($item['date_alloc'])) {
                try {
                    $dto->date_alloc = new \DateTime($item['date_alloc']);
                } catch (\Exception $e) {
                    $dto->date_alloc = null;
                }
            }

            return $dto;
        }, $data);
    }
}
