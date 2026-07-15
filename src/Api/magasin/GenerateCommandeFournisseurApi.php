<?php

namespace App\Api\magasin;

use App\Controller\Controller;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;



class GenerateCommandeFournisseurApi extends Controller
{
    /**
     * @Route("/api/generer-pdf-cmde-fournisseur", name="api_generate_cmde_fournisseur" ,methods={"GET"})
     */
    public function generatePdfCmdeFournisseur(Request $request): JsonResponse
    {
        $numCde = $request->query->get('numCde');

        $fileUrl = '/Upload/RIpaysage.pdf';

        if (!$numCde) {
            return new JsonResponse([
                'message' => 'Numéro de commande requis',
                'data' => []
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        return new JsonResponse([
            'url' => $fileUrl,
        ], JsonResponse::HTTP_OK);
    }
}
