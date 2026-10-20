<?php

namespace App\Controller\Atelier\OrBc;

use App\Controller\Controller;
use App\Dto\Atelier\OrBc\CmdeATraitrerAtelierSearchDto;
use App\Form\Atelier\Dit\OrBc\CmdeATraiterAtelierSearchType;
use App\Mapper\Atelier\OrBc\CmdeATraiterAtelierListeMapper;
use App\Model\Atelier\OrBc\CmdeATraiterAtelierModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/atelier/or-bc-negoce")
 */
class CmdeATraiterAtelierController extends Controller
{
    private CmdeATraiterAtelierModel $cmdeAtraiterAtelierModel;

    public function __construct(CmdeATraiterAtelierModel $cmdeAtraiterAtelierModel)
    {
        parent::__construct();
        $this->cmdeAtraiterAtelierModel = $cmdeAtraiterAtelierModel;
    }

    /**
     * @Route("/commande-a-traiter-liste", name="atelier_commande_a_traiter_liste")
     */
    public function index(Request $request)
    {

        $dtoSearch =  $this->getSessionService()->has('criteria_for_excel_atelier_cmde_a_traiter_liste') ? $this->getSessionService()->get('criteria_for_excel_atelier_cmde_a_traiter_liste') : new CmdeATraitrerAtelierSearchDto();
        //création et initialisation du formulaire de la recherche
        $form = $this->getFormFactory()->createBuilder(CmdeATraiterAtelierSearchType::class, $dtoSearch, [
            'method' => 'GET',
        ])->getForm();

        $dtoSearch  = $this->traitementFormulaireRecherhce($form, $request, $dtoSearch);
        $dataCmde = $this->getDataEnDto($dtoSearch, $request);
        $criteria = [];
        return $this->render('atelier/orBc/list.html.twig', [
            'data'        => $dataCmde['data'],
            'currentPage' => $dataCmde['currentPage'],
            'totalPages'  => $dataCmde['lastPage'],
            'resultat'    => $dataCmde['totalItems'],
            'criteria'    => $criteria,
            'form'        => $form->createView(),
        ]);
    }

    /**
     * Recupération des données à afficher
     *
     * @return array
     */
    private function getDataEnDto(CmdeATraitrerAtelierSearchDto $dtoSearch, Request $request): array
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        $page = (int) $request->query->get('page', 1);
        if ($page < 1) $page = 1;
        $perPage = 50;

        $result = $this->cmdeAtraiterAtelierModel->findPaginatedAndFiltered($codeSociete, $dtoSearch, $page, $perPage);
        $cmdListeAtraiterDto = (new CmdeATraiterAtelierListeMapper())
            ->map($result['data'] ?? []);
        return [
            'data' => $cmdListeAtraiterDto,
            'totalItems'  => $result['totalItems']  ?? 0,
            'currentPage' => $result['currentPage'] ?? $page,
            'lastPage'    => $result['lastPage']    ?? 1,
        ];
    }

    private function traitementFormulaireRecherhce(FormInterface $form, Request $request, CmdeATraitrerAtelierSearchDto $dtoSearch): CmdeATraitrerAtelierSearchDto
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoSearch = $form->getData();

            //recupères les données du criteria dans une session nommé dit_serch_criteria
            $this->getSessionService()->set('criteria_for_excel_atelier_cmde_a_traiter_liste', $dtoSearch);
        }

        return $dtoSearch;
    }
}
