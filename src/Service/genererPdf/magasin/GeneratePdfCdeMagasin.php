<?php

namespace App\Service\genererPdf\magasin;

use TCPDF;
use App\Service\genererPdf\GeneratePdf;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionLigneDTO;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDetailDTO;

class GeneratePdfCdeMagasin extends GeneratePdf
{
    private TCPDF $pdf;
    private string $font;
    private float $titleSize;
    private float $textSize;
    private float $textHeight;
    private float $mR;
    private float $mL;
    private float $mT;
    private float $mB;
    private array $mainRowWidths = [];
    private const COL_LABELS = [
        'noLigne'      => "N°\nLine",
        'cst'          => "CST",
        'avBat'        => "Av.\nBat.",
        'ref'          => "Réf.",
        'packQty'      => "Pack.\nQty",
        'designation'  => "Désignation",
        'npr'          => "NPR\n(*)",
        'fms'          => "F\nM\nS",
        'ret'          => "Ret",
        'qteCdee'      => "Qté\nCdée",
        'qteDispo'     => "Qté\nDispo",
        'qteDispoMin'  => "Qté\nDispo\nMin",
        'qteDispoMax'  => "Qté\nDispo\nMax",
        'qteVte6M'     => "Qté Vte\nDernier\n6 Mois",
        'nbrVte6M'     => "Nbr Vte\nDernier\n6 Mois",
        'coutUnit'     => "Coût\nUnit.",
        'coutTotal'    => "Coût\nTotal",
        'poids'        => "Poids\n[kg]",
    ];

    public function __construct()
    {
        parent::__construct();
        $this->font       = "helvetica";
        $this->pdf        = $this->initPDF();
        $this->titleSize  = 10;
        $this->textSize   = 7;
        $this->textHeight = 5;
    }

    public function generate(CommandeSoumissionDTO $dto, string $filePath): void
    {
        $this->renderHeader($dto);

        $this->renderTable($dto->lignes);

        $this->pdf->Output($filePath, 'I');
    }

    private function initPDF(): TCPDF
    {
        $pdf = new TCPDF("L");
        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        // Définir les marges
        $this->mL = $this->mR = $this->mT = $this->mB = 5;
        $pdf->setMargins($this->mL, $this->mT, $this->mR, true);

        $pdf->AddPage();

        return $pdf;
    }

    private function getUsableWidth(): float
    {
        $w_total = $this->pdf->getPageWidth();
        return $w_total - ($this->mT + $this->mB);
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
        $this->pdf->Cell(0, $this->textHeight, $dto->getDateCdeFormatted(), 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Type Commande:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell($wLbl, $this->textHeight, $dto->typeCde, 0, 0);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Délai d'expédition:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, $dto->getDelaiExpedition(), 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Fournisseur:", 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, $dto->getFournisseur(), 0, 1);

        $this->pdf->setFont($this->font, "I", $this->textSize);
        $this->cellUnderline($wLbl, $this->textHeight, "Responsable:", 0, 0);

        $this->pdf->setFont($this->font, "", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, $dto->responsable, 0, 0);

        $this->pdf->setFont($this->font, "B", $this->textSize);
        $this->pdf->Cell(0, $this->textHeight, $dto->getAgenceService(), 0, 1, 'R');
    }

    /** 
     * Méthode pour construire le tableau de lignes de commande
     * 
     * @param list<CommandeSoumissionLigneDTO> $lignes 
     * 
     * @return void
     */
    private function renderTable(array $lignes): void
    {
        $this->pdf->Ln(5);

        // Définir la largeur du colonne principale avant utilisation
        $this->defineMainRowWidths();

        $this->renderTableHeader();

        $rowIndex = 0;

        foreach ($lignes as $ligne) {
            // 1. Calculer la hauteur totale du bloc (ligne + sous-lignes)
            $blockHeight = $this->calculateBlockHeight($ligne);

            // 2. Vérifier s'il faut un saut de page AVANT de dessiner le bloc
            $this->checkPageBreak($blockHeight);

            // 3. Couleur de fond alternée
            $fill = ($rowIndex % 2 == 0);
            $this->pdf->SetFillColor(240, 240, 240);

            // 4. Dessiner la ligne principale
            $this->renderMainRow($ligne, $fill);

            // 5. Dessiner les sous-lignes (même fill que la ligne parente, pour cohérence visuelle)
            foreach ($ligne->details as $detail) {
                $this->renderSubRow($detail, $fill);
            }

            $rowIndex++;
        }
    }

    private function defineMainRowWidths(): void
    {
        $w100 = $this->getUsableWidth();
        $this->mainRowWidths = array_fill_keys(array_keys(self::COL_LABELS), 0);

        $this->mainRowWidths['noLigne'] = $this->mainRowWidths['cst'] = $this->mainRowWidths['avBat'] = $this->mainRowWidths['npr'] = $this->mainRowWidths['ret'] = $this->mainRowWidths['fms'] = 10;
        $this->mainRowWidths['coutUnit'] = $this->mainRowWidths['coutTotal'] = $this->mainRowWidths['ref'] = 20;
        $this->mainRowWidths['designation'] = 50;

        $wUsed = array_sum(array_values($this->mainRowWidths));
        $wRemaining = $w100 - $wUsed;

        $this->mainRowWidths['packQty'] = $this->mainRowWidths['qteCdee'] = $this->mainRowWidths['qteDispo'] = $this->mainRowWidths['qteDispoMin'] = $this->mainRowWidths['qteDispoMax'] = $this->mainRowWidths['poids'] = $this->mainRowWidths['qteVte6M'] = $this->mainRowWidths['nbrVte6M'] = $wRemaining / 8;
    }

    private function renderTableHeader(): void
    {
        $this->pdf->SetFont($this->font, "B", $this->textSize);
        $this->pdf->SetFillColor(60, 60, 60);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->SetDrawColor(60, 60, 60);

        $headerHeight = $this->calculateHeaderHeight();
        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();

        foreach ($this->mainRowWidths as $key => $width) {
            $label = self::COL_LABELS[$key];

            // MultiCell avec fond, mais sans avancer X automatiquement
            $this->pdf->MultiCell($width, $headerHeight, $label, 1, 'C', true, 0, null, null, true, 0, false, true, $headerHeight, 'M');

            $x += $width;
            $this->pdf->SetXY($x, $y);
        }

        $this->pdf->SetXY($this->mL, $y + $headerHeight);

        // Reset couleurs pour les lignes de données
        $this->pdf->SetTextColor(0, 0, 0);
        $this->pdf->SetDrawColor(0, 0, 0);
        $this->pdf->SetFont($this->font, "", $this->textSize);
    }

    private function calculateBlockHeight(CommandeSoumissionLigneDTO $ligne): float
    {
        $height = $this->textHeight; // hauteur ligne principale
        $height += count($ligne->details) * $this->textHeight;
        return $height;
    }

    private function calculateHeaderHeight(): float
    {
        $maxLines = 1;

        foreach (self::COL_LABELS as $key => $label) {
            $width = $this->mainRowWidths[$key];
            $nbLines = $this->pdf->getNumLines($label, $width);
            $maxLines = max($maxLines, $nbLines);
        }

        return $maxLines * ($this->textHeight - 1); // maxLines = 3
    }

    private function checkPageBreak(float $neededHeight): void
    {
        $pageBreakTrigger = $this->pdf->getPageHeight() - $this->pdf->getBreakMargin();
        if ($this->pdf->GetY() + $neededHeight > $pageBreakTrigger) {
            $this->pdf->AddPage();
            $this->renderTableHeader(); // réafficher les en-têtes de colonnes
        }
    }

    private function renderMainRow(CommandeSoumissionLigneDTO $ligne, bool $fill): void
    {
        $this->pdf->SetFont($this->font, "B", $this->textSize);
        $this->pdf->SetFillColor(240, 240, 240);

        $this->pdf->Cell($this->mainRowWidths['noLigne'],     $this->textHeight, $ligne->numLine,        0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['cst'],         $this->textHeight, $ligne->const,          0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['avBat'],       $this->textHeight, $ligne->avBat,          0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['ref'],         $this->textHeight, $ligne->ref,            0, 0, 'L', $fill);
        $this->pdf->Cell($this->mainRowWidths['packQty'],     $this->textHeight, $ligne->packQty,        0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['designation'], $this->textHeight, $ligne->designation,    0, 0, 'L', $fill);
        $this->pdf->Cell($this->mainRowWidths['npr'],         $this->textHeight, $ligne->npr,            0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['fms'],         $this->textHeight, $ligne->fms,            0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['ret'],         $this->textHeight, $ligne->ret,            0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['qteCdee'],     $this->textHeight, $ligne->qteDem,         0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['qteDispo'],    $this->textHeight, $ligne->qteDispo,       0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['qteDispoMin'], $this->textHeight, $ligne->qteDispoMin,    0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['qteDispoMax'], $this->textHeight, $ligne->qteDispoMax,    0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['qteVte6M'],    $this->textHeight, $ligne->qteVteDer6Mois, 0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['nbrVte6M'],    $this->textHeight, $ligne->nbrVteDer6Mois, 0, 0, 'C', $fill);
        $this->pdf->Cell($this->mainRowWidths['coutUnit'],    $this->textHeight, $ligne->prixUnitaire,   0, 0, 'R', $fill);
        $this->pdf->Cell($this->mainRowWidths['coutTotal'],   $this->textHeight, $ligne->prixTotal,      0, 0, 'R', $fill);
        $this->pdf->Cell($this->mainRowWidths['poids'],       $this->textHeight, $ligne->poids,          0, 1, 'C', $fill);
    }

    private function renderSubRow(CommandeSoumissionDetailDTO $detail, bool $fill): void
    {
        $this->pdf->SetFont($this->font, "", $this->textSize - 0.6);
        $this->pdf->SetFillColor(240, 240, 240);

        // Largeur vide = somme des colonnes de "N° Ligne" jusqu'à "Désignation" incluse
        $emptyWidth = $this->mainRowWidths['noLigne']
            + $this->mainRowWidths['cst']
            + $this->mainRowWidths['avBat']
            + $this->mainRowWidths['ref']
            - ($this->mainRowWidths['ref'] / 2);

        $x = $this->pdf->GetX();
        $y = $this->pdf->GetY();

        // Cellule vide (avec bordure normale, comme le reste du tableau)
        $this->pdf->Cell($emptyWidth, $this->textHeight, '', 0, 0, 'L', $fill);

        $this->pdf->SetFont($this->font, "I", $this->textSize - 0.6);
        $this->cellUnderline(20, $this->textHeight, "Référence client:", 0, 0, 'L', $fill);

        $this->pdf->Cell(10, $this->textHeight, "VTE NEG", 0, 0, 'R', $fill);
        $this->pdf->Cell(15, $this->textHeight, " - {$detail->numDoc}",    0, 0, 'L', $fill);
        $this->pdf->Cell(75, $this->textHeight, $detail->getRefSplitted(), 0, 0, 'C', $fill);
        $this->pdf->Cell(90, $this->textHeight, "{$detail->numClient} - {$detail->nomClient}", 0, 0, 'L', $fill);
        $w100 = $this->getUsableWidth();
        $wUsed = $emptyWidth + 20 + 10 + 15 + 75 + 15 + 75;

        $this->pdf->Cell($w100 - $wUsed,  $this->textHeight, $detail->getDatePlanningFormatted(), 0, 1, 'L', $fill);

        // Ligne pointillée séparant ce détail du suivant (sous la cellule texte)
        $this->drawDottedSeparator(
            $x + $emptyWidth,
            $y + $this->textHeight,
            $this->pdf->getPageWidth() - $this->mL
        );
    }

    /**
     * Trace une ligne horizontale en pointillés entre deux sous-lignes de détail.
     */
    private function drawDottedSeparator(float $xStart, float $y, float $xEnd): void
    {
        $this->pdf->SetLineStyle([
            'width' => 0.5,
            'dash'  => '3',
            'color' => [150, 150, 150],
        ]);

        $this->pdf->Line($xStart, $y, $xEnd, $y);

        // Reset au style de ligne normal (plein) pour la suite du tableau
        $this->pdf->SetLineStyle([
            'width' => 0.1,
            'dash'  => 0,
            'color' => [0, 0, 0],
        ]);
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
        if ($ln == 0)      $this->pdf->SetXY($x + $w - 1, $y);
        elseif ($ln == 1)  $this->pdf->SetXY($this->mL, $y + $h);
        elseif ($ln == 2)  $this->pdf->SetXY($x - 1, $y + $h);
    }

    public function copyToDOCUWARE(string $cheminDuFichier, string $numCmde): bool
    {
        $cheminDW = rtrim($this->baseCheminDocuware, '/\\') . '/cmde/' . $numCmde . '.pdf';
        return $this->copyFile($cheminDuFichier, $cheminDW);
    }
}
