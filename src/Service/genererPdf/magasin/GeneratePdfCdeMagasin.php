<?php

namespace App\Service\genererPdf\magasin;

use App\Service\genererPdf\GeneratePdf;
use TCPDF;

class GeneratePdfCdeMagasin extends GeneratePdf
{
    public function generate(string $filePath): void
    {
        $pdf = new TCPDF("L");

        $pdf->Output($filePath, 'F');
    }

    public function copyToDOCUWARE(string $fileName, string $numCmde): bool
    {
        $cheminDW = rtrim($this->baseCheminDocuware, '/\\') . '/cmde/' . $fileName;
        $cheminDuFichier = rtrim($this->baseCheminDuFichier, '/\\') . '/cmde/' . $numCmde . '/' . $fileName;

        if (!file_exists($cheminDuFichier)) {
            return false;
        }

        return   $this->copyFile($cheminDuFichier, $cheminDW);
    }
}
