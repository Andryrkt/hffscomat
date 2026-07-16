<?php

namespace App\Api\magasin;

use App\Controller\Controller;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Model\magasin\CommANDe\Soumission\CdeSoumissionModel;
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
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        $userMail = $this->getUserMail();


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


        try {
            $cdeSoumissionModel = new CdeSoumissionModel();

            $commandeSoumissionDto = $cdeSoumissionModel->findInfoCommande(
                $numCde,
                $userMail,
                "1",
                $codeSociete
            );

            if ($commandeSoumissionDto === null) {
                return new JsonResponse([
                    'url' => null,
                    'message' => "Aucune information trouvée pour la commande {$numCde}."
                ], JsonResponse::HTTP_NOT_FOUND);
            }

            $generatePdfCdeMagasin = new GeneratePdfCdeMagasin();
            $generatePdfCdeMagasin->generate(
                $commandeSoumissionDto,
                $filePath
            );

            $url = $basePathCourt
                . "/cmde/"
                . $numCde
                . "/"
                . $fileName;

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
