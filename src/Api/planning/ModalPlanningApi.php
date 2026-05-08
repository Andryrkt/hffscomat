<?php

namespace App\Api\planning;

use App\Controller\Controller;
use App\Model\planning\ModalPlanningModel;
use Symfony\Component\Routing\Annotation\Route;

class ModalPlanningApi extends Controller
{
    private ModalPlanningModel $planningModel;

    public function __construct()
    {
        parent::__construct();
        $this->planningModel = new ModalPlanningModel();
    }

    /**
     * @Route("/api/technicien-intervenant/{numOr}/{numItv}", name="api_technicien_intervenant")
     */
    public function TechnicienIntervenant($numOr, $numItv)
    {
        $matriculeNom = $this->planningModel->recupTechnicientIntervenant($numOr, $numItv);

        if (empty($matriculeNom)) {
            $matriculeNom = $this->planningModel->recupTechnicien2($numOr, $numItv);
        }

        header("Content-type:application/json");

        echo json_encode($matriculeNom);
    }
}
