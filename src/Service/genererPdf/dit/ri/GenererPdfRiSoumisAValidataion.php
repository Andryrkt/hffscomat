<?php

namespace App\Service\genererPdf\dit\ri;

use App\Service\genererPdf\GeneratePdf;

class GenererPdfRiSoumisAValidataion extends GeneratePdf
{
    public function copyToDw(string $fileName)
    {
        $cheminFichierDistant = $this->baseCheminDocuware . 'RAPPORT_INTERVENTION/' . $fileName;
        $cheminDestinationLocal = $this->baseCheminDuFichier . 'vri/' . $fileName; // avec tiret 6
        $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }
}