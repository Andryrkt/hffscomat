<?php

namespace App\Controller\Atelier\Planning;

use App\Controller\Controller;
use App\Controller\Traits\Transformation;
use App\Dto\Atelier\Planning\PlanningMaterielDto;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Form\Atelier\Planning\PlanningSearchType;
use App\Mapper\Atelier\Planning\PlanningMapper;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Model\Atelier\Planning\PlanningMaterielModel;
use App\Model\Atelier\Planning\PlanningModel;
use App\Service\Atelier\Planning\PlanningService;
use App\Service\TableauEnStringService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier")
 */
class PlanningController extends Controller
{

    use Transformation;

    private PlanningModel $planningModel;
    private PlanningMaterielModel $planningMaterielModel;
    private DitOrSoumisAValidationModel $ditOrSoumisAValidationModel;
    private PlanningSearchDto $searchDto;

    private PlanningMapper $planningMapper;

    private PlanningService $planningService;

    public function __construct()
    {
        parent::__construct();
        $this->planningModel = new PlanningModel();
        $this->planningMaterielModel = new PlanningMaterielModel();
        $this->ditOrSoumisAValidationModel = new DitOrSoumisAValidationModel();
        $this->searchDto = new PlanningSearchDto();
        $this->searchDto->annee = date('Y');
        $this->searchDto->facture = 'ENCOURS';
        $this->searchDto->plan = 'PLANIFIE';
        $this->searchDto->interneExterne = 'TOUS';
        $this->searchDto->typeLigne = 'TOUTES';
        $this->searchDto->months = 3;
        $this->planningMapper = new PlanningMapper();
        $this->planningService = new PlanningService();
    }

    /**
     * @Route("/planning-vue", name="planning_vue")
     */
    public function listPlanning(Request $request): \Symfony\Component\HttpFoundation\Response
    {

        $form = $this->getFormFactory()->createBuilder(
            PlanningSearchType::class,
            null,
            [
                'method' => 'GET',
                'planningDetaille' => false
            ]
        )->getForm();

        $dto = $this->traitementFormulaire($form, $request);
        $this->getSessionService()->set('planning_search_criteria', $dto);

        if($request->query->get('action') !== 'oui')
        {
            ['num_ors' => $numOrs] = $this->planningModel->getNumeroOrValider($dto);
            ['num_ors' => $numOrSoumis] = $this->planningModel->getOrsSoumis();
            ['num_or_itvs' => $numOrItvBack] = $this->planningModel->getBackOrderPlanning($numOrs, $numOrSoumis, $dto);
            $data = $this->planningMaterielModel->getMaterielPlanifier($numOrs, $numOrSoumis, $numOrItvBack, $dto);
        }
        else
        {
            $data = [];
            $numOrItvBack = [];
        }

        $data = $this->planningMapper->getMaterielData($data, $numOrItvBack);
        $data = $this->planningService->getDataList($data, $dto->months);

        $this->logUserVisit('planning_vue');

        return $this->render('atelier/planning/planning.html.twig', [
            'form' => $form->createView(),
            'preparedData' => $data['prepared_data'],
            'uniqueMonths' => $data['months'],
        ]);

    }

    private function traitementFormulaire(FormInterface $form, Request $request)
    {
        $form->handleRequest($request);
        $dto = $this->searchDto;

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();
        }

        return $dto;
    }

}