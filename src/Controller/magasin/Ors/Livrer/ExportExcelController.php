<?php

namespace App\Controller\magasin\Ors\Livrer;

use App\Controller\Controller;
use App\Factory\magasin\Ors\Livrer\OrLivrerSearchFactory;
use App\Model\magasin\Ors\Livrer\OrLivrerModel;
use App\Service\ExcelService;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/or")
 */
class ExportExcelController extends Controller
{

    /**
     * @Route("/magasin-list-or-livrer-export-excel", name="export_liste_or_livrer")
     */
    public function exportExcel()
    {
        $dtoSearch = (new OrLivrerSearchFactory(
            $this->getSecurityService()
        ))->initialisationSearch();
        $criteria = $this->getSessionService()
            ->get('magasin_liste_or_livrer_search_criteria', $dtoSearch);
        $orLivrerModel = new OrLivrerModel();
        $orLivrers = $orLivrerModel->recupereListeMaterielValider($criteria);

        //Transformation
        $data = $this->transformationEnTableauAvecEntet($orLivrers);

        (new ExcelService())->createSpreadsheet($data);
    }

    private function transformationEnTableauAvecEntet(array $orLivrers): array
    {
        $data = [];
        $data[] = ['N° DIT', 'N° Or', 'Date planning', "Niv. d'urg", "Date Or", "Agence Emetteur", "Service Emetteur", 'Agence Débiteur', 'Service Débiteur', 'N° Intv', 'N° lig', 'Cst', 'Réf.', 'Désignations', 'Qté demandée', 'Qté a livrer', 'Qté déjà livrée', 'Utilisateur', 'ID Materiel', 'N° Serie', 'N° Parc', 'Marque', 'Casier'];
        foreach ($orLivrers as $orLivrer) {
            $data[] = [
                $orLivrer['referencedit'],
                $orLivrer['numeroor'],
                $orLivrer['dateplanning'],
                $orLivrer['niveauurgence'] ?? '',
                $orLivrer['datecreation'],
                $orLivrer['agencecrediteur'],
                $orLivrer['servicecrediteur'],
                $orLivrer['agencedebiteur'],
                $orLivrer['servicedebiteur'],
                $orLivrer['numinterv'],
                $orLivrer['numeroligne'],
                $orLivrer['constructeur'],
                $orLivrer['referencepiece'],
                $orLivrer['designation'],
                $orLivrer['quantitedemander'],
                $orLivrer['qtealivrer'],
                $orLivrer['quantitelivree'],
                $orLivrer['nomprenom'],
                $orLivrer['idmateriel'],
                $orLivrer['num_serie'],
                $orLivrer['num_parc'],
                $orLivrer['marque'],
                $orLivrer['casie']
            ];
        }

        return $data;
    }
}
