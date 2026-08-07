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
}
