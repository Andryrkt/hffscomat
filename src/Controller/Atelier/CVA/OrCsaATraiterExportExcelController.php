<?php

namespace App\Controller\Atelier\CVA;


use App\Controller\Controller;
use App\Dto\Atelier\CVA\OrCsaATraiterSearchDto;
use App\Model\Atelier\CVA\OrCsaATraiterListeModel;
use App\Service\ExcelService;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/atelier/cva")
 */
class OrCsaATraiterExportExcelController extends Controller
{

    /**
     * @Route("/export-excel", name="export_excel_or_csa_a_traiter_export")
     */
    public function exportExcel()
    {
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        $criteria = $this->getSessionService()->get('criteria_for_or_csa_a_traiter_liste');
        if (!$criteria instanceof OrCsaATraiterSearchDto) {
            $criteria = new OrCsaATraiterSearchDto();
        }
        $orCsaATraiterListeModel = new OrCsaATraiterListeModel($this->getSecurityService());
        $rows = $orCsaATraiterListeModel->DonnerAAjouterExcel($criteria, $codeSociete);
        $data = $this->transformationEnTableauAvecEntet($rows);

        $fileName = sprintf(
            'OR-CSA-a-traiter_%s.xlsx',
            (new \DateTimeImmutable())->format('Y-m-d_His')
        );
        (new ExcelService())->createSpreadsheet($data, $fileName);
    }

    private function transformationEnTableauAvecEntet(array $rows): array
    {
        $data = [];
        $data[] = [
            'N° OR',
            'Client',
            'Référence client',
            'Type OR',
            'Lead time (j)',
            'Jours dispo',
            'Délai restant',
            'Statut allocation',
        ];

        foreach ($rows as $row) {
            $data[] = [
                isset($row['seor_numor'])       ? trim((string) $row['seor_numor'])       : '',
                $row['client']                  ?? '',
                $row['reference_client']        ?? '',
                $row['type_or']                 ?? '',
                $row['lead_time']               ?? '',
                $row['nb_jours_dispo']          ?? '',
                $row['nb_jours_dispo_lib']      ?? '',
                $row['statut_allocation']       ?? '',
            ];
        }

        return $data;
    }
}
