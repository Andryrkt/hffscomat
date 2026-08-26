<?php

namespace App\Service\genererPdf\magasin\devis;

use App\Dto\Magasin\Devis\Soumission\SoumissionDto;
use App\Service\genererPdf\dit\ors\Tables\TableauMargeReferenceTableTrait;
use App\Service\genererPdf\dit\ors\Tables\TableauMargeTableTrait;
use App\Service\genererPdf\GeneratePdf;
use App\Service\genererPdf\HeaderPdf;
use App\Service\genererPdf\PdfTableGeneratorFlexible;
use App\Service\TableauEnStringService;

class GeneratePdfDeviMagasinVp extends GeneratePdf
{
    use TableauMargeTableTrait;
    use TableauMargeReferenceTableTrait;

    /**
     * copie la page de garde fusionner du devis magasin dans docuware
     * pour verification prix
     * @param string $fileName
     * @param string $numeroDevis
     * @return void
     */
    public function copyToDWDevisVpMagasin(string $fileName, string $numeroDevis): void
    {
        $cheminFichierDistant = $this->baseCheminDocuware . 'VERIFICATION_PRIX_MAGASIN/' . $fileName;
        $cheminDestinationLocal = $this->baseCheminDuFichier . 'magasin/devis/' . $numeroDevis . '/' . $fileName;
        $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    public function genererPdf(
        SoumissionDto $dto,
        string $filePath,
        array $tableauMarges,
        array $tableauMargeReference
    ) {
        $pdf = new HeaderPdf(null);
        // $font1 = "pdfatimesbi";
        $font2 = "helvetica";

        $tableGenerator = new PdfTableGeneratorFlexible();

        $tableGenerator->setOptions([
            'table_attributes' => 'border="0" cellpadding="0" cellspacing="0" align="center" style="font-size: 8px;"',
            'header_row_style' => 'background-color: #D3D3D3;',
            'footer_row_style' => 'background-color: #D3D3D3;'
        ]);

        $pdf->AddPage();
        $pdf->SetFont($font2, 'B', 12);
        $pdf->Cell(30, 10, 'Vendor : ', 0, 0, 'L');
        $pdf->SetFont($font2, '', 10);
        $pdf->Cell(0, 10, $dto->userName . ' - ' . $dto->userMail, 0, 1, 'L');

        $pdf->Ln(5, true);

        $pdf->SetFont($font2, 'B', 12);
        $pdf->Cell(63, 10, 'Opération à faire sur le devis : ', 0, 0, 'L');
        $pdf->SetFont($font2, '', 10);
        $y = $pdf->GetY();
        $pdf->setAbsY($y + 3);
        $pdf->MultiCell(0, 10, TableauEnStringService::orEnString($dto->tacheValidateur), 0, 'L');

        $pdf->Ln(5, true);
        //==========================================================================================================
        //Titre: Tableaux de marge (CAT, MFN, Autres)
        $this->renderTableauxMarge($pdf, $tableGenerator, $tableauMarges);

        //==========================================================================================================
        //Titre: Tableaux de marge avec reference (CAT, MFN, Autres)
        $this->renderTableauxMargeReference($pdf, $tableGenerator, $tableauMargeReference);
        //==========================================================================================================
        //Titre: Observation
        $pdf->setFont($font2, 'B', 10);
        $pdf->Cell(30, 6, 'Observation', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->setFont($font2, '', 10);
        $pdf->MultiCell(164, 100, ': ' . $dto->observation, 0, '', 0, 0, '', '', true);

        $pdf->Output($filePath, 'F');
    }
}
