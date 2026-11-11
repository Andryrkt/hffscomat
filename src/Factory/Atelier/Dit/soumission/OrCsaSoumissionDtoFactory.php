<?php

namespace App\Factory\Atelier\Dit\soumission;



use App\Dto\Atelier\CVA\OrCsaSoumissionDto;

final class OrCsaSoumissionDtoFactory
{
    private const PAYS_DEFAUT = 'MAURITUS';
    private const FORMAT_DATE = 'd/m/Y';

    /**
     * @param array<string,mixed>                  $data
     * @param array{exclVat: float, incVat: float} $montants
     * @param array<int,array{quantity:string,reference:string,description:string,unitPrice:float,netAmount:float}> $lignes
     */
    public function createFromData(
        array $data,
        array $montants,
        string $langueIps,
        array $lignes = []
    ): OrCsaSoumissionDto {
        $dto = new OrCsaSoumissionDto();

        // Section 1 — Client
        $dto->nomClient     = (string) ($data['nom_client']     ?? '');
        $dto->adresseClient = (string) ($data['adresse_client'] ?? '');
        $dto->bureauPostal  = (string) ($data['bureau_postal']  ?? '');
        $dto->pays          = self::PAYS_DEFAUT;

        // Section 2 — Infos proforma
        $dto->numeroOr     = (string) ($data['numero_or']     ?? '');
        $dto->numeroClient = (string) ($data['numero_client'] ?? '');
        $dto->dateOr       = $this->formaterDate($data['date_or'] ?? null);

        // Section 3 — Références
        $dto->referenceClient = (string) ($data['reference_client'] ?? '');
        $dto->refDemande      = (string) ($data['ref_demande']      ?? '');

        // Section 4 — Dept / Sort
        $dto->codeService            = (string) ($data['code_service']              ?? '');
        $dto->libelleService         = (string) ($data['libelle_service']           ?? '');
        $dto->natureOperation        = (string) ($data['nature_operation']          ?? '');
        $dto->libelleNatureOperation = (string) ($data['libelle_nature_operation']  ?? '');

        // Section 5 — Totaux
        $dto->montantExclVat = (float) ($montants['exclVat'] ?? 0.0);
        $dto->montantIncVat  = (float) ($montants['incVat']  ?? 0.0);

        $dto->langueIps = $langueIps;

        // Section 6 — Compléments PROFORMA
        $dto->operator           = (string) ($data['operator']           ?? '');
        $dto->quoteNo            = (string) ($data['quote_no']           ?? $data['seor_numdev'] ?? '');
        $dto->salesman           = (string) ($data['salesman']           ?? '');
        $dto->segmentDescription = (string) ($data['segment_description'] ?? $data['reference_client'] ?? '');
        $dto->lignes             = $lignes;   // ← OUBLI CRITIQUE dans le fichier actuel

        return $dto;
    }

    /**
     * @param \DateTimeInterface|string|null $date
     */
    private function formaterDate($date): string
    {
        if (empty($date)) {
            return '';
        }

        if ($date instanceof \DateTimeInterface) {
            return $date->format(self::FORMAT_DATE);
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d', (string) $date);
        if ($dateTime !== false) {
            return $dateTime->format(self::FORMAT_DATE);
        }

        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', (string) $date);
        if ($dateTime !== false) {
            return $dateTime->format(self::FORMAT_DATE);
        }

        try {
            return (new \DateTime((string) $date))->format(self::FORMAT_DATE);
        } catch (\Throwable $e) {
            return '';
        }
    }
}
