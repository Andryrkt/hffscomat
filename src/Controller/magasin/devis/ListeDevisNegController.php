<?php

namespace App\Controller\magasin\devis;

use App\Constants\admin\ApplicationConstant;
use App\Constants\Magasin\Devis\TypeSoumissionConstant;
use App\Controller\Controller;
use App\Dto\Magasin\Devis\DevisSearchDto;
use App\Entity\magasin\devis\DevisMagasin;
use App\Form\magasin\devis\DevisNegSearchType;
use App\Mapper\Magasin\Devis\DevisNegMapper;
use App\Model\magasin\devis\DevisNegModel;
use App\Service\security\SecurityService;
use App\Service\TableauEnStringService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/dematerialisation")
 */
class ListeDevisNegController extends Controller
{

    private DevisNegModel $listeDevisNegModel;
    private DevisNegMapper $devisNegMapper;

    public function __construct()
    {
        parent::__construct();
        $this->listeDevisNegModel = new DevisNegModel();
        $this->devisNegMapper = new DevisNegMapper();
    }

    /**
     * @Route("/liste-devis-neg", name="liste_devis_neg")
     */
    public function listeDevisNeg(Request $request)
    {
        // Traitement du formulaire de recherche
        [$form, $criteria] = $this->creationEtTraitementformulaireDeRecherche($request);


        $response = $this->render('magasin/devis/liste_devis_neg.html.twig', [
            'form' => $form->createView(),
            'urlBases' => [
                'verificationPrix' => $this->getUrlGenerator()->generate('devis_neg_soumission_verification_prix', ['typeSoumission' => TypeSoumissionConstant::VERIFICATION_PRIX, 'numeroDevis' => 'PLACEHOLDER']),
                'soumissionBC'     => $this->getUrlGenerator()->generate('bc_neg_soumission', ['numeroDevis' => 'PLACEHOLDER']),
                'pointageDevis'    => $this->getUrlGenerator()->generate('pointage_envoyer_au_client', ['numeroDevis' => 'PLACEHOLDER']),
            ]
        ]);

        return $response;
    }

    /**
     * @Route("/api/devis-neg/data", name="api_devis_neg_data")
     */
    public function getApiData(Request $request)
    {
        try {
            $page = $request->query->getInt('page', 1);
            $limit = $request->query->getInt('limit', 500);

            [, $criteria] = $this->creationEtTraitementformulaireDeRecherche($request);

            $devisNeg = $this->getDataDevisNegEnDto($page, $limit, $criteria);

            return new JsonResponse([
                'success' => true,
                'data' => $devisNeg,
            ]);
        } catch (\Throwable $e) {
            // Fallback si le logger n'est pas dispo
            error_log("API Devis Neg Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

            return new JsonResponse([
                'success' => false,
                'message' => 'Une erreur est survenue lors de la récupération des données.',
                'error' => $e->getMessage(),
                'output_detected' => !empty($outputParasite) ? 'Yes' : 'No'
            ], 500);
        }
    }

    private function creationEtTraitementformulaireDeRecherche(Request $request): array
    {
        $form = $this->getFormFactory()->createBuilder(DevisNegSearchType::class, null, [
            'em' => $this->getEntityManager(),
            'method' => 'GET'
        ])->getForm();

        $form->handleRequest($request);
        $criteria = [];
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            // Stockage des critères de recherche dans la session pour les réutiliser lors de l'export Excel
            $this->getSessionService()->set('criteria_for_excel_liste_devis_neg', $criteria);
        }
        return [$form, $criteria];
    }

    private function getDataDevisNegEnDto(int $page = 1, int $limit = 100, $criteria = [])
    {
        if ($criteria instanceof DevisSearchDto) {
            $criteria = (array) $criteria;
        }

        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser() ?? 'HF';

        // Code Agence par défaut
        $codeAgenceDefaut = $this->getSecurityService()->getCodeAgenceUser();

        // code agence autoriser
        $codeAgenceAutoriserString = TableauEnStringService::orEnString(array_column($this->getSecurityService()->getAgenceServices(ApplicationConstant::CODE_DVM), 'agence_code'));

        $multiSuccursale = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE, 'liste_devis_neg');


        $urlGenerator = function ($dto) {
            $dto->pointagedevis = in_array($dto->statutDw, [DevisMagasin::STATUT_PRIX_VALIDER_TANA, DevisMagasin::STATUT_PRIX_MODIFIER_TANA, DevisMagasin::STATUT_VALIDE_AGENCE]);
            $dto->relanceClient = true;

            return [];
        };

        $devisNeg = $this->listeDevisNegModel->getDevisNeg($criteria, $codeAgenceAutoriserString, $multiSuccursale, $codeAgenceDefaut, $codeSociete, $page, $limit);
        $devisNeg = $this->devisNegMapper->map($devisNeg, $urlGenerator);

        return $devisNeg;
    }
}
