<?php

namespace App\Controller\Atelier\Planning;

use App\Controller\Controller;
use App\Controller\Traits\Transformation;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Form\Atelier\Planning\PlanningSearchType;
use App\Model\Atelier\Planning\PlanningModel;
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
    private PlanningSearchDto $searchDto;

    public function __construct()
    {
        parent::__construct();
        $this->planningModel = new PlanningModel();
        $this->searchDto = new PlanningSearchDto();
        $this->searchDto->annee = date('Y');
        $this->searchDto->facture = 'ENCOURS';
        $this->searchDto->plan = 'PLANIFIE';
        $this->searchDto->interneExterne = 'TOUS';
        $this->searchDto->typeLigne = 'TOUETS';
        $this->searchDto->months = 3;
    }

    /**
     * @Route("/planning-vue", name="planning_vue")
     */
    public function listPlanning(Request $request)
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

        

        return $this->render('atelier/planning/planning.html.twig', [
            'form' => $form->createView()
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