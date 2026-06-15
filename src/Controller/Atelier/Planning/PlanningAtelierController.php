<?php

namespace App\Controller\Atelier\Planning;

use App\Controller\Controller;
use App\Dto\Atelier\Planning\PlanningAtelierSearchDto;
use App\Form\Atelier\Planning\PlanningAtelierSearchType;
use App\Model\Atelier\Planning\PlanningAtelierModel;
use App\Service\atelier\Planning\PlanningAtelierService;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier")
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
     * @Route("/planning-atelier", name="planningAtelier_vue")
     */
    public function index(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $form = $this->getFormFactory()->createBuilder(
            PlanningAtelierSearchType::class,
            null,
            ['method' => 'GET']
        )->getForm();
        $form->handleRequest($request);
        $dto = $form->getData() ?? new PlanningAtelierSearchDto();

        $output = [];
        $dates = [];
        $filteredDates = [];

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getSessionService()->set('planning_atelier_search_criteria', $dto);

            $codeSociete = 'HFF';

            $startStr = $dto->dateDebut ? $dto->dateDebut->format('Y-m-d') : null;
            $endStr = $dto->dateFin ? $dto->dateFin->format('Y-m-d') : null;

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
        }

        return $this->render('atelier/planning/atelier/planningAtelier.html.twig', [
            'form' => $form->createView(),
            'dates' => $dates,
            'filteredDates' => $filteredDates,
            'planning' => $output
        ]);
    }

    /**
     * @Route("/planning-atelier-excel", name="export_planning_atelier_excel")
     */
    public function exportExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $data = $this->getSessionService()->get('data_export_planningAtelier_excel');
        $dates = $this->getSessionService()->get('dates_export_planningAtelier_excel');

        $data = $this->service->processExcelData($data, $dates);
        $dateCount = count($dates);

        $rowIdx = 1;
        foreach ($data as $row) {
            $sheet->fromArray($row, null, "A$rowIdx");
            $rowIdx++;
        }

        $colStart = 8;
        for ($i = 0; $i < $dateCount; $i++) {
            $col1 = Coordinate::stringFromColumnIndex($colStart + $i * 2);
            $col2 = Coordinate::stringFromColumnIndex($colStart + $i * 2 + 1);
            $sheet->mergeCells("$col1" . "1:$col2" . "1");
        }

        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export.xlsx"');
        setcookie('fileDownload', 'true', 0, '/');
        $writer->save('php://output');
        exit();
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
