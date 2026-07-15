<?php

namespace App\Service\genererPdf\magasin;

use TCPDF;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Service\genererPdf\GeneratePdf;

class GeneratePdfCdeMagasin extends GeneratePdf
{
    private TCPDF $pdf;
    private string $font;
    private float $w100;
    private float $titleSize;
    private float $textSize;
    private float $textHeight;

    public function __construct()
    {
        parent::__construct();
        $this->font = "helvetica";
        $this->pdf  = $this->initPDF();
        $this->w100 = $this->getUsableWidth();
        $this->titleSize = 10;
        $this->textSize = 7;
        $this->textHeight = 5;
    }

    public function generate(CommandeSoumissionDTO $dto, string $filePath): void
    {
        $this->renderHeader($dto);

        $this->pdf->Output($filePath, 'I');
    }

    private function initPDF(): TCPDF
    {
        $pdf = new TCPDF("L");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);
        $pdf->setMargins(3, 3, 3, true);
        $pdf->AddPage();

        return $pdf;
    }

    private function getUsableWidth(): float
    {
        $w_total = $this->pdf->getPageWidth();
        $margins = $this->pdf->GetMargins();

        return $w_total - ($margins['top'] * 2);
    }

    private function renderHeader(CommandeSoumissionDTO $dto): void
    {
        $this->pdf->SetFont($this->font, "B", $this->titleSize);
        $this->pdf->Cell(0, $this->textHeight + 1, "Cde Fournisseur", 0, 1);

        $wLbl = 23;
        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, "No Commande:", 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, $dto->numeroCommande, 0, 0);
        $this->pdf->Cell(0, $this->textHeight, "du {$dto->getDateJourFormatted()}", 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, "Type Commande:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, $dto->typeCde, 0, 0);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, "Délai d'expédition:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, "{$dto->delaiExpedition} jours", 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, "Fournisseur:", 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, "{$dto->numFrn}   {$dto->nomFrn}", 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, "Responsable:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, $dto->responsable, 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, "{$dto->libelleAgence} - {$dto->libelleService}", 0, 1, 'R');
    }
}
