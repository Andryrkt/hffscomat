<?php

namespace App\Api\magasin;

use App\Controller\Controller;
use App\Service\genererPdf\magasin\GeneratePdfCdeMagasin;
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

        if (!$numCde) {
            return new JsonResponse([
                'message' => 'Numéro de commande requis'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }
        $fileName = "Commande_Fournisseur_{$numCde}.pdf";

        $basePath = rtrim($_ENV['BASE_PATH_FICHIER'], '/\\');
        $basePathCourt = rtrim($_ENV['BASE_PATH_FICHIER_COURT'], '/\\');
        $dirPath = $basePath . "/cmde/" . $numCde;

        if (!is_dir($dirPath)) {
            mkdir($dirPath, 0777, true);
        }

        $filePath = $dirPath . "/" . $fileName;

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        $generatePdfCdeMagasin = new GeneratePdfCdeMagasin();
        $generatePdfCdeMagasin->generate($filePath);

        $url = rtrim($basePathCourt, '/\\')
            . "/cmde/"
            . $numCde
            . "/"
            . $fileName;

        return new JsonResponse([
            'url' => $url,
        ]);
    }
}
