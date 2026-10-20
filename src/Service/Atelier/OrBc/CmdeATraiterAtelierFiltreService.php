<?php

namespace App\Service\Atelier\OrBc;

use App\Dto\Atelier\OrBc\CmdeATraitrerAtelierSearchDto;
use App\Model\Informix\SelectWhereCondition;

class CmdeATraiterAtelierFiltreService
{
    /** Défaut si aucune date fournie : 30 derniers jours */
    private const DEFAULT_DAYS_BACK = 30;

    public function filtre(CmdeATraitrerAtelierSearchDto $dto): string
    {
        $swc = new SelectWhereCondition();
        $conditions = [];

        // --- Type : OR / BC / (tous) ---
        // Le DTO peut contenir 'OR', 'BC' ou null/'' (= tous)
        $this->addCondition($conditions, $swc->eq('type_doc', $dto->type_doc));

        // --- Recherche libre : N° OR/BC OU référence OU désignation ---
        if (!empty($dto->reference)) {
            $needle = strtoupper(addslashes(trim($dto->reference)));

            $conditions[] = " AND ("
                . "UPPER(CAST(numero_doc AS VARCHAR(50))) LIKE '%$needle%'"
                . " OR UPPER(CAST(reference AS VARCHAR(100))) LIKE '%$needle%'"
                . " OR UPPER(CAST(designation AS VARCHAR(255))) LIKE '%$needle%'"
                . ")";
        }

        // --- Constructeur : ATC / CAT ---
        $this->addCondition($conditions, $swc->eq('constructeur', $dto->constructeur));

        // --- Période sur date_doc (défaut : 30 derniers jours) ---
        $dateDebut = $dto->dateDebut ?? new \DateTime('-' . self::DEFAULT_DAYS_BACK . ' days');
        $dateFin   = $dto->dateFin   ?? new \DateTime();

        $conditions[] = " AND date_doc >= " . $this->formatDateInformix($dateDebut);
        $conditions[] = " AND date_doc <= " . $this->formatDateInformix($dateFin);

        // Nettoyage : on retire les chaînes vides
        $conditions = array_filter($conditions, function ($c) {
            return !empty(trim($c));
        });

        return implode(' ', $conditions);
    }

    /**
     * Formate une DateTime en MDY(m,j,a) — format utilisé par le reste du projet
     * (ex. MDY(1,1,2026) dans la requête d'origine).
     */
    private function formatDateInformix(\DateTimeInterface $date): string
    {
        return sprintf(
            'MDY(%d,%d,%d)',
            (int) $date->format('n'),
            (int) $date->format('j'),
            (int) $date->format('Y')
        );
    }

    private function addCondition(array &$conditions, string $condition): void
    {
        if (!empty(trim($condition))) {
            $conditions[] = $condition;
        }
    }
}
