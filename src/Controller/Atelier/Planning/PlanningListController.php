<?php

namespace App\Controller\Atelier\Planning;

use App\Controller\Controller;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Entity\planning\PlanningSearch;
use App\Form\Atelier\Planning\PlanningSearchType;
use App\Model\Atelier\Planning\PlanningMaterielModel;
use App\Model\Atelier\Planning\PlanningModel;
use App\Service\Atelier\Planning\PlanningService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier")
 */
class PlanningListController extends Controller
{

    private PlanningModel $planningModel;
    private PlanningMaterielModel $planningMaterielModel;
    private PlanningService $planningService;
    private PlanningSearchDto $searchDto;

    public function __construct()
    {
        parent::__construct();
        $this->planningModel = new PlanningModel();
        $this->planningMaterielModel = new PlanningMaterielModel();
        $this->planningService = new PlanningService();
        $this->searchDto = new PlanningSearchDto();
        $this->searchDto->annee = date('Y');
        $this->searchDto->facture = 'ENCOURS';
        $this->searchDto->plan = 'PLANIFIE';
        $this->searchDto->interneExterne = 'TOUS';
        $this->searchDto->typeLigne = 'TOUTES';
        $this->searchDto->months = 3;
    }

    /**
     * @Route("/planning-detaille", name="liste_planning")
     */
    public function listPlanning(Request $request)
    {

        $dto = $this->getSessionService()->get('planning_search_criteria');
        if (!$dto)
            $dto = $this->searchDto;
        $form = $this->getFormFactory()->createBuilder(
            PlanningSearchType::class,
            null,
            ['method' => 'GET']
        )->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid())
            $dto = $form->getData();

        $data = ['data' => []];
        $count = [];
        if ($request->query->get('action') !== 'oui') {
            ['num_ors' => $numOrs] = $this->planningModel->getNumeroOrValider($dto);
            ['num_ors' => $numOrSoumis] = $this->planningModel->getOrsSoumis();
            ['num_or_itvs' => $numOrItvBack] = $this->planningModel->getBackOrderPlanning($numOrs, $numOrSoumis, $dto);
            $result = $this->planningMaterielModel->getMaterielList($numOrs, $numOrSoumis, $numOrItvBack, $dto);
            $data = $this->planningService->getDetailledDataList($result, $numOrItvBack);
            $count = $this->planningMaterielModel->getMaterielListCount($numOrs, $numOrSoumis, $numOrItvBack, $dto);
            $this->getSessionService()->set('data_planning_detail_excel', $data['data_excel']);
        }

        return $this->render('atelier/planning/listePlanning.html.twig', [
            'form' => $form->createView(),
            'criteria' => $dto->toArray(),
            'data' => $data['data'],
            'count' => $count,
        ]);
    }

    /**
     * @Route("/export_excel_liste_planning", name= "export_liste_planning")
     */
    public function exportExcel()
    {
        $data = $this->getSessionService()->get('data_planning_detail_excel');
        $header = [
            'agenceServiceTravaux' => 'Agence - Service',
            'Marque' => 'Marque',
            'Modele' => 'Modèle',
            'Id' => 'ID',
            'N_Serie' => 'N° Série',
            'parc' => 'Parc',
            'casier' => 'Casier',
            'commentaire' => 'Intitulé',
            'numor_itv' => 'Num OR - ITV',
            'dateplanning' => 'Date Planning',
            'cst' => 'CST',
            'ref' => 'Référence',
            'desi' => 'Désignation',
            'qteres_or' => 'Qte Res OR',
            'qteall_or' => 'Qte All OR',
            'qtereliquat' => 'Qte Reliquat',
            'qteliv_or' => 'Qte Livrée OR',
            'statutOR' => 'Statut OR',
            'datestatutOR' => 'Date statut OR',
            'ctr_marque' => 'Ctr Marque ',
            'numerocmd' => 'Numéro CMD',
            'statut_ctrmq' => 'Statut CTRMQ',
            'numcis' => 'Numéro CIS',
            'qteORlig_cis' => 'Qte OR CIS',
            'qtealllig_cis' => 'Qte All CIS',
            'qterlqlig_cis' => 'Qte Reliquat CIS',
            'qtelivlig_cis' => 'Qte Livrée CIS',
            'statutCis' => 'Statut CIS',
            'datestatutCis' => 'Date Statut CIS',
            'Eta_ivato' => 'État Ivato',
            'Eta_magasin' => 'État Magasin',
            'message' => 'Message',
            'ord' => 'Commande Envoyé',
            'status_b' => 'Statut'
        ];
        array_unshift($data, $header);
        $this->exporterDonneesExcel($data);
    }

    private function exporterDonneesExcel($data)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Ajout des données
        $rowIndex = 1;
        foreach ($data as $row) {
            $sheet->fromArray([$row], null, "A$rowIndex");
            $rowIndex++;
        }

        // Téléchargement du fichier
        $writer = new Xlsx($spreadsheet);
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="export.xlsx"');
        $writer->save('php://output');
        exit();
    }

}