<?php

namespace App\Service\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningAtelierDto;
use App\Dto\Atelier\Planning\PresenceDto;
use App\Mapper\Atelier\Planning\PlanningAtelierMapper;

class PlanningAtelierService
{

    private PlanningAtelierMapper $mapper;

    public function __construct()
    {
        $this->mapper = new PlanningAtelierMapper();
    }

    /**
     * @return array{planning: array<string, PlanningAtelierDto>, dates: array<\DateTime>, filteredDates: array<string>}
     * @throws \Exception
     */
    public function process(array $dbResults, ?string $startDate = null, ?string $endDate = null): array
    {
        $planning = [];
        foreach ($dbResults as $item) {
            $currDto = $this->mapper->mapToDto($item);
            $key = $currDto->getKey();

            if (!isset($planning[$key])) {
                $planning[$key] = $currDto;
            }

            $planning[$key]->nbTotalJour += $currDto->nbJour;

            $debut = new \DateTime($item['date_debut']);
            $dateStr = $debut->format('Y-m-d');

            if (!isset($planning[$key]->presences[$dateStr])) {
                $planning[$key]->presences[$dateStr] = new PresenceDto();
            }

            $this->applyPresenceCalculation($planning[$key]->presences[$dateStr], $item, $dateStr);
        }

        $calendar = $this->generateCalendarPeriod($startDate, $endDate);

        return [
            'planning' => $planning,
            'dates' => $calendar['dates'],
            'filteredDates' => $calendar['filteredDates'],
        ];
    }

    private function applyPresenceCalculation(PresenceDto $presence, array $item, string $dateStr)
    {
        $debut = new \DateTime($item['date_debut']);
        $fin = new \DateTime($item['date_fin']);

        $matin_debut = new \DateTime("$dateStr 08:00:00");
        $matin_fin   = new \DateTime("$dateStr 12:00:00");
        $aprem_debut = new \DateTime("$dateStr 13:30:00");
        $aprem_fin   = new \DateTime("$dateStr 17:30:00");

        if ($fin >= $matin_debut && $debut < $matin_fin) {
            $presence->matin = true;
        }
        if ($fin >= $aprem_debut && $debut < $aprem_fin) {
            $presence->apm = true;
        }

        // Résolution du cas limite (Edge-case des lignes sans pointage via LEFT JOIN)
        if (!isset($item['hpointee']) || empty($item['hpointee_debut']) || empty($item['hpointee_fin'])) {
            return;
        }

        $hpointee = (float) $item['hpointee'];
        $hdebut = new \DateTime($item['hpointee_debut']);
        $hfin = new \DateTime($item['hpointee_fin']);

        if ($presence->heure === null) {
            $presence->heure = 0.0;
        }
        $presence->heure += $hpointee;

        $isFullDay = ($hdebut <= $matin_debut && $hfin >= $aprem_fin);

        if (!$isFullDay) {
            if ($hdebut <= $matin_fin && $hfin >= $matin_debut) {
                $presence->hmtn = ($presence->hmtn ?? 0.0) + $hpointee;
            }
            if ($hdebut <= $aprem_fin && $hfin >= $aprem_debut) {
                $presence->hapm = ($presence->hapm ?? 0.0) + $hpointee;
            }
        }
    }

    private function generateCalendarPeriod(?string $startStr, ?string $endStr): array
    {
        $dates = [];
        $filteredDates = [];

        if (!$startStr || !$endStr) {
            return ['dates' => $dates, 'filteredDates' => $filteredDates];
        }

        $start = new \DateTime($startStr);
        $end = new \DateTime($endStr);
        $interval = new \DateInterval('P1D');
        $period = new \DatePeriod($start, $interval, (clone $end)->modify('+1 day'));

        foreach ($period as $date) {
            $dates[] = $date;
            $filteredDates[] = $date->format('Y-m-d');
        }

        return ['dates' => $dates, 'filteredDates' => $filteredDates];
    }

}