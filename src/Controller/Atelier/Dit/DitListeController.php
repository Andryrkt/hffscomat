<?php

namespace App\Controller\Atelier\Dit;

use App\Constants\admin\ApplicationConstant;
use App\Controller\Controller;
use App\Dto\Atelier\Dit\DitSearchDto;
use App\Form\Atelier\Dit\DitSearchType;
use App\Mapper\Atelier\Dit\DitListeMapper;
use App\Model\Atelier\Dit\DitListeModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitListeController extends Controller
{
    private DitListeModel $ditListeModel;


    public function __construct(DitListeModel $ditListeModel)
    {
        parent::__construct();
        $this->ditListeModel = $ditListeModel;
    }
    /**
     * @Route("/dit-liste", name="dit_index")
     */
    public function index(Request $request)
    {
        $criteria = [];

        $allAgenceServices = $this->getSecurityService()->getAllAgenceServices();
        //création et initialisation du formulaire de la recherche
        $form = $this->getFormFactory()->createBuilder(DitSearchType::class, null, [
            'method' => 'GET',
            'allAgenceServices' => $allAgenceServices
        ])->getForm();
        $dto = new DitSearchDto();
        $dtoSearch  = $this->traitementFormualireRecherhce($form, $request, $dto);

        $dataDit = $this->getDataDitEnDto($dtoSearch);

        return $this->render('atelier/dit/list.html.twig', [
            'data'          => $dataDit['data'],
            'currentPage'   => $dataDit['currentPage'],
            'totalPages'    => $dataDit['lastPage'],
            'criteria'      => $criteria,
            'resultat'      => $dataDit['totalItems'],
            'statusCounts'  => $dataDit['statusCounts'],
            'form'          => $form->createView()
        ]);
    }

    /**
     * Recupération des données à afficher
     *
     * @return array
     */
    private function getDataDitEnDto(DitSearchDto $dtoSearch): array
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        // Agences Services autorisés sur le DIT
        $agenceServiceAutorises = $this->getSecurityService()->getAgenceServices(ApplicationConstant::CODE_DIT);
        $allAgenceServices = $this->getSecurityService()->getAllAgenceServices();

        $dits = $this->ditListeModel->findPaginatedAndFiltered($codeSociete, $dtoSearch);
        $ditDto = (new DitListeMapper())->map($dits['data']);

        return [
            'data' => $ditDto,
            'totalItems' => $dits['totalItems'],
            'currentPage' => $dits['currentPage'],
            'lastPage' => $dits['lastPage'],
            'statusCounts' => $dits['statusCounts'],
        ];
    }

    private function traitementFormualireRecherhce(FormInterface $form, Request $request, DitSearchDto $dto): DitSearchDto
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();

            //recupères les données du criteria dans une session nommé dit_serch_criteria
            $this->getSessionService()->set('dit_search_criteria', $dto);
        }

        return $dto;
    }
}
