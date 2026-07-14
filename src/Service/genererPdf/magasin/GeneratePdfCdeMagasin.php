<?php

namespace App\Service\genererPdf\magasin;

use TCPDF;

class GeneratePdfCdeMagasin
{
    public function generate(string $filePath): void
    {
        $pdf = new TCPDF("L");

        $pdf->Output($filePath, 'I');
    }
}
