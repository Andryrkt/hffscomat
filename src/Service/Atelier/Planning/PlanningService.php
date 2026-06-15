<?php

namespace App\Service\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningMaterielDto;
use function PHPSTORM_META\map;

class PlanningService
{

    /**
     * @param array<string, PlanningMaterielDto> $data
     */
    public function getDataList(array $data, ?int $selectedMonth = 3): array
    {
        $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
        $currentMonth = (int)date('n') - 1;
        $currentYear = (int)date('Y');

        $month = $this->getSelectedMonths($months, $currentMonth, $currentYear, $selectedMonth);
        $preparedData = array_filter(array_map(function ($item) use ($months, $month) {

            $moisDetails = $item->moisDetails ?? [];
            $filteredMonths = array_filter(array_map(function ($detail) use ($months, $month) {
                if (!isset($detail) || !isset($detail['orIntv'], $detail['mois']) || $detail['orIntv'] === "-") {
                    return null;
                }

                $monthIndex = (int)$detail['mois'] - 1;
                $year = $detail['annee'] ?? '';
                $monthKey = sprintf('%04d-%02d', $year, $monthIndex + 1);

                if (array_search($monthKey, array_column($month, 'key')) !== false) {
                    return [
                        'month' => $months[$monthIndex],
                        'year' => $year,
                        'details' => $detail,
                    ];
                }
                return null;
            }, $moisDetails));
            if (empty($filteredMonths)) { return null; }

            return [
                'commercial'     => $item->commercial ?? '',
                'libsuc'         => $item->libsuc ?? '',
                'libserv'        => $item->libServ ?? '',
                'idmat'          => $item->idMat ?? '',
                'marqueMat'      => $item->markMat ?? '',
                'typemat'        => $item->typeMat ?? '',
                'numserie'       => $item->numSerie ?? '',
                'numparc'        => $item->numParc ?? '',
                'casier'         => $item->casier ?? '',
                'filteredMonths' => array_values($filteredMonths),
            ];

        }, $data));

        return ['prepared_data' => $preparedData, 'months' => $month];
    }

    private function getSelectedMonths(array $months, int $currentMonth, int $currentYear, int $selectedOption): array
    {
        $selectedMonths = [];

        switch ($selectedOption) {
            case 3: // 3 mois suivant
            case 6: // 6 mois suivant
                $monthsCount = $selectedOption === 3 ? 4 : 7;
                for ($i = 0; $i < $monthsCount; $i++) {
                    $selectedMonths[] = $this->generateMonthData($months, $currentMonth, $currentYear, $i);
                }
                // Compléter avec les mois précédents
                for ($i = -1; count($selectedMonths) < 12; $i--) {
                    array_unshift($selectedMonths, $this->generateMonthData($months, $currentMonth, $currentYear, $i));
                }
                break;

            case 9: // Année en cours
                for ($i = 0; $i < 12; $i++) {
                    $selectedMonths[] = [
                        'month' => $months[$i],
                        'year' => $currentYear,
                        'key' => sprintf('%04d-%02d', $currentYear, $i + 1),
                    ];
                }
                break;

            case 11: // Année suivante
                for ($i = 0; $i < 12; $i++) {
                    $selectedMonths[] = [
                        'month' => $months[$i],
                        'year' => $currentYear + 1,
                        'key' => sprintf('%04d-%02d', $currentYear + 1, $i + 1),
                    ];
                }
                break;
            case 12: // 12 mois suivant (à partir du mois suivant le mois courant)
                for ($i = 0; $i < 12; $i++) {
                    $selectedMonths[] = $this->generateMonthData($months, $currentMonth, $currentYear, $i);
                }
                break;

            case 13: // 12 mois précédent (jusqu'au mois précédent le mois courant)
                for ($i = -11; $i <= 0; $i++) {
                    $selectedMonths[] = $this->generateMonthData($months, $currentMonth, $currentYear, $i);
                }
                break;

            case 14: // Année précédente
                $previousYear = $currentYear - 1;
                for ($i = 0; $i < 12; $i++) {
                    $selectedMonths[] = [
                        'month' => $months[$i],
                        'year' => $previousYear,
                        'key' => sprintf('%04d-%02d', $previousYear, $i + 1),
                    ];
                }
                break;
        }
        return $selectedMonths;
    }

        private function generateMonthData(array $months, int $currentMonth, int $currentYear, int $offset): array
    {
        $totalMonths = $currentMonth + $offset;
        $monthIndex = ($totalMonths % 12 + 12) % 12; // Assure un index valide entre 0-11
        $year = $currentYear + intdiv($totalMonths, 12);

        if ($totalMonths < 0 && $monthIndex > $currentMonth) {
            $year--; // Si l'offset est négatif et que le mois calculé est après le mois courant, ajuste l'année.
        }

        return [
            'month' => $months[$monthIndex],
            'year' => $year,
            'key' => sprintf('%04d-%02d', $year, $monthIndex + 1),
        ];
    }
}