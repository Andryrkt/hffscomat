<?php

namespace App\Api\magasin;

use App\Controller\Controller;
use App\Model\magasin\CommANDe\Soumission\CdeSoumissionModel;
use App\Service\genererPdf\magasin\GeneratePdfCdeMagasin;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class GenerateCommandeFournisseurApi extends Controller
{
    /**
     * @Route("/api/cmde-fournisseur/{numCde}/generate-pdf", name="api_generate_cmde_fournisseur", methods={"GET"})
     */
    public function generatePdfCmdeFournisseur(string $numCde): JsonResponse
    {
        // 1. Validation basique de l'input
        if (empty($numCde) || !preg_match('/^[0-9]+$/', $numCde)) {
            return new JsonResponse([
                'success' => false,
                'error'   => 'Numéro de document invalide.'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        $basePath = rtrim($_ENV['BASE_PATH_FICHIER'], '/\\');
        $filePath = "magasin/commandes fournisseurs/$numCde/$numCde.pdf";
        $dirPath = dirname("$basePath/$filePath");

        if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);

        if (file_exists($filePath)) {
            // TODO que faire ?
            unlink($filePath);
        }

        try {
            $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
            $userMail = $this->getUserMail();
            $commandeSoumissionDto = (new CdeSoumissionModel())->findInfoCommande($numCde, $userMail, $_ENV["SUC_NEG"], $codeSociete);

            if ($commandeSoumissionDto === null) {
                return new JsonResponse([
                    'url' => null,
                    'message' => "Aucune information trouvée pour la commande \"{$numCde}\"."
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            (new GeneratePdfCdeMagasin())->generate($commandeSoumissionDto, "$basePath/$filePath");

            $basePathCourt = rtrim($_ENV['BASE_PATH_FICHIER_COURT'], '/\\');
            $url = "$basePathCourt/$filePath";

            return new JsonResponse([
                'url' => $url,
                'message' => "PDF généré avec succès."
            ]);
        } catch (\Throwable $e) {
            return new JsonResponse([
                'url' => null,
                'message' => "Erreur lors de la génération du PDF : " . $e->getMessage()
            ], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
