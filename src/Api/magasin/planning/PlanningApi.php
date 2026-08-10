<?php

namespace App\Api\magasin\planning;

use App\Controller\Controller;
use App\Model\magasin\planning\PlanningMagasinModel;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

class PlanningApi extends Controller
{
    private PlanningMagasinModel $planningMagasinModel;

    public function __construct()
    {
        parent::__construct();
        $this->planningMagasinModel = new PlanningMagasinModel();
    }

    /**
     * @Route("/api/magasin-planning-liste-fournisseur", name="api_magasin_planning_liste_fournisseur")
     */
    public function listeFournisseur(): JsonResponse
    {
        try {
            $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

            $fournisseurs = $this->planningMagasinModel->recupListeFournissseur($codeSociete);

            if (empty($fournisseurs)) {
                return new JsonResponse([
                    'message' => 'Aucune donnée trouvée',
                    'data'    => []
                ], JsonResponse::HTTP_OK);
            }

            return new JsonResponse([
                'message' => 'Données chargées avec succès',
                'data'    => $fournisseurs
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors du chargement des données: ' . $e->getMessage(),
                'data'    => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @Route("/api/magasin-planning-ligne-commande/{numCde}", name="api_magasin_planning_ligne_commande")
     */
    public function ligneCommande(string $numCde): JsonResponse
    {
        // Données mockées, en attendant le branchement sur le modèle
        $lignes = [
            [
                'type'          => 'BC',
                'num_bc_negoce' => 'NEG-0001',
                'num_ligne'     => 1,
                'client'        => 'CLIENT A',
                'code_cst'      => 'CONST1',
                'ref'           => 'REF-0001',
                'designation'   => 'Désignation article 1',
                'qte_dem'       => 10,
                'qte_rest'      => 4,
                'qte_a_livrer'  => 4,
                'qte_livree'    => 6,
                'qte_facturee'  => 6,
                'statut'        => 'EN COURS',
                'eta_magasin'   => '2026-08-10',
            ],
            [
                'type'          => 'BC',
                'num_bc_negoce' => 'NEG-0002',
                'num_ligne'     => 2,
                'client'        => 'CLIENT B',
                'code_cst'      => 'CONST2',
                'ref'           => 'REF-0002',
                'designation'   => 'Désignation article 2',
                'qte_dem'       => 5,
                'qte_rest'      => 0,
                'qte_a_livrer'  => 0,
                'qte_livree'    => 5,
                'qte_facturee'  => 5,
                'statut'        => 'LIVREE',
                'eta_magasin'   => '2026-07-28',
            ],
        ];

        return new JsonResponse([
            'message' => 'Données chargées avec succès',
            'data'    => $lignes
        ], JsonResponse::HTTP_OK);
    }
}
