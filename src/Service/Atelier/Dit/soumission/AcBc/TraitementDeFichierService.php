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
        $this->pdfGenerator        = new GenererPdfAcSoumis();
        $this->fileUploaderService = new FileUploaderService($_ENV['BASE_PATH_FICHIER']  . "/dit/$numDit/");
        $this->fusionPdf           = $this->fileUploaderService->getFusionPdf();
    }

    /** 
     * @param AccuseReceptionDto $accuseReceptionDto Dto pour l'accusé de réception dans le PDF
     * @param string             $acFileName         nom du PDF généré pour l'accusé de réception
     */
    public function traitementDeFichier(AccuseReceptionDto $accuseReceptionDto, string $acFileName)
    {
        /** CREATION PDF */
        $this->pdfGenerator->genererPdfAc($accuseReceptionDto);

        $ficherAfusioner = $this->fileUploaderService->insertFileAtPosition($pathFichiers, $pathPageDeGarde, 0);
        $fichierConvertie = $this->ConvertirLesPdf($ficherAfusioner);
        $this->fusionPdf->mergePdfs($fichierConvertie, $pathPageDeGarde);
        $fileName = "";
        $this->pdfGenerator->copyToDWAcSoumis($fileName);
    }

    private function enregistrerPdf(DitFactureSoumisAValidationDto $dto)
    {
        $ditFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();

        $orSoumisFact = $ditFactureSoumisAValidationModel->recupOrSoumisValidation($dto->numeroOr, $dto->numeroFact, $dto->codeSociete);
        $statut = $this->affectationStatutFac($dto, $ditFactureSoumisAValidationModel);
        $montantPdf = $this->montantpdf($dto, $statut, $orSoumisFact);
        $estFactureConformAOr = $this->estFactureConformAOr($dto);

        return $this->genererPdfFacture->GenererPdfFactureSoumisAValidation($dto, $montantPdf, $this->securityService->getDataService()->getUserMail(), $estFactureConformAOr);
    }
}
