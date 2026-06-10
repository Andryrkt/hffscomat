<?php

namespace App\Api\magasin;

use App\Controller\Controller;
use App\Model\magasin\Ors\Traiter\OrTraiterModel;
use Symfony\Component\Routing\Annotation\Route;

class agenceServiceInformixApi extends Controller
{
    /** 
     * RECUPERATION SERVICE INFORMIX
     * @Route("/api/service-informix-fetch/{agence}", name="api_service_informix_fetch") 
     * */
    public function agenceInformix(string $agence)
    {
        $OrTraiterModel = new OrTraiterModel();
        $service = $OrTraiterModel->service($agence);

        header("Content-type:application/json");

        echo json_encode($service);
    }
}
