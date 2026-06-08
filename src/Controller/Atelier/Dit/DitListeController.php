<?php

namespace App\Controller\Atelier\Dit;

use App\Constants\admin\ApplicationConstant;
use App\Controller\Controller;
use App\Mapper\Atelier\Dit\DitListeMapper;
use App\Model\Atelier\Dit\DitListeModel;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitListeController extends Controller
{
    /**
     * @Route("/dit-liste", name="dit_index")
     */
    public function index()
    {
        $criteria = [];


        return $this->render('atelier/dit/list.html.twig', [
            'data'          => $this->getDataDitEnDto()['data'],
            'currentPage'   => $this->getDataDitEnDto()['currentPage'],
            'totalPages'    => $this->getDataDitEnDto()['lastPage'],
            'criteria'      => $criteria,
            'resultat'      => $this->getDataDitEnDto()['totalItems'],
            'statusCounts'  => $this->getDataDitEnDto()['statusCounts']
        ]);
    }

    private function getDataDitEnDto(): array
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        // Agences Services autorisés sur le DIT
        $agenceServiceAutorises = $this->getSecurityService()->getAgenceServices(ApplicationConstant::CODE_DIT);
        $allAgenceServices = $this->getSecurityService()->getAllAgenceServices();

        $dits = (new DitListeModel())->findPaginatedAndFiltered($codeSociete);
        $ditDto = (new DitListeMapper())->map($dits['data']);

        return [
            'data' => $ditDto,
            'totalItems' => $dits['totalItems'],
            'currentPage' => $dits['currentPage'],
            'lastPage' => $dits['lastPage'],
            'statusCounts' => $dits['statusCounts'],
        ];
    }
}
