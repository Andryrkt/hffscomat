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
        $this->cellUnderline($wLbl, $this->textHeight, "No Commande:", 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, $dto->numeroCommande, 0, 0);
        $this->pdf->Cell(0, $this->textHeight, "du {$dto->getDateJourFormatted()}", 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Type Commande:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, $dto->typeCde, 0, 0);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Délai d'expédition:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, "{$dto->delaiExpedition} jours", 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Fournisseur:", 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, "{$dto->numFrn}   {$dto->nomFrn}", 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Responsable:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, $dto->responsable, 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, "{$dto->libelleAgence} - {$dto->libelleService}", 0, 1, 'R');
    }

    /**
     * Affiche une Cell classique et dessine un trait fin en dessous du texte,
     * pour simuler un soulignement (non supporté nativement par TCPDF sur Cell()).
     */
    private function cellUnderline(float $w, float $h, string $txt, $border = 0, int $ln = 0, string $align = '', bool $fill = false): void
    {
        $x = $this->pdf->GetX() + 1; // + décalage
        $y = $this->pdf->GetY();

        $this->pdf->Cell($w, $h, $txt, $border, 0, $align, $fill);

        // Largeur réelle du texte pour ne souligner que le texte, pas toute la cellule
        $textWidth = $this->pdf->GetStringWidth($txt);

        $lineY = $y + $h - 1; // légèrement au-dessus du bas de la cellule
        $lineXStart = $x;
        $lineXEnd = $x + $textWidth;

        $this->pdf->Line($lineXStart, $lineY, $lineXEnd, $lineY);

        // Repositionner le curseur comme le ferait Cell() avec $ln=0
        if ($ln == 0) {
            $this->pdf->SetXY($x + $w, $y);
        } elseif ($ln == 1) {
            $this->pdf->SetXY(3, $y + $h);
        } elseif ($ln == 2) {
            $this->pdf->SetXY($x, $y + $h);
        }
    }
}
