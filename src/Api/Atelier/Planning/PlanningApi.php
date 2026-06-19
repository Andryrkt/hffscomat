<?php

namespace App\Api\Atelier\Planning;

use App\Controller\Controller;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Planning\PlanningMaterielModel;
use App\Model\Atelier\Planning\PlanningModel;
use Symfony\Component\Routing\Annotation\Route;

class PlanningApi extends Controller
{
    private PlanningModel $planningModel;
    private PlanningMaterielModel $planningMaterielModel;
    private DitModel $ditModel;

    public function __construct()
    {
        parent::__construct();
        $this->planningModel = new PlanningModel();
        $this->planningMaterielModel = new PlanningMaterielModel();
        $this->ditModel = new DitModel();
    }

    /**
     * @Route("/api/serviceDebiteurPlanning-fetch/{agentId}", name="api_serviceDebiteurPlanning_fetch")
     */
    public function serviceDebiteur(int $agentId)
    {
        if ($agentId == 10) {
            $serviceDebiteur = [];
        }
        else
        {
            $serviceDebiteur = $this->planningModel->getServiceDebiteByAgence($agentId);
        }

        header("Content-type:application/json");

        echo json_encode($serviceDebiteur);
    }

    /**
     * @Route("/api/detail-modal/{numOr}", name="api_detailModal_fetch")
     */
    public function detailModal(string $numOr)
    {
        $dto = $this->getSessionService()->get('planning_search_criteria', []);
        if ($numOr === '')
            $details = [];
        else
        {
            $details = $this->planningMaterielModel->getDetailPieceInformix($numOr, $dto);
            $numDit = $this->ditModel->getNumDitByNumOr($numOr, 'HF');
            $detailSize = count($details);

            $magasins = [];
            $parts = [];
            for ($i = 0; $i < $detailSize; $i++) {
                if ($details[$i]['num_cmd_cis'])
                {
                    $parts[] = $this->planningModel->getEtatPiecePartiel($details[$i]['num_cmd_cis'], $details[$i]['ref']);
                    $magasins[] = $this->planningModel->getEtaMagasin($details[$i]['num_cmd_cis'], $details[$i]['ref'], $details[$i]['cst']);
                } else
                {
                    $parts[] = [];
                }

                $details[$i]['Eta_ivato'] = "";
                $details[$i]['Eta_magasin'] = "";
                $details[$i]['Est_ship_date'] = "";

                if ($parts[$i])
                {
                    $details[$i]['qteSolde'] = $parts[$i]['0']['solde'];
                    $details[$i]['qte'] = $parts[$i]['0']['qte'];
                }
                else
                {
                    $details[$i]['qteSolde'] = "";
                    $details[$i]['qte'] = "";
                }

                $details[$i]['Ord'] = "";
                $details[$i]['date_liv'] = "";
                $details[$i]['dateAllLIg'] = "";
                $details[$i]['qteORlig'] = "";
                $details[$i]['qtealllig'] = "";
                $details[$i]['qterlqlig'] = "";
                $details[$i]['qtelivlig'] = "";
                $details[$i]['migration'] = "";
                $details[$i]['numDit'] = $numDit;
            }

            header("Content-type:application/json");

            echo json_encode([
                'avecOnglet' => false,
                'data' => $details,
            ]);
        }
    }

    /**
     * @Route("/api/technicien-intervenant/{numOr}/{numItv}", name="api_technicien_intervenant")
     */
    public function TechnicienIntervenant($numOr, $numItv)
    {
        $matriculeNom = $this->planningModel->getTechnicientIntervenantSkw($numOr, $numItv);

        if (empty($matriculeNom)) {
            $matriculeNom = $this->planningModel->getTechnicientIntervenantItv($numOr, $numItv);
        }

        header("Content-type:application/json");

        echo json_encode($matriculeNom);
    }

    private function regroupeParIntervention(array $details): array
    {
        $groupedDetails = [];

        foreach ($details as $detail) {
            $itvKey = $detail['num_itv']; // La valeur de 'num_itv' utilisée comme clé
            if (!isset($groupedDetails[$itvKey])) {
                $groupedDetails[$itvKey] = [];
            }
            $groupedDetails[$itvKey][] = $detail; // Ajouter l'élément au groupe correspondant
        }
        return $groupedDetails;
    }

}