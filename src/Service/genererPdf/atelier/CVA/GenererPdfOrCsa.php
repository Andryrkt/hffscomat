<?php

declare(strict_types=1);

namespace App\Service\genererPdf\atelier\CVA;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\CVA\OrCsaSoumissionDto;
use App\Service\genererPdf\GeneratePdf;
use App\Service\genererPdf\HeaderPdf;

class GenererPdfOrCsa extends GeneratePdf
{
    use FormatageTrait;

    // ---- Largeurs (mm) — A4, marges 10mm ------------------------
    private const COL_QUANTITY    = 14;
    private const COL_REFERENCE   = 34;
    private const COL_DESCRIPTION = 82;
    private const COL_UNIT_PRICE  = 30;
    private const COL_NET         = 30;

    // ------------------------------------------------------------
    // Dépôt DocuWare
    // ------------------------------------------------------------
    public function copyToDw(string $filename, string $numDit, string $langueIps): bool
    {
        $cheminFichierDistant = $langueIps === 'A'
            ? $this->baseCheminDocuware . 'OR/' . $filename
            : $this->baseCheminDocuware . 'OR - Fr/' . $filename;

        $cheminDestinationLocal = $this->baseCheminDuFichier . 'dit/' . $numDit . '/' . $filename;

        return $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    // ------------------------------------------------------------
    // Génération
    // ------------------------------------------------------------
    public function genererPdf(
        OrCsaSoumissionDto $dto,
        string $email,
        string $filename,
        string $numeroDit
    ): void {
        $chemin = $this->baseCheminDuFichier . 'dit/' . $numeroDit . '/' . $filename;
        $dir    = dirname($chemin);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Impossible de créer le répertoire {$dir}");
        }

        $pdf = new HeaderPdf($email);
        $pdf->AddPage();
        $this->renderEnteteImages($pdf);

        $this->renderOrScomat($pdf, $dto);

        $pdf->Output($chemin, 'F');
    }

    // ------------------------------------------------------------
    // En-tête : logo + image d'infos à droite (sans cadre)
    // ------------------------------------------------------------
    private function renderEnteteImages(HeaderPdf $pdf): void
    {
        $basePath = $_ENV['BASE_PATH_LONG'] ?? '';

        $logoPath = $basePath . '/Views/assets/logo_scomat.png';
        if (is_file($logoPath)) {
            $pdf->Image(
                $logoPath,
                10,
                20,
                45,
                0,
                '',
                '',
                '',
                true,
                300,
                '',
                false,
                false,
                0,
                false,
                false,
                false
            );
        }

        $infoPath = $basePath . '/Views/assets/info_scomat.png';
        if (is_file($infoPath)) {
            $pdf->Image(
                $infoPath,
                145,
                20,
                55,
                0,
                '',
                '',
                '',
                true,
                300,
                '',
                false,
                false,
                0,
                false,
                false,
                false
            );
        }
    }

    // ------------------------------------------------------------
    // PROFORMA INVOICE — corps (aucune bordure, aucun fond)
    // ------------------------------------------------------------
    private function renderOrScomat(HeaderPdf $pdf, OrCsaSoumissionDto $dto): void
    {
        // 1. Bloc client
        $pdf->SetY(38);

        $pdf->setFont('helvetica', 'B', 11);
        $pdf->Cell(120, 6, $dto->nomClient, 0, 1, 'L');

        $pdf->setFont('helvetica', '', 9);
        $pdf->Cell(120, 5, $dto->adresseClient, 0, 1, 'L');
        $pdf->Cell(120, 5, $dto->bureauPostal,  0, 1, 'L');
        $pdf->Cell(120, 5, $dto->pays,          0, 1, 'L');

        $pdf->Ln(8);

        $pdf->setFont('helvetica', 'B', 12);

        // 
        $pdf->Cell(85, 8, 'PROFORMA INVOICE No. WO ' . $dto->numeroOr, 0, 0, 'L');

        // 
        $pdf->Cell(60, 8, 'Customer No.: ' . $this->tronquer($dto->numeroClient, 20), 0, 0, 'C');
        $pdf->Cell(0, 8, 'Date : ' . $dto->dateOr, 0, 1, 'R');

        $pdf->setFont('helvetica', '', 8);
        $pdf->Cell(0, 5, sprintf('Page %d / 1', $pdf->PageNo()), 0, 1, 'R');


        $pdf->Ln(2);
        $yLigne = $pdf->GetY();
        $pdf->Line(10, $yLigne, 200, $yLigne);

        $labelW = 40;
        $valueW = 100;
        $lineH  = 1;


        $pdf->Ln(1);
        $pdf->setFont('helvetica', '', 8);
        // --- Les 3 premières lignes ---
        $pdf->Cell($labelW, $lineH, 'Customer reference', 0, 0, 'L');
        $pdf->Cell($valueW, $lineH, (string) $dto->referenceClient, 0, 1, 'L');

        $pdf->Cell($labelW, $lineH, 'Request reference', 0, 0, 'L');
        $pdf->Cell($valueW, $lineH, (string) $dto->refDemande, 0, 1, 'L');

        $pdf->Cell($labelW, $lineH, 'Operator', 0, 0, 'L');
        $pdf->Cell($valueW, $lineH, (string) $dto->operator, 0, 1, 'L');

        // --- L'espace (gap) entre Operator et Quote No. ---
        $pdf->Ln(2);

        // --- Les 2 dernières lignes ---
        $pdf->Cell($labelW, $lineH, 'Quote No.', 0, 0, 'L');
        $pdf->Cell($valueW, $lineH, (string) $dto->quoteNo, 0, 1, 'L');

        $pdf->Cell($labelW, $lineH, 'Salesman', 0, 0, 'L');
        $pdf->Cell($valueW, $lineH, (string) $dto->salesman, 0, 1, 'L');

        // LIGNE HORIZONTALE DU BAS
        $pdf->Ln(4);
        $yLigneBottom = $pdf->GetY();
        $pdf->Line(10, $yLigneBottom, 200, $yLigneBottom);
        // 5. Tableau principal (sans bordure)
        $colW   = [
            self::COL_QUANTITY,
            self::COL_REFERENCE,
            self::COL_DESCRIPTION,
            self::COL_UNIT_PRICE,
            self::COL_NET,
        ];
        $totalW = array_sum($colW);

        // En-têtes de colonnes — gras, sans fond ni bordure
        $xStart = $pdf->GetX(); // Position X de départ (généralement 10mm)
        $yStart = $pdf->GetY(); // Position Y actuelle

        // 1. Ligne horizontale du HAUT
        $pdf->Line($xStart, $yStart, $xStart + $totalW, $yStart);

        // 2. Textes des colonnes (Bold + Underline)
        $pdf->setFont('helvetica', 'BU', 8);
        $headers = ['Quantity', 'Reference', 'Description', 'Unit price', 'Net Amt'];
        $lastIndex = count($headers) - 1;

        foreach ($headers as $i => $h) {
            $align = 'L';
            if ($i === $lastIndex) $align = 'R';
            $pdf->Cell($colW[$i], $lineH + 4, $h, 0, 0, $align);
        }
        $pdf->Ln();
        // 3. Ligne horizontale du BAS
        $yEnd = $pdf->GetY();
        $pdf->Line($xStart, $yEnd, $xStart + $totalW, $yEnd);
        // Bandeau Segment 1
        $segmentDesc = $dto->segmentDescription !== ''
            ? $dto->segmentDescription
            : $dto->referenceClient;
        $pdf->Ln(1);

        // 1. Le mot "Segment" en Gras + Souligné (BU)
        $pdf->setFont('helvetica', 'BU', 8);
        $pdf->Cell(15, $lineH, 'Segment', 0, 0, 'L');

        // 2. Le numéro "1" en Gras (B)
        $pdf->setFont('helvetica', 'B', 8);
        $pdf->Cell(5, $lineH, '1', 0, 0, 'L');

        // 3. La description en Gras (B) sur le reste de la ligne
        $pdf->setFont('helvetica', 'B', 8);
        $pdf->Cell(0, $lineH, '  ' . strtoupper($segmentDesc), 0, 1, 'L');


        $pdf->Ln(2);
        // Dept. credited
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(35,  $lineH, 'Dept. credited', 0, 0, 'L');
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell($totalW - 35, $lineH, $this->libelleCombine($dto->codeService, $dto->libelleService), 0, 1, 'L');
        // Sort
        $pdf->setFont('helvetica', '', 8);
        $pdf->Cell(35, $lineH, 'Sort', 0, 0, 'L');
        $pdf->Cell($totalW - 35, $lineH, $this->libelleCombine($dto->natureOperation, $dto->libelleNatureOperation), 0, 1, 'L');
        $pdf->Ln(1);
        // Hardcoded line for Replacement
        $pdf->setFont('helvetica', 'I', 8);
        $pdf->Cell($totalW, $lineH, 'Replacement of two Batteries 200AH 12V CAT', 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->setFont('helvetica', '', 8);
        $pdf->Cell($totalW, $lineH, '2 YEARS WARRANTY', 0, 1, 'L');
        $pdf->Ln(2);

        // Lignes de détail (multi-lignes, sans bordure)
        if (!empty($dto->lignes)) {
            foreach ($dto->lignes as $ligne) {
                $this->renderLigneDetail($pdf, $ligne, $colW);
            }
        } else {
            $pdf->Cell($totalW, 4, '', 0, 1, 'L');
        }

        $pdf->Ln(2);

        // =====================================================================
        // AJOUT DE LA LIGNE EN POINTILLÉS (DASH)
        // =====================================================================

        $xDash = $pdf->GetX();
        $yDash = $pdf->GetY();

        // 1. Configurer le style de ligne en pointillés
        $pdf->SetLineStyle([
            'width' => 0.4,     // Épaisseur du trait
            'cap' => 'butt',
            'join' => 'miter',
            'dash' => 1.5,
            'color' => [50, 50, 50]
        ]);

        // 2. Tracer la ligne (ici sur toute la largeur $totalW)
        // Si vous voulez la ligne SEULEMENT sous la description, utilisez plutôt :
        $pdf->Line($xDash, $yDash, $xDash + $colW[0] + $colW[1] - 20, $yDash);

        // 3. Remettre le style de trait normal (plein) pour ne pas affecter le reste du PDF
        $pdf->SetLineStyle(['dash' => 0]);

        // 4. Espace avant la suite du document
        $pdf->Ln(1);

        $pdf->Cell($colW[0], $lineH, '-', 0, 0, 'R');
        $pdf->Cell($colW[1], $lineH, '', 0, 0, 'C');
        // Totaux segment — sans bordure ni fond
        $pdf->setFont('helvetica', '', 9);
        $pdf->Cell($colW[2], $lineH, 'Total amount', 0, 0, 'L');
        $pdf->setFont('helvetica', '', 9);
        $pdf->Cell($colW[3],  $lineH, $this->formatNumber($dto->montantExclVat), 0, 0, 'L');
        $pdf->Cell($colW[4],  $lineH, $this->formatNumber($dto->montantExclVat), 0, 1, 'R');

        $pdf->Cell($colW[0] + $colW[1] + $colW[2], $lineH, '', 0, 0, 'L');

        // Totaux segment —
        $pdf->setFont('helvetica', 'B', 9);
        $pdf->Cell($colW[3], $lineH + 6, 'Total segment', 0, 0, 'L');
        $pdf->Cell($colW[4],  $lineH + 6, $this->formatNumber($dto->montantExclVat), 0, 1, 'R');

        $pdf->Ln(10);

        // 6. Totaux TVA
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(120, 7, 'Total excl. VAT :', 0, 0, 'R');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(55, 7, $this->formatNumber($dto->montantExclVat), 0, 1, 'R');

        $pdf->setFont('helvetica', 'B', 11);
        $pdf->Cell(120, 8, 'TOTAL inc. VAT :', 0, 0, 'R');
        $pdf->setFont('helvetica', '', 11);
        $pdf->Cell(55, 8, $this->formatNumber($dto->montantIncVat), 0, 1, 'R');
    }

    /**
     * Ligne de détail.
     */
    private function renderLigneDetail(HeaderPdf $pdf, array $ligne, array $colW): void
    {

        $rowHeight = 4;

        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->setFont('helvetica', '', 9);
        $pdf->Cell($colW[0], $rowHeight, (string) ($ligne['quantity'] ?? ''), 0, 0, 'L');

        $pdf->Cell($colW[1], $rowHeight, (string) ($ligne['reference'] ?? ''), 0, 0, 'L');

        $pdf->MultiCell($colW[2],  $rowHeight, (string) ($ligne['description'] ?? ''), 0, 'L');

        $pdf->SetXY($x + $colW[0] + $colW[1] + $colW[2], $y);

        $pdf->setFont('helvetica', '', 9);
        $pdf->Cell($colW[3], $rowHeight, $this->formatNumber((float) ($ligne['unitPrice'] ?? 0)), 0, 0, 'L');

        $pdf->Cell($colW[4], $rowHeight, $this->formatNumber((float) ($ligne['netAmount'] ?? 0)), 0, 0, 'R');

        $pdf->SetXY($x, $y + $rowHeight);
    }

    // ------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------
    private function tronquer(string $txt, int $max): string
    {
        return mb_strlen($txt, 'UTF-8') > $max
            ? mb_substr($txt, 0, $max - 1, 'UTF-8') . '…'
            : $txt;
    }

    private function libelleCombine(string $code, string $libelle): string
    {
        $code    = trim($code);
        $libelle = trim($libelle);

        if ($code !== '' && $libelle !== '') {
            return "{$code} - {$libelle}";
        }

        return $code !== '' ? $code : $libelle;
    }
}
