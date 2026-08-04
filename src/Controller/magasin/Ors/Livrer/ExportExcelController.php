<?php

namespace App\Controller\magasin\Ors\Livrer;

use App\Service\ExcelService;
use App\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\magasin\Ors\Livrer\OrLivrerModel;
use App\Dto\Magasin\Ors\Livrer\MaterielALivrerDto;
use App\Factory\magasin\Ors\Livrer\OrLivrerSearchFactory;

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
        $dtoSearch = (new OrLivrerSearchFactory($this->getSecurityService()))->initialisationSearch();

        $criteria = $this->getSessionService()->get('magasin_liste_or_livrer_search_criteria', $dtoSearch);

        $orLivrerModel = new OrLivrerModel();
        $orLivrers = $orLivrerModel->recupereListeMaterielValider($criteria);

        //Transformation
        $data = $this->transformationEnTableauAvecEntet($orLivrers);

        (new ExcelService())->createSpreadsheet($data);
    }

    /** 
     * @param array<MaterielALivrerDto> $orLivrers
     * 
     * @return array
     */
    private function transformationEnTableauAvecEntet(array $orLivrers): array
    {
        $data = [];
        $data[] = [
            'N° DIT',
            'N° Or',
            'Date planning',
            "Niv. d'urg",
            "Date Or",
            "Agence Emetteur",
            "Service Emetteur",
            'Agence Débiteur',
            'Service Débiteur',
            'N° Intv',
            'N° lig',
            'Cst',
            'Réf.',
            'Désignations',
            'Qté demandée',
            'Qté a livrer',
            'Qté déjà livrée',
            'Utilisateur',
            'ID Materiel',
            'N° Serie',
            'N° Parc',
            'Marque',
            'Casier'
        ];

        foreach ($orLivrers as $orLivrer) {
            $data[] = [
                $orLivrer->referenceDit,
                $orLivrer->numeroOr,
                $orLivrer->getDatePlanningFormatee(),
                $orLivrer->niveauUrgence ?? '',
                $orLivrer->getDateCreationFormatee(),
                $orLivrer->agenceCrediteur,
                $orLivrer->serviceCrediteur,
                $orLivrer->agenceDebiteur,
                $orLivrer->serviceDebiteur,
                $orLivrer->numeroIntervention,
                $orLivrer->numeroLigne,
                $orLivrer->constructeur,
                $orLivrer->referencePiece,
                $orLivrer->designation,
                $orLivrer->quantiteDemandee,
                $orLivrer->quantiteALivrer,
                $orLivrer->quantiteLivree,
                $orLivrer->nomPrenom,
                $orLivrer->idMateriel,
                $orLivrer->numeroSerie,
                $orLivrer->numeroParc,
                $orLivrer->marque,
                $orLivrer->casier
            ];
        }

        return $data;
    }
}
