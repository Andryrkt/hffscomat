<?php

namespace App\Controller\Atelier\Dit;

use App\Constants\admin\ApplicationConstant;
use App\Controller\Controller;
use App\Dto\Atelier\Dit\DitSearchDto;
use App\Form\Atelier\Dit\DitSearchType;
use App\Form\Atelier\Dit\DocDansDwType;
use App\Mapper\Atelier\Dit\DitListeMapper;
use App\Mapper\Atelier\Dit\DitSearchMapper;
use App\Model\Atelier\Dit\DitListeModel;
use App\Service\security\SecurityService;
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
     * @Route("/dit-liste", name="dit_liste")
     */
    public function index(Request $request)
    {

        $page = (int) $request->query->get('page', 1);
        if ($page < 1) {
            $page = 1;
        }
        $perPage = 20;

        $criteria = [];

        $allAgenceServices = $this->getSecurityService()->getAllAgenceServices();
        //création et initialisation du formulaire de la recherche
        $form = $this->getFormFactory()->createBuilder(DitSearchType::class, null, [
            'method' => 'GET',
            'allAgenceServices' => $allAgenceServices
        ])->getForm();
        $dto = new DitSearchDto();
        $dtoSearch  = $this->traitementFormualireRecherhce($form, $request, $dto);

        /**  Docs à intégrer dans DW * */
        $formDocDansDW = $this->getFormFactory()->createBuilder(DocDansDwType::class, null, [
            'method' => 'GET',
        ])->getForm();

        // $this->dossierDit($request, $formDocDansDW);
        $formDocDansDW->handleRequest($request);

        if ($formDocDansDW->isSubmitted() && $formDocDansDW->isValid()) {
            if ($formDocDansDW->getData()['docDansDW'] === 'OR') {
                $this->redirectToRoute("dit_insertion_or", ['numDit' => $formDocDansDW->getData()['numeroDit']]);
            } elseif ($formDocDansDW->getData()['docDansDW'] === 'FACTURE') {
                $this->redirectToRoute("dit_insertion_facture", ['numDit' => $formDocDansDW->getData()['numeroDit']]);
            } elseif ($formDocDansDW->getData()['docDansDW'] === 'RI') {
                $this->redirectToRoute("dit_insertion_ri", ['numDit' => $formDocDansDW->getData()['numeroDit']]);
            } elseif ($formDocDansDW->getData()['docDansDW'] === 'DEVIS-VP') {
                $this->redirectToRoute("dit_insertion_devis", ['numDit' => $formDocDansDW->getData()['numeroDit'], 'type' => 'VP']);
            } elseif ($formDocDansDW->getData()['docDansDW'] === 'DEVIS-VA') {
                $this->redirectToRoute("dit_insertion_devis", ['numDit' => $formDocDansDW->getData()['numeroDit'], 'type' => 'VA']);
            } elseif ($formDocDansDW->getData()['docDansDW'] === 'BC') {
                $this->redirectToRoute("dit_ac_bc_soumis", ['numDit' => $formDocDansDW->getData()['numeroDit']]);
            }
        }

        $dataDit = $this->getDataDitEnDto($dtoSearch, $page, $perPage);
        return $this->render('atelier/dit/list.html.twig', [
            'data'          => $dataDit['data'],
            'currentPage'   => $dataDit['currentPage'],
            'totalPages'    => $dataDit['lastPage'],
            'criteria'      => $criteria,
            'resultat'      => $dataDit['totalItems'],
            'statusCounts'  => $dataDit['statusCounts'],
            'form'          => $form->createView(),
            'formDocDansDW' => $formDocDansDW->createView()
        ]);
    }

    /**
     * Recupération des données à afficher
     *
     * @return array
     */
    private function getDataDitEnDto(DitSearchDto $dtoSearch, $page, $perPage): array
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        $dits = $this->ditListeModel->findPaginatedAndFiltered($codeSociete, $dtoSearch, $page, $perPage);
        $ditDto = (new DitListeMapper())->map($dits['data']);

        return [
            'data' => $ditDto,
            'totalItems' => $dits['totalItems'],
            'currentPage' => $dits['currentPage'],
            'lastPage' => $dits['lastPage'],
            'statusCounts' => $dits['statusCounts'],
        ];
    }

    private function traitementFormualireRecherhce(FormInterface $form, Request $request): DitSearchDto
    {
        $form->handleRequest($request);
        $dto = $form->getData() ?? new DitSearchDto();

        if ($form->isSubmitted() && $form->isValid()) {
            //recupères les données du criteria dans une session nommé dit_serch_criteria
            $this->getSessionService()->set('criteria_for_excel_dit_liste', $dto);
        }

        return $dto;
    }
}
