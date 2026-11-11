<?php

namespace App\Factory\Atelier\Dit\soumission;

use App\Dto\Atelier\CVA\OrCsaSoumissionDto;


class OrCsaSoumissionDtoFactory
{
    private const PAYS_DEFAUT = 'MAURITUS';

    /**
     * Construit un OrCsaSoumissionDto à partir :
     *   - $data     : données issues de OrCsaSoumissionPdfModel::findOrByRefDemande()
     *   - $montants : ['exclVat' => float, 'incVat' => float] issues de calculMontants()
     *   - $langueIps: 'A' ou 'F'
     *
     * @param array<string,mixed> $data
     * @param array{exclVat: float, incVat: float} $montants
     */
    public function createFromData(
        array $data,
        array $montants,
        string $langueIps
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

        // Cas 1 : déjà un objet DateTime
        if ($date instanceof \DateTimeInterface) {
            return $date->format('d/m/Y');
        }

        // Cas 2 : string ISO — on force l'interprétation YYYY-MM-DD
        $dateTime = \DateTime::createFromFormat('Y-m-d', (string) $date);
        if ($dateTime !== false) {
            return $dateTime->format('d/m/Y');
        }

        // Cas 3 : ISO avec heure "YYYY-MM-DD HH:MM:SS"
        $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', (string) $date);
        if ($dateTime !== false) {
            return $dateTime->format('d/m/Y');
        }

        // Cas 4 : fallback permissif (DB2 "03-NOV-2020", etc.)
        try {
            return (new \DateTime((string) $date))->format('d/m/Y');
        } catch (\Throwable $e) {
            return '';
        }
    }
}
