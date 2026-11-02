<?php

namespace App\Mapper\Atelier\CVA;

use App\Dto\Atelier\CVA\OrCsaATraiterDto;

class OrCsaATraiterListeMapper
{
    /**
     * @param  array<int, array<string, mixed>> $rows
     * @return OrCsaATraiterDto[]
     */
    public function map(array $rows): array
    {
        $result = [];
        foreach ($rows as $row) {
            $result[] = $this->mapRow($row);
        }

        return $result;
    }

    private function mapRow(array $row): OrCsaATraiterDto
    {
        $dto = new OrCsaATraiterDto();

        $dto->numOr            = $this->pick($row, 'seor_numor');
        $dto->client           = $this->pick($row, 'client');
        $dto->referenceClient  = $this->pick($row, 'reference_client');
        $dto->typeOr           = $this->pick($row, 'type_or');
        $dto->leadTime         = $this->toDateTime($this->pick($row, 'lead_time'));
        $dto->nbJoursDispo     = (int) ($this->pick($row, 'nb_jours_dispo') ?? 0);
        $dto->nbJoursDispoLib  = $this->pick($row, 'nb_jours_dispo_lib');
        $dto->statutAllocation = $this->pick($row, 'statut_allocation');
        $dto->codeCouleur      = $this->pick($row, 'code_couleur');

        return $dto;
    }

    /**
     * Informix renvoie les colonnes en MAJUSCULES selon la configuration
     * du driver (PDO/odbc). On tolère les deux casses pour rester robuste
     * si la couche d'accès change.
     */
    private function pick(array $row, string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $row)) {
            return $row[$key];
        }
        $upper = strtoupper($key);
        if (array_key_exists($upper, $row)) {
            return $row[$upper];
        }

        return $default;
    }

    private function toDateTime(mixed $value): ?\DateTime
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTime) {
            return $value;
        }
        if ($value instanceof \DateTimeInterface) {
            return \DateTime::createFromInterface($value);
        }

        // Informix renvoie généralement 'YYYY-MM-DD' pour un DATE
        $dt = \DateTime::createFromFormat('Y-m-d', (string) $value);
        if ($dt !== false) {
            return $dt;
        }

        try {
            return new \DateTime((string) $value);
        } catch (\Exception $e) {
            return null;
        }
    }
}
