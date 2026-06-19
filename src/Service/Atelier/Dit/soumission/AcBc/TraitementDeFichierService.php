<?php

namespace App\Service\Atelier\Dit\soumission\AcBc;

use App\Controller\Traits\PdfConversionTrait;
use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Service\fichier\FileUploaderService;
use App\Service\FusionPdf;
use App\Service\genererPdf\dit\AcBc\GenererPdfAcSoumis;

class TraitementDeFichierService
{
    use PdfConversionTrait;

    private FusionPdf $fusionPdf;
    private GenererPdfAcSoumis $pdfGenerator;
    private FileUploaderService $fileUploaderService;

    public function __construct(string $numDit)
    {
        $baseDirDitFiles           = "{$_ENV['BASE_PATH_FICHIER']}/dit/{$numDit}";
        $this->pdfGenerator        = new GenererPdfAcSoumis($baseDirDitFiles);
        $this->fileUploaderService = new FileUploaderService($baseDirDitFiles);
        $this->fusionPdf           = $this->fileUploaderService->getFusionPdf();
    }

    /** 
     * Méthode pour gérer le traitement des fichiers (géneration PDF + fusion PDF + envoi DW)
     * 
     * @param AccuseReceptionDto $accuseReceptionDto Dto pour l'accusé de réception dans le PDF
     */
    public function traitementDeFichier(AccuseReceptionDto $accuseReceptionDto)
    {
        $this->pdfGenerator->genererPdfAc($accuseReceptionDto);

        $ficherAfusioner = $this->fileUploaderService->insertFileAtPosition($pathFichiers, $pathPageDeGarde, 0);
        $fichierConvertie = $this->ConvertirLesPdf($ficherAfusioner);
        $this->fusionPdf->mergePdfs($fichierConvertie, $pathPageDeGarde);


        $this->pdfGenerator->copyToDWAcSoumis($accuseReceptionDto->nomFichierAcSoumis);
    }
}
