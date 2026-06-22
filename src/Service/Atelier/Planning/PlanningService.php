<?php

namespace App\Service\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningMaterielDto;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Mapper\Atelier\Planning\PlanningMapper;
use App\Model\Atelier\Planning\PlanningMaterielModel;
use App\Model\Atelier\Planning\PlanningModel;
use DateTime;

class PlanningService
{

    private PlanningModel $planningModel;
    private PlanningMaterielModel $planningMaterielModel;
    private PlanningMapper $mapper;

    public function __construct()
    {
        $this->planningModel = new PlanningModel();
        $this->planningMaterielModel = new PlanningMaterielModel();
        $this->mapper = new PlanningMapper();
    }

    /**
     * @param PlanningSearchDto $dto
     * @return PlanningMaterielDto[]
     */
    public function getPlanningMaterielData(PlanningSearchDto $dto, string $codeSociete): array
    {
        ['num_ors' => $orsValides] = $this->planningModel->getNumeroOrValider($dto);
        ['num_ors' => $orsSoumis] = $this->planningModel->getOrsSoumis();
        ['num_or_itvs' => $numOrItvBack] = $this->planningModel->getBackOrderPlanning($orsValides, $orsSoumis, $dto);

        $rawData = $this->planningMaterielModel->getMaterielPlanifier(
            $orsValides,
            $orsSoumis,
            $numOrItvBack,
            $dto,
            $codeSociete
        );

        $dtos = $this->mapper->toDtoArray($rawData, $numOrItvBack);
        return $this->mapper->groupByMateriel($dtos);
    }

    /**
     * @param PlanningMaterielDto[] $data
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
        $data_excel = [];
        $res = [];

        if (!empty($data)) {
            $qteCis = [];
            $dateLivLigCIS = [];
            $dateAllLigCIS = [];
            for ($i = 0; $i < count($data); $i++) {
                $orItv = $data[$i]['or_itv'];
                if (in_array($orItv, $back)) {
                    $data[$i]['backOrder'] = $excelBack === false ? 'back' : '';
                }
                else {
                    $data[$i]['backOrder'] = $excelBack === false ? 'not' : '';
                }
                if (substr($data[$i]['num_or'], 0, 1) == '5') {
                    if ($data[$i]['num_cis'] !== "0" || $data[$i]['num_cmd_cis'] == "0") {
                        $recupGcot = [];
                        //$qteCis[] = $this->planningModel->recupeQteCISlig($data[$i]['numor'], $data[$i]['itv'], $data[$i]['ref']);
                        //$dateLivLigCIS[] = $this->planningModel->dateLivraisonCIS($data[$i]['numcis'], $data[$i]['ref'], $data[$i]['cst']);
                        //$dateAllLigCIS[] = $this->planningModel->dateAllocationCIS($data[$i]['numcis'], $data[$i]['ref'], $data[$i]['cst']);
                        //$recupGcot['ord'] = $this->planningModel->recuperationinfodGcot($data[$i]['numerocdecis']);
                    } else {
                        //$etatMag[] = $this->planningModel->getEtaMagasin($data[$i]['num_cmd_cis'], $data[$i]['ref'], $data[$i]['cst']);
                        //$qteCis[] = $this->planningModel->recupeQteCISlig($data[$i]['numor'], $data[$i]['itv'], $data[$i]['ref']);
                        //$dateLivLigCIS[] = $this->planningModel->dateLivraisonCIS($data[$i]['numcis'], $data[$i]['ref'], $data[$i]['cst']);
                        //$dateAllLigCIS[] = $this->planningModel->dateAllocationCIS($data[$i]['numcis'], $data[$i]['ref'], $data[$i]['cst']);
                        //$recupGcot['ord'] = $this->planningModel->recuperationinfodGcot($data[$i]['numerocdecis']);
                        //$recupPartiel[] = $this->planningModel->getEtatPiecePartiel($data[$i]['num_cmd_cis'], $data[$i]['ref']);
                    }
                }
                else {
                    if (empty($data[$i]['num_cmd']) || $data[$i]['num_cmd'] == '0') {
                        $recupGcot = [];
                    } else {
                        //$recupPartiel[] = $this->planningModel->getEtatPiecePartiel($data[$i]['num_cmd'], $data[$i]['ref']);
                        //$etatMag[] = $this->planningModel->getEtaMagasin($data[$i]['num_cmd'], $data[$i]['ref'], $data[$i]['cst']);
                        //$recupGcot['ord'] = $this->planningModel->recuperationinfodGcot($data[$i]['num_cmd']);
                    }
                }


                if (!empty($etatMag[0])) {
                    $data[$i]['Eta_ivato'] = $etatMag[0][0]['Eta_ivato'];
                    $data[$i]['Eta_magasin'] = $etatMag[0][0]['Eta_magasin'];
                    $etatMag = [];
                }
                else {
                    $data[$i]['Eta_ivato'] = "";
                    $data[$i]['Eta_magasin'] = "";
                    $etatMag = [];
                }

                if (!empty($recupPartiel[$i])) {
                    $data[$i]['qteSlode'] = $recupPartiel[$i]['0']['solde'];
                    $data[$i]['qte'] = $recupPartiel[$i]['0']['qte'];
                } else {
                    $data[$i]['qteSlode'] = "";
                    $data[$i]['qte'] = "";
                }

                if (!empty($recupGcot)) {
                    $data[$i]['Ord'] = $recupGcot['ord'] === false ? '' : ($sendCmd === false ? $recupGcot['ord']['Ord'] : "oui");
                } else {
                    $data[$i]['Ord'] = "";
                }

                if (!empty($dateLivLigCIS[$i][0])) {
                    $data[$i]['dateLivLIg'] = $dateLivLigCIS[$i]['0']['datelivlig'];
                } else {
                    $data[$i]['dateLivLIg'] = "";
                }

                if (!empty($dateAllLigCIS)) {
                    $data[$i]['dateAllLIg'] = $dateAllLigCIS[0]['0']['datealllig'];
                } else {
                    $data[$i]['dateAllLIg'] = "";
                }

                if (!empty($qteCis)) {
                    if (!empty($qteCis[$i])) {
                        $data[$i]['qteORlig'] = $qteCis[$i]['0']['qteorlig'];
                        $data[$i]['qtealllig'] = $qteCis[$i]['0']['qtealllig'];
                        $data[$i]['qterlqlig'] = $qteCis[$i]['0']['qtereliquatlig'];
                        $data[$i]['qtelivlig'] = $qteCis[$i]['0']['qtelivlig'];
                    } elseif (isset($qteCis[$i - 1]) && !empty($qteCis[$i - 1])) {
                        $data[$i]['qteORlig'] = $qteCis[$i - 1]['0']['qteorlig'];
                        $data[$i]['qtealllig'] = $qteCis[$i - 1]['0']['qtealllig'];
                        $data[$i]['qterlqlig'] = $qteCis[$i - 1]['0']['qtereliquatlig'];
                        $data[$i]['qtelivlig'] = $qteCis[$i - 1]['0']['qtelivlig'];
                    } else {
                        $data[$i]['qteORlig'] = "";
                        $data[$i]['qtealllig'] = "";
                        $data[$i]['qterlqlig'] = "";
                        $data[$i]['qtelivlig'] = "";
                    }
                } else {
                    $data[$i]['qteORlig'] = "";
                    $data[$i]['qtealllig'] = "";
                    $data[$i]['qterlqlig'] = "";
                    $data[$i]['qtelivlig'] = "";
                }
                if ($data[$i]['qtelivlig'] > 0 && $data[$i]['qtealllig'] == 0 && $data[$i]['qterlqlig'] == 0) {
                    $data[$i]['StatutCIS'] = "LIVRE";
                    $data[$i]['DateStatutCIS'] = $data[$i]['dateLivLIg'];
                } elseif ($data[$i]['qtealllig'] > 0) {
                    $data[$i]['StatutCIS'] = "A LIVRER";
                    $data[$i]['DateStatutCIS'] = $data[$i]['dateAllLIg'];
                } else {
                    $data[$i]['StatutCIS'] = "";
                    $data[$i]['DateStatutCIS'] = "";
                }
                // dump($i, $data[$i]['numcis'] , $data[$i]['numerocmd'] );
                if (substr($data[$i]['num_cis'], 0, 1) !== '1') {
                    $data[$i]['num_cmd_cis'] = $data[$i]['num_cis'];
                    $data[$i]['numcisOR'] = '';
                } else {
                    $data[$i]['num_cmd_cis'] = $data[$i]['num_cis'];
                    $data[$i]['numcisOR'] = $data[$i]['num_cis'];
                }


                if ($data[$i]['statut'] == "" || $data[$i]['statut'] == null) {
                    $statutDetail = "";
                } else {
                    $statutDetail = $data[$i]['statut'];
                }
                if ($data[$i]['StatutCIS'] == "" || $data[$i]['StatutCIS'] == null) {
                    $statutCisDetail = "";
                } else {
                    $statutCisDetail = $data[$i]['StatutCIS'];
                }
                if ($data[$i]['date_statut'] == "" || $data[$i]['date_statut'] == null) {
                    $datestatutDetail = "";
                } else {
                    $datestatutDetail = (new DateTime($data[$i]['date_statut']))->format('d/m/Y');
                }
                if ($data[$i]['DateStatutCIS'] == "" || $data[$i]['DateStatutCIS'] == null) {
                    $datestatutCisDetail = "";
                } else {
                    $datestatutCisDetail = (new DateTime($data[$i]['DateStatutCIS']))->format('d/m/Y');
                }
                if ($data[$i]['Eta_ivato'] == "" || $data[$i]['Eta_ivato'] == null) {
                    $dateEtaIvato = "";
                } else {
                    $dateEtaIvato = (new DateTime($data[$i]['Eta_ivato']))->format('d/m/Y');
                }
                if ($data[$i]['Eta_magasin'] == "" || $data[$i]['Eta_magasin'] == null) {
                    $dateEtaMag = "";
                } else {
                    $dateEtaMag = (new DateTime($data[$i]['Eta_magasin']))->format('d/m/Y');
                }
                $row = [
                    'agenceServiceTravaux' => $data[$i]['lib_suc'] . ' - ' . $data[$i]['lib_serv'],
                    'Marque' => $data[$i]['mark_mat'],
                    'Modele' => $data[$i]['type_mat'],
                    'Id' => $data[$i]['id_mat'],
                    'N_Serie' => $data[$i]['num_serie'],
                    'parc' => $data[$i]['num_parc'],
                    'casier' => $data[$i]['casier'],
                    'commentaire' => $data[$i]['commentaire'],
                    'numor_itv' => $data[$i]['num_or'] . '-' . $data[$i]['itv'],
                    'dateplanning' => $data[$i]['date_planning'] == "" ? null : (new DateTime($data[$i]['date_planning'])),
                    'cst' => $data[$i]['cst'],
                    'ref' => $data[$i]['ref'],
                    'desi' => $data[$i]['desi'],
                    'qteres_or' => $data[$i]['qte_res_or'] == 0 ? '' : $data[$i]['qte_res_or'],
                    'qteall_or' => $data[$i]['qte_all'] == 0 ? '' : $data[$i]['qte_all'],
                    'qtereliquat' => $data[$i]['qte_reliquat'] == 0 ? '' : $data[$i]['qte_reliquat'],
                    'qteliv_or' => $data[$i]['qte_liv'] == 0 ? '' : $data[$i]['qte_liv'],
                    'statutOR' => $statutDetail,
                    'datestatutOR' => $datestatutDetail,
                    'ctr_marque' => $data[$i]['num_cmd_cis'] == 0 ? '' : $data[$i]['num_cmd_cis'],
                    'numerocmd' => $data[$i]['num_cmd_cis'],
                    'statut_ctrmq' => $data[$i]['statut_ctrmq'] . $data[$i]['statut_ctrmq_cis'],
                    'numcis' => $data[$i]['num_cis'] == 0 ? '' : $data[$i]['num_cis'],
                    'qteORlig_cis' => $data[$i]['qteORlig'] == 0 ? '' : $data[$i]['qteORlig'],
                    'qtealllig_cis' => $data[$i]['qtealllig'] == 0 ? '' : $data[$i]['qtealllig'],
                    'qterlqlig_cis' => $data[$i]['qterlqlig'] == 0 ? '' : $data[$i]['qterlqlig'],
                    'qtelivlig_cis' => $data[$i]['qtelivlig'] == 0 ? '' : $data[$i]['qtelivlig'],
                    'statutCis' => $statutCisDetail,
                    'datestatutCis' => $datestatutCisDetail,
                    'Eta_ivato' => $dateEtaIvato == '01/01/1900' ? '' : $dateEtaIvato,
                    'Eta_magasin' => $dateEtaMag == '01/01/1900' ? '' : $dateEtaMag,
                    'message' => $data[$i]['message'],
                    'ord' => $data[$i]['Ord'], //*****
                    'status_b' => $data[$i]['statut_b'],
                    'Qte_Solde' => $data[$i]['qteSlode'],
                    'qte' => $data[$i]['qte'],
                    'backorder' => $data[$i]['backOrder']
                ];

                $row_excel = $row;
                $row_excel['backorder'] = ''; // Supprimer la partie visuelle Excel
                $row_excel['ord'] = $row_excel['ord'] !== '' ? 'oui' : ''; // Excel

                $res[] = $row;
                $data_excel[] = $row_excel;
            }
        }

        return ['data' => $res, 'data_excel' => $data_excel];

    }

}