<?php

namespace App\Controller\Atelier\CVA;


use App\Controller\Controller;
use App\Dto\Atelier\CVA\OrCsaATraiterSearchDto;
use App\Form\Atelier\Dit\DocDansDwType;
use App\Mapper\Atelier\CVA\OrCsaATraiterListeMapper;
use App\Model\Atelier\CVA\OrCsaATraiterListeModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/cva/")
 */
class OrCsaATraiterListeController extends Controller
{
    private OrCsaATraiterListeModel $orCsaATraiterListeModel;

    public function __construct(OrCsaATraiterListeModel $orCsaATraiterListeModel)
    {
        parent::__construct();
        $this->orCsaATraiterListeModel = $orCsaATraiterListeModel;
    }
    /**
     * @Route("/or-csa-a-traiter-liste", name="or_csa_a_traiter_liste")
     */
    public function index(Request $request)
    {
        $allAgenceServices = $this->getSecurityService()->getAllAgenceServices();

        $dtoSearch =  $this->getSessionService()->has('criteria_for_excel_dit_liste') ? $this->getSessionService()->get('criteria_for_excel_dit_liste') : new OrCsaATraiterSearchDto();

        //création et initialisation du formulaire de la recherche
        $form = $this->getFormFactory()->createBuilder(OrCsaATraiterSearchDto::class, $dtoSearch, [
            'method' => 'GET',
            'allAgenceServices' => $allAgenceServices
        ])->getForm();
        $dtoSearch  = $this->traitementFormualireRecherhce($form, $request, $dtoSearch);

        /**  Docs à intégrer dans DW * */
        $formDocDansDW = $this->getFormFactory()->createBuilder(DocDansDwType::class, null, [
            'method' => 'GET',
        ])->getForm();



        $dataOrCsaATraiter = $this->getDataOrCsaATraiteEnDto($dtoSearch, $request);

        $criteria = [];
        return $this->render('atelier/cva/list.html.twig', [
            'data'          => $dataOrCsaATraiter['data'],
            'currentPage'   => $dataOrCsaATraiter['currentPage'],
            'totalPages'    => $dataOrCsaATraiter['lastPage'],
            'criteria'      => $criteria,
            'resultat'      => $dataOrCsaATraiter['totalItems'],
            'statusCounts'  => $dataOrCsaATraiter['statusCounts'],
            'form'          => $form->createView(),
            'formDocDansDW' => $formDocDansDW->createView()
        ]);
    }

    private function traitementFormualireRecherhce(FormInterface $form, Request $request, OrCsaATraiterSearchDto $dtoSearch): OrCsaATraiterSearchDto
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoSearch = $form->getData();

            //recupères les données du criteria dans une session nommé dit_serch_criteria
            $this->getSessionService()->set('criteria_for_excel_or_csa_a_traiter_liste', $dtoSearch);
        }

        return $dtoSearch;
    }

    private function getDataOrCsaATraiteEnDto(OrCsaATraiterSearchDto $dtoSearch, Request $request): array
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        $page = (int) $request->query->get('page', 1);
        if ($page < 1) $page = 1;
        $perPage = 20;

        $dits = $this->orCsaATraiterListeModel->findPaginatedAndFiltered($codeSociete, $dtoSearch, $page, $perPage);
        $ditDto = (new OrCsaATraiterListeMapper())->map($dits['data']);

        return [
            'data' => $ditDto,
            'totalItems' => $dits['totalItems'],
            'currentPage' => $dits['currentPage'],
            'lastPage' => $dits['lastPage'],
            'statusCounts' => $dits['statusCounts'],
        ];
    }
}
