<?php

namespace App\Service\genererPdf\dit\ri;

use App\Service\genererPdf\GeneratePdf;

class GenererPdfRiSoumisAValidataion extends GeneratePdf
{
    /**
     * @param string $fileName nom du fichier RI
     * @param string $numDit   numéro de la DIT : le RI est enregistré dans dit/{numDit}/ (Ri\TraitementFichierService)
     */
    public function copyToDw(string $fileName, string $numDit): bool
    {
        $cheminFichierDistant = $this->baseCheminDocuware . "RAPPORT INTERVENTION/$fileName";
        $cheminDestinationLocal = $this->baseCheminDuFichier . "dit/$numDit/$fileName";
        return $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }
}
