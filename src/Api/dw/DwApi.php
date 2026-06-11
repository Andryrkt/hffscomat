<?php

namespace App\Api\dw;

use App\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Atelier\DossierDit\DossierDitService;
use Symfony\Component\HttpFoundation\JsonResponse;

class DwApi extends Controller
{
    private DossierDitService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new DossierDitService();
    }

    /**
     * @Route("/api/dw-fetch/{numDit}", name="api_fetch_dw")
     */
    public function dwfetch(string $numDit): JsonResponse
    {
        try {
            $data = $this->service->getDwDocs($numDit);

            if (empty($data)) {
                return new JsonResponse([
                    'message' => 'Aucune donnée trouvée',
                    'data'    => []
                ], JsonResponse::HTTP_OK);
            }

            return new JsonResponse([
                'message' => 'Données chargées avec succès',
                'data'    => $data
            ], JsonResponse::HTTP_OK);
        } catch (\Exception $e) {
            return new JsonResponse([
                'message' => 'Erreur lors du chargement des données: ' . $e->getMessage(),
                'data'    => []
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
