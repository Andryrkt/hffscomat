<?php

namespace App\Factory\magasin\Commande\Soumission;

use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Dto\Magasin\Commande\Soumission\CdeFrnSoumisAValidationDTO;

final class CdeFrnSoumisAValidationFactory
{
    /** 
     * Fonction pour hydrater un DTO de cde frn soumis à validation
     * 
     * @param CommandeSoumissionDTO $commandeSoumissionDTO DTO de cde frn
     * 
     * @return CdeFrnSoumisAValidationDTO DTO de cde frn soumis à validation
     */
    public function hydrate(CommandeSoumissionDTO $commandeSoumissionDTO): CdeFrnSoumisAValidationDTO
    {
        $numCde = $commandeSoumissionDTO->numeroCommande;
        $dto = new CdeFrnSoumisAValidationDTO();

        $dto->numCde     = $numCde;
        $dto->codeFrn    = $commandeSoumissionDTO->numFrn;
        $dto->libelleFrn = $commandeSoumissionDTO->nomFrn;

        $urlsPDF          = $this->getUrlPDF($numCde);
        $dto->urlPDFCourt = $urlsPDF['urlPDFCourt'];
        $dto->urlPDFLong  = $urlsPDF['urlPDFLong'];

        return $dto;
    }

    /** 
     * Fonction pour obtenir l'URL du PDF à générer
     * 
     * @param string $numCde numéro du cde frn
     * 
     * @return array{urlPDFCourt:string,urlPDFLong:string}
     */
    private function getUrlPDF(string $numCde): array
    {
        $basePath = rtrim($_ENV['BASE_PATH_FICHIER'], '/\\');
        $filePath = "magasin/commandes fournisseurs/$numCde/$numCde.pdf";
        $urlPDFLong = "$basePath/$filePath";
        $dirPath  = dirname($urlPDFLong);

        if (!is_dir($dirPath)) mkdir($dirPath, 0777, true);

        if (file_exists($urlPDFLong)) unlink($urlPDFLong);

        return [
            'urlPDFCourt' => rtrim($_ENV['BASE_PATH_FICHIER_COURT'], '/\\') . "/$filePath",
            'urlPDFLong'  => $urlPDFLong
        ];
    }
}
