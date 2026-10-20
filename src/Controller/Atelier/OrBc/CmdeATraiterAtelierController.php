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
    private const SESSION_KEY = 'criteria_for_excel_atelier_cmde_a_traiter_liste';
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
        if ($request->query->get('reset') === '1') {
            $this->getSessionService()->remove(self::SESSION_KEY);
            // On redirige vers la page propre (sans paramètres)
            return $this->redirectToRoute('atelier_commande_a_traiter_liste');
        }


        // 2. Récupération du DTO (depuis la session ou nouveau)
        $dtoSearch = $this->getSessionService()->has(self::SESSION_KEY)
            ? $this->getSessionService()->get(self::SESSION_KEY)
            : new CmdeATraitrerAtelierSearchDto();
        // 3. Création du formulaire
        $form = $this->getFormFactory()->createBuilder(CmdeATraiterAtelierSearchType::class, $dtoSearch, [
            'method' => 'GET',
        ])->getForm();

        // 4. Traitement de la soumission du formulaire
        $dtoSearch = $this->traitementFormulaireRecherche($form, $request, $dtoSearch);

        // 5. Récupération des données
        $dataCmde = $this->getDataEnDto($dtoSearch, $request);

        return $this->render('atelier/orBc/list.html.twig', [
            'data'        => $dataCmde['data'],
            'currentPage' => $dataCmde['currentPage'],
            'totalPages'  => $dataCmde['lastPage'],
            'resultat'    => $dataCmde['totalItems'],
            'criteria'    => [],
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

    private function traitementFormulaireRecherche(FormInterface $form, Request $request, CmdeATraitrerAtelierSearchDto $dtoSearch): CmdeATraitrerAtelierSearchDto
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoSearch = $form->getData();
            // Sauvegarde des critères en session
            $this->getSessionService()->set(self::SESSION_KEY, $dtoSearch);
        }

        return $dtoSearch;
    }
}
