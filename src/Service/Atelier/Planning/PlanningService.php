<?php

namespace App\Service\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningMaterielDto;
use App\Model\Atelier\Planning\PlanningModel;
use DateTime;

class PlanningService
{

    private PlanningModel $planningModel;

    public function __construct()
    {
        $this->planningModel = new PlanningModel();
    }

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

    public function getDetailledDataList(array $data, array $back, bool $sendCmd = false, bool $excelBack = false): array
    {
        $result = [];
        $data_excel = [];

        if (!empty($data)) {
            $qteCis = [];
            $dateLivLigCIS = [];
            $dateAllLigCIS = [];
            for ($i = 0; $i < count($result); $i++) {
                $orItv = $result[$i]['orintv'];
                if (in_array($orItv, $back)) {
                    $result[$i]['backOrder'] = $excelBack === false ? 'back' : '';
                }
                else {
                    $result[$i]['backOrder'] = $excelBack === false ? 'not' : '';
                }
                if (substr($result[$i]['numor'], 0, 1) == '5') {
                    if ($result[$i]['numcis'] !== "0" || $result[$i]['numerocdecis'] == "0") {
                        $recupGcot = [];
                        //$qteCis[] = $this->planningModel->recupeQteCISlig($result[$i]['numor'], $result[$i]['itv'], $result[$i]['ref']);
                        //$dateLivLigCIS[] = $this->planningModel->dateLivraisonCIS($result[$i]['numcis'], $result[$i]['ref'], $result[$i]['cst']);
                        //$dateAllLigCIS[] = $this->planningModel->dateAllocationCIS($result[$i]['numcis'], $result[$i]['ref'], $result[$i]['cst']);
                        //$recupGcot['ord'] = $this->planningModel->recuperationinfodGcot($result[$i]['numerocdecis']);
                    } else {
                        $etatMag[] = $this->planningModel->getEtaMagasin($result[$i]['numerocdecis'], $result[$i]['ref'], $result[$i]['cst']);
                        //$qteCis[] = $this->planningModel->recupeQteCISlig($result[$i]['numor'], $result[$i]['itv'], $result[$i]['ref']);
                        //$dateLivLigCIS[] = $this->planningModel->dateLivraisonCIS($result[$i]['numcis'], $result[$i]['ref'], $result[$i]['cst']);
                        //$dateAllLigCIS[] = $this->planningModel->dateAllocationCIS($result[$i]['numcis'], $result[$i]['ref'], $result[$i]['cst']);
                        //$recupGcot['ord'] = $this->planningModel->recuperationinfodGcot($result[$i]['numerocdecis']);
                        $recupPartiel[] = $this->planningModel->getEtaPiecePart($result[$i]['numerocdecis'], $result[$i]['ref']);
                    }
                }
                else {
                    if (empty($result[$i]['numerocmd']) || $result[$i]['numerocmd'] == '0') {
                        $recupGcot = [];
                    } else {
                        $recupPartiel[] = $this->planningModel->getEtaPiecePart($result[$i]['numerocmd'], $result[$i]['ref']);
                        $etatMag[] = $this->planningModel->getEtaMagasin($result[$i]['numerocmd'], $result[$i]['ref'], $result[$i]['cst']);
                        //$recupGcot['ord'] = $this->planningModel->recuperationinfodGcot($result[$i]['numerocmd']);
                    }
                }


                if (!empty($etatMag[0])) {
                    $result[$i]['Eta_ivato'] = $etatMag[0][0]['Eta_ivato'];
                    $result[$i]['Eta_magasin'] = $etatMag[0][0]['Eta_magasin'];
                    $etatMag = [];
                }
                else {
                    $result[$i]['Eta_ivato'] = "";
                    $result[$i]['Eta_magasin'] = "";
                    $etatMag = [];
                }

                if (!empty($recupPartiel[$i])) {
                    $result[$i]['qteSlode'] = $recupPartiel[$i]['0']['solde'];
                    $result[$i]['qte'] = $recupPartiel[$i]['0']['qte'];
                } else {
                    $result[$i]['qteSlode'] = "";
                    $result[$i]['qte'] = "";
                }

                if (!empty($recupGcot)) {
                    $result[$i]['Ord'] = $recupGcot['ord'] === false ? '' : ($sendCmd === false ? $recupGcot['ord']['Ord'] : "oui");
                } else {
                    $result[$i]['Ord'] = "";
                }

                if (!empty($dateLivLigCIS[$i][0])) {
                    $result[$i]['dateLivLIg'] = $dateLivLigCIS[$i]['0']['datelivlig'];
                } else {
                    $result[$i]['dateLivLIg'] = "";
                }

                if (!empty($dateAllLigCIS)) {
                    $result[$i]['dateAllLIg'] = $dateAllLigCIS[0]['0']['datealllig'];
                } else {
                    $result[$i]['dateAllLIg'] = "";
                }

                if (!empty($qteCis)) {
                    if (!empty($qteCis[$i])) {
                        $result[$i]['qteORlig'] = $qteCis[$i]['0']['qteorlig'];
                        $result[$i]['qtealllig'] = $qteCis[$i]['0']['qtealllig'];
                        $result[$i]['qterlqlig'] = $qteCis[$i]['0']['qtereliquatlig'];
                        $result[$i]['qtelivlig'] = $qteCis[$i]['0']['qtelivlig'];
                    } elseif (isset($qteCis[$i - 1]) && !empty($qteCis[$i - 1])) {
                        $result[$i]['qteORlig'] = $qteCis[$i - 1]['0']['qteorlig'];
                        $result[$i]['qtealllig'] = $qteCis[$i - 1]['0']['qtealllig'];
                        $result[$i]['qterlqlig'] = $qteCis[$i - 1]['0']['qtereliquatlig'];
                        $result[$i]['qtelivlig'] = $qteCis[$i - 1]['0']['qtelivlig'];
                    } else {
                        $result[$i]['qteORlig'] = "";
                        $result[$i]['qtealllig'] = "";
                        $result[$i]['qterlqlig'] = "";
                        $result[$i]['qtelivlig'] = "";
                    }
                } else {
                    $result[$i]['qteORlig'] = "";
                    $result[$i]['qtealllig'] = "";
                    $result[$i]['qterlqlig'] = "";
                    $result[$i]['qtelivlig'] = "";
                }
                if ($result[$i]['qtelivlig'] > 0 && $result[$i]['qtealllig'] == 0 && $result[$i]['qterlqlig'] == 0) {
                    $result[$i]['StatutCIS'] = "LIVRE";
                    $result[$i]['DateStatutCIS'] = $result[$i]['dateLivLIg'];
                } elseif ($result[$i]['qtealllig'] > 0) {
                    $result[$i]['StatutCIS'] = "A LIVRER";
                    $result[$i]['DateStatutCIS'] = $result[$i]['dateAllLIg'];
                } else {
                    $result[$i]['StatutCIS'] = "";
                    $result[$i]['DateStatutCIS'] = "";
                }
                // dump($i, $result[$i]['numcis'] , $result[$i]['numerocmd'] );
                if (substr($result[$i]['numcis'], 0, 1) !== '1') {
                    $result[$i]['numcde_cis'] = $result[$i]['numcis'];
                    $result[$i]['numcisOR'] = '';
                } else {
                    $result[$i]['numcde_cis'] = $result[$i]['numcis'];
                    $result[$i]['numcisOR'] = $result[$i]['numcis'];
                }


                if ($result[$i]['statut'] == "" || $result[$i]['statut'] == null) {
                    $statutDetail = "";
                } else {
                    $statutDetail = $result[$i]['statut'];
                }
                if ($result[$i]['StatutCIS'] == "" || $result[$i]['StatutCIS'] == null) {
                    $statutCisDetail = "";
                } else {
                    $statutCisDetail = $result[$i]['StatutCIS'];
                }
                if ($result[$i]['datestatut'] == "" || $result[$i]['datestatut'] == null) {
                    $datestatutDetail = "";
                } else {
                    $datestatutDetail = (new DateTime($result[$i]['datestatut']))->format('d/m/Y');
                }
                if ($result[$i]['DateStatutCIS'] == "" || $result[$i]['DateStatutCIS'] == null) {
                    $datestatutCisDetail = "";
                } else {
                    $datestatutCisDetail = (new DateTime($result[$i]['DateStatutCIS']))->format('d/m/Y');
                }
                if ($result[$i]['Eta_ivato'] == "" || $result[$i]['Eta_ivato'] == null) {
                    $dateEtaIvato = "";
                } else {
                    $dateEtaIvato = (new DateTime($result[$i]['Eta_ivato']))->format('d/m/Y');
                }
                if ($result[$i]['Eta_magasin'] == "" || $result[$i]['Eta_magasin'] == null) {
                    $dateEtaMag = "";
                } else {
                    $dateEtaMag = (new DateTime($result[$i]['Eta_magasin']))->format('d/m/Y');
                }
                $row = [
                    'agenceServiceTravaux' => $result[$i]['libsuc'] . ' - ' . $result[$i]['libserv'],
                    'Marque' => $result[$i]['markmat'],
                    'Modele' => $result[$i]['typemat'],
                    'Id' => $result[$i]['idmat'],
                    'N_Serie' => $result[$i]['numserie'],
                    'parc' => $result[$i]['numparc'],
                    'casier' => $result[$i]['casier'],
                    'commentaire' => $result[$i]['commentaire'],
                    'numor_itv' => $result[$i]['numor'] . '-' . $result[$i]['itv'],
                    'dateplanning' => $result[$i]['dateplanning'] == "" ? null : (new DateTime($result[$i]['dateplanning'])),
                    'cst' => $result[$i]['cst'],
                    'ref' => $result[$i]['ref'],
                    'desi' => $result[$i]['desi'],
                    'qteres_or' => $result[$i]['qteres_or'] == 0 ? '' : $result[$i]['qteres_or'],
                    'qteall_or' => $result[$i]['qteall'] == 0 ? '' : $result[$i]['qteall'],
                    'qtereliquat' => $result[$i]['qtereliquat'] == 0 ? '' : $result[$i]['qtereliquat'],
                    'qteliv_or' => $result[$i]['qteliv'] == 0 ? '' : $result[$i]['qteliv'],
                    'statutOR' => $statutDetail,
                    'datestatutOR' => $datestatutDetail,
                    'ctr_marque' => $result[$i]['numcde_cis'] == 0 ? '' : $result[$i]['numcde_cis'],
                    'numerocmd' => $result[$i]['numerocdecis'],
                    'statut_ctrmq' => $result[$i]['statut_ctrmq'] . $result[$i]['statut_ctrmq_cis'],
                    'numcis' => $result[$i]['numcisOR'] == 0 ? '' : $result[$i]['numcisOR'],
                    'qteORlig_cis' => $result[$i]['qteORlig'] == 0 ? '' : $result[$i]['qteORlig'],
                    'qtealllig_cis' => $result[$i]['qtealllig'] == 0 ? '' : $result[$i]['qtealllig'],
                    'qterlqlig_cis' => $result[$i]['qterlqlig'] == 0 ? '' : $result[$i]['qterlqlig'],
                    'qtelivlig_cis' => $result[$i]['qtelivlig'] == 0 ? '' : $result[$i]['qtelivlig'],
                    'statutCis' => $statutCisDetail,
                    'datestatutCis' => $datestatutCisDetail,
                    'Eta_ivato' => $dateEtaIvato == '01/01/1900' ? '' : $dateEtaIvato,
                    'Eta_magasin' => $dateEtaMag == '01/01/1900' ? '' : $dateEtaMag,
                    'message' => $result[$i]['message'],
                    'ord' => $result[$i]['Ord'], //*****
                    'status_b' => $result[$i]['status_b'],
                    'Qte_Solde' => $result[$i]['qteSlode'],
                    'qte' => $result[$i]['qte'],
                    'backorder' => $result[$i]['backOrder']
                ];

                $row_excel = $row;
                $row_excel['backorder'] = ''; // Supprimer la partie visuelle Excel
                $row_excel['ord'] = $row_excel['ord'] !== '' ? 'oui' : ''; // Excel

                $data[] = $row;
                $data_excel[] = $row_excel;
            }
        }

        return ['data' => $data, 'data_excel' => $data_excel];

    }

}