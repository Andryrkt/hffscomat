<?php

namespace App\Controller\Magasin\OrBc;

use App\Controller\Controller;
use App\Dto\Magasin\OrBc\CmdeATraitrerMagasinSearchDto;
use App\Form\magasin\OrBc\CmdeATraiterMagasinSearchType;
use App\Mapper\Magasin\OrBc\CmdeATraiterMagasinListeMapper;
use App\Model\magasin\OrBc\CmdeATraiterMagasinModel;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/magasin/or-bc-negoce")
 */
class CmdeATraiterMagasinController extends Controller
{
    private const SESSION_KEY = 'criteria_for_excel_Magasin_cmde_a_traiter_liste';
    private CmdeATraiterMagasinModel $cmdeAtraiterMagasinModel;

    public function __construct(CmdeATraiterMagasinModel $cmdeAtraiterMagasinModel)
    {
        parent::__construct();
        $this->cmdeAtraiterMagasinModel = $cmdeAtraiterMagasinModel;
    }


    /**
     * @Route("/commande-a-traiter-liste", name="magasin_commande_a_traiter_liste")
     */
    public function index(Request $request)
    {
        if ($request->query->get('reset') === '1') {
            $this->getSessionService()->remove(self::SESSION_KEY);
            // On redirige vers la page propre (sans paramètres)
            return $this->redirectToRoute('magasin_commande_a_traiter_liste');
        }


        // 2. Récupération du DTO (depuis la session ou nouveau)
        $dtoSearch = $this->getSessionService()->has(self::SESSION_KEY)
            ? $this->getSessionService()->get(self::SESSION_KEY)
            : new CmdeATraitrerMagasinSearchDto();
        // 3. Création du formulaire
        $form = $this->getFormFactory()->createBuilder(CmdeATraiterMagasinSearchType::class, $dtoSearch, [
            'method' => 'GET',
        ])->getForm();

        // 4. Traitement de la soumission du formulaire
        $dtoSearch = $this->traitementFormulaireRecherche($form, $request, $dtoSearch);

        // 5. Récupération des données
        $dataCmde = $this->getDataEnDto($dtoSearch, $request);

        return $this->render('Magasin/orBc/list.html.twig', [
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
    private function getDataEnDto(CmdeATraitrerMagasinSearchDto $dtoSearch, Request $request): array
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        $page = (int) $request->query->get('page', 1);
        if ($page < 1) $page = 1;
        $perPage = 50;

        $result = $this->cmdeAtraiterMagasinModel->findPaginatedAndFiltered($codeSociete, $dtoSearch, $page, $perPage);
        $cmdListeAtraiterDto = (new CmdeATraiterMagasinListeMapper())
            ->map($result['data'] ?? []);
        return [
            'data' => $cmdListeAtraiterDto,
            'totalItems'  => $result['totalItems']  ?? 0,
            'currentPage' => $result['currentPage'] ?? $page,
            'lastPage'    => $result['lastPage']    ?? 1,
        ];
    }

    private function traitementFormulaireRecherche(FormInterface $form, Request $request, CmdeATraitrerMagasinSearchDto $dtoSearch): CmdeATraitrerMagasinSearchDto
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
