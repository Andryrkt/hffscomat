<?php

namespace App\Service\genererPdf\dit\ri;

use App\Service\genererPdf\GeneratePdf;

class GenererPdfRiSoumisAValidataion extends GeneratePdf
{
    public function copyToDw(string $fileName)
    {
        $cheminFichierDistant = $this->baseCheminDocuware . "RAPPORT INTERVENTION/$fileName";
        $cheminDestinationLocal = $this->baseCheminDuFichier . "vri/$fileName";
        $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }
}
