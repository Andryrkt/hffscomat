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

    private string $baseDirDitFiles;
    private FusionPdf $fusionPdf;
    private GenererPdfAcSoumis $pdfGenerator;
    private FileUploaderService $fileUploaderService;

    public function __construct(string $numDit)
    {
        $this->baseDirDitFiles     = "{$_ENV['BASE_PATH_FICHIER']}/dit/{$numDit}";
        $this->pdfGenerator        = new GenererPdfAcSoumis($this->baseDirDitFiles);
        $this->fileUploaderService = new FileUploaderService($this->baseDirDitFiles);
        $this->fusionPdf           = $this->fileUploaderService->getFusionPdf();
    }

    /** 
     * Méthode pour gérer le traitement des fichiers (géneration PDF + fusion PDF + envoi DW)
     * 
     * @param AccuseReceptionDto $accuseReceptionDto Dto pour l'accusé de réception dans le PDF
     */
    public function traitementDeFichier(AccuseReceptionDto $accuseReceptionDto)
    {
        // Géneration PDF
        $this->pdfGenerator->genererPdfAc($accuseReceptionDto);

        // Nom de fichier avec chemin de dossier
        $nomFichierAc = "{$this->baseDirDitFiles}/{$accuseReceptionDto->nomFichierAcSoumis}";

        // Upload + Réarrangement des fichiers PDF
        $uploadedFilePath = $this->fileUploaderService->uploadFileSansName($accuseReceptionDto->pieceJoint01, $accuseReceptionDto->nomFichierAcSoumis);
        $fichiersAfusioner = $this->fileUploaderService->insertFileAtPosition([$uploadedFilePath], $nomFichierAc, 1);

        // Conversion PDF
        $fichierConvertis = $this->ConvertirLesPdf($fichiersAfusioner);

        // Fusion PDF
        $this->fusionPdf->mergePdfs($fichierConvertis, $nomFichierAc);

        // Envoi dans DW
        $this->pdfGenerator->copyToDWAcSoumis($accuseReceptionDto->nomFichierAcSoumis);
    }
}
