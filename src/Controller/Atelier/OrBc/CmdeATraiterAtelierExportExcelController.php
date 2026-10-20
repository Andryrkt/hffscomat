<?php

namespace App\Controller\Atelier\OrBc;

ini_set('memory_limit', '512M');

use App\Controller\Controller;
use App\Dto\Atelier\OrBc\CmdeATraitrerAtelierSearchDto;
use App\Model\Atelier\OrBc\CmdeATraiterAtelierModel;
use App\Service\ExcelService;
use DateTime;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/or-bc-negoce")
 */
class CmdeATraiterAtelierExportExcelController extends Controller
{



    /**
     * @Route("/export-excel", name="atelier_commande_a_traiter_export")
     */
    public function exportExcel()
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        // Récupération des critères depuis la session
        $dtoSearch = $this->getSessionService()->get('criteria_for_excel_atelier_cmde_a_traiter_liste');

        // Si aucun critère en session (ex: première visite), on crée un DTO vide
        if (!$dtoSearch instanceof CmdeATraitrerAtelierSearchDto) {
            $dtoSearch = new CmdeATraitrerAtelierSearchDto();
        }
        $cmdeAtraiterAtelierModel = new CmdeATraiterAtelierModel($this->getSecurityService());
        // Récupération de TOUTES les données filtrées
        $cmdeDatas = $cmdeAtraiterAtelierModel->DonnerAAjouterExcel($dtoSearch, $codeSociete);

        // Convertir les données en tableau avec en-têtes
        $data = $this->transformationEnTableauAvecEntet($cmdeDatas);

        $filename = 'commandes_a_traiter_' . (new DateTime())->format('Y-m-d_His') . '.xlsx';
        // Création du fichier excel
        (new ExcelService())->createSpreadsheet($data, $filename);
    }

    private function transformationEnTableauAvecEntet($cmdeDatas): array
    {
        $data = [];

        // En-têtes des colonnes (identiques au tableau Twig)
        $data[] = [
            'Type',
            'N° OR / BC',
            'Date OR / BC',
            'Constructeur',
            'Référence',
            'Désignation',
            'Qté demandée',
            'Qté reliquat',
            'Qté réservée / à livrer',
            'Date allocation'
        ];

        foreach ($cmdeDatas as $cmde) {

            // Formatage de la date pour Excel
            $dateDoc = '';
            if (!empty($cmde["date_doc"])) {
                $dateDoc = (new \DateTime($cmde["date_doc"]))->format('d/m/Y');
            }

            $dateAlloc = '';
            if (!empty($cmde["date_alloc"])) {
                $dateAlloc = (new \DateTime($cmde["date_alloc"]))->format('d/m/Y');
            }

            $data[] = [
                $cmde["type_doc"] ?? '',
                $cmde["numero_doc"] ?? '',
                $dateDoc,
                $cmde["constructeur"] ?? '',
                $cmde["reference"] ?? '',
                $cmde["designation"] ?? '',
                $cmde["qte_demandee"] ?? 0,
                $cmde["qte_reliquat"] ?? 0,
                $cmde["qte_reservee"] ?? 0,
                $dateAlloc,
            ];
        }

        return $data;
    }
}
