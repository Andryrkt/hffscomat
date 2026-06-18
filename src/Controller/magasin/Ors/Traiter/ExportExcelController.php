<?php

namespace App\Controller\magasin\Ors\Traiter;

use App\Controller\Controller;
use App\Dto\Magasin\Ors\Traiter\OrATraiterSearchDto;
use App\Factory\magasin\Ors\Traiter\OrATraiterSearchFactory;
use App\Form\magasin\Ors\Traiter\OrATraiterSearchType;
use App\Model\magasin\Ors\Traiter\OrTraiterModel;
use App\Service\ExcelService;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/or")
 */
class ExportExcelController extends Controller
{
    /**
     * @Route("/magasin-list-or-traiter-export-excel", name="export_magasin_list_or_traiter")
     */
    public function exportExcel()
    {

        $dtoSearch = (new OrATraiterSearchFactory(
            $this->getSecurityService()
        ))->initialisationSearch();

        $criteria = $this->getSessionService()
            ->get('magasin_liste_or_traiter_search_criteria', $dtoSearch);


        $orTraiterModel = new OrTraiterModel();
        $orTraiters = $orTraiterModel->recupereListeMaterielValider($criteria);

        //Transformation
        $data = $this->transformationEnTableauAvecEntet($orTraiters);

        (new ExcelService())->createSpreadsheet($data);
    }

    private function transformationEnTableauAvecEntet(array $orTraiters): array
    {
        $data = [];
        $data[] = ['N° DIT', 'N° Or', 'Date planning', "Niv. d'urg", "Date Or", "Agence Emetteur", "Service Emetteur", 'Agence Débiteur', 'Service Débiteur', 'N° Intv', 'N° lig', 'Cst', 'Réf.', 'Désignations', 'Qté demandée', 'Utilisateur', 'ID Materiel', 'Marque', 'Casier'];
        foreach ($orTraiters as $orTraiter) {
            $data[] = [
                $orTraiter['numero_dit'],
                $orTraiter['numero_or'],
                $orTraiter['dateplanning'],
                $orTraiter['niveauUrgence'] ?? '',
                $orTraiter['datecreation'],
                $orTraiter['agencecrediteur'],
                $orTraiter['servicecrediteur'],
                $orTraiter['agence'],
                $orTraiter['service'],
                $orTraiter['numinterv'],
                $orTraiter['numeroligne'],
                $orTraiter['constructeur'],
                $orTraiter['referencepiece'],
                $orTraiter['designation'],
                $orTraiter['quantitedemander'],
                $orTraiter['nomprenom'],
                $orTraiter['numero_mat'],
                $orTraiter['marque'],
                $orTraiter['casier']
            ];
        }

        return $data;
    }
}
