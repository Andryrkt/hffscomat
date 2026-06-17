<?php

namespace App\Service\Atelier\Dit\soumission\AcBc;

use App\Controller\Traits\PdfConversionTrait;
use App\Service\fichier\FileUploaderService;
use App\Service\FusionPdf;
use App\Service\genererPdf\dit\AcBc\GenererPdfAcSoumis;

class TraitementDeFichierService
{
    use PdfConversionTrait;

    private FusionPdf $fusionPdf;
    private GenererPdfAcSoumis $pdfGenerator;
    private FileUploaderService $fileUploaderService;

    public function __construct()
    {
        $this->pdfGenerator        = new GenererPdfAcSoumis();
        $this->fileUploaderService = new FileUploaderService($_ENV['BASE_PATH_FICHIER']  . '/dit/ac_bc/');
        $this->fusionPdf           = $this->fileUploaderService->getFusionPdf();
    }
}
