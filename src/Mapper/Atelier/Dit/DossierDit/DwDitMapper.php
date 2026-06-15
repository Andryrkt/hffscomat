<?php

namespace App\Mapper\Atelier\Dit\DossierDit;

use App\Dto\Atelier\Dit\DossierDit\DwDitDto;

class DwDitMapper
{
    public function mapToDto(array $item): DwDitDto
    {
        $dto = new DwDitDto();

        $dto->numeroDit           = $item['numero_dit'] ?? '-';
        $dto->numeroOR            = $item['numero_or'] ?? '-';
        $dto->numeroDevis         = $item['numero_devis'] ?? '-';
        $dto->idMateriel          = $item['id_materiel'] ?? '-';
        $dto->numeroParc          = $item['numero_parc'] ?? '-';
        $dto->numeroSerie         = $item['numero_serie'] ?? '-';
        $dto->designationMateriel = $item['designation_materiel'] ?? '-';
        $dto->typeIntervention    = $item['type_reparation'] ?? '-';
        $dto->dateCreation        = $item['date_creation'] ? (new \DateTime($item['date_creation']))->format('d/m/Y') : '-';
        $dto->nbDoc               = $item['nb_docs'] ?? 0;

        return $dto;
    }
}
