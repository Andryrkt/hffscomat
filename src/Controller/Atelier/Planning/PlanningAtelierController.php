<?php

namespace App\Controller\Atelier\Planning;

use App\Controller\Controller;
use App\Dto\Atelier\Planning\PlanningAtelierSearchDto;
use App\Form\Atelier\Planning\PlanningAtelierSearchType;
use App\Model\Atelier\Planning\PlanningAtelierModel;
use App\Service\Atelier\Planning\PlanningAtelierService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/planning")
 */
class PlanningAtelierController extends Controller
{

    private PlanningAtelierService $service;
    private PlanningAtelierModel $model;

    public function __construct(PlanningAtelierService $service, PlanningAtelierModel $model)
    {
        parent::__construct();
        $this->service = $service;
        $this->model = $model;
    }

    /**
     * @Route("/atelier", name="planning_index")
     */
    public function index(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->getFormFactory()->createBuilder(
            planningAtelierSearchType::class,
            null,
            ['method' => 'GET']
        )->getForm();
        $dto = new PlanningAtelierSearchDto();
        $dto = $this->traitementFormulaireRecherche($form, $request, $dto);
        $codeSociete = 'HFF';

        $startStr = $dto->dateDebut->format('Y-m-d');
        $endStr = $dto->dateFin->format('Y-m-d');

        if (!$startStr && !$endStr) {
            [$startStr, $endStr] = $this->model->getMinMaxDates($codeSociete, $dto);
        }

        $result = $this->model->getList($codeSociete, $dto);
        $processedData = $this->service->process($result, $startStr, $endStr);

        $output = $processedData['planning'];
        $dates = $processedData['dates'];
        $filteredDates = $processedData['filteredDates'];

        $this->getSessionService()->set('data_export_planningAtelier_excel', $output);
        $this->getSessionService()->set('dates_export_planningAtelier_excel', $dates);

        return $this->render('atelier/planning/atelier/planningAtelier.html.twig', [
            'form' => $form->createView(),
            'dates' => $dates,
            'filteredDates' => $filteredDates,
            'planning' => $output
        ]);
    }

    private function traitementFormulaireRecherche(FormInterface $form, Request $request): PlanningAtelierSearchDto
    {
        $form->handleRequest($request);
        $dto = $form->getData() ?? new PlanningAtelierSearchDto();

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getSessionService()->set('planning_atelier_search_criteria', $dto);
        }

        return $dto;
    }

}