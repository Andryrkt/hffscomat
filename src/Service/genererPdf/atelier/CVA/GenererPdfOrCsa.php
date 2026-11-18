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
    private const COL_DESCRIPTION = 62;
    private const COL_UNIT_PRICE  = 40;
    private const COL_NET         = 40;

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
        string $numeroDit,
        array $quelqueAffichage
    ): void {
        $chemin = $this->baseCheminDuFichier . 'dit/' . $numeroDit . '/' . $filename;
        $dir    = dirname($chemin);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException("Impossible de créer le répertoire {$dir}");
        }

        $pdf = new HeaderPdf($email);
        $pdf->SetMargins(8, 8, 4);

        // ----- PAGE (Page de garde) : Validation OR - CVA  -----
        $this->renderValidationOrCVA($pdf, $dto, $quelqueAffichage);

        // ----- PAGE 2+ : Proforma Invoice -----
        $pdf->AddPage();
        $this->renderEnteteImages($pdf);
        $this->renderOrScomat($pdf, $dto);
        $this->renderFooter($pdf, $dto);

        $pdf->Output($chemin, 'F');
    }

    // ------------------------------------------------------------
    // En-tête : logo + image d'infos à droite
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
        $xClient = 11;   // 4 mm < marge (8 mm) => décalage de 4 mm vers la gauche

        $pdf->SetY(36.5);
        $pdf->SetX($xClient);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(0, 0, $dto->nomClient, 0, 1, 'L');

        $pdf->SetX($xClient);
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(0, 0, $dto->adresseClient, 0, 1, 'L');

        $pdf->SetX($xClient);
        $pdf->Cell(0, 0, $dto->bureauPostal, 0, 1, 'L');

        $pdf->SetX($xClient);
        $pdf->Cell(0, 0, $dto->pays, 0, 1, 'L');

        $pdf->Ln(11);

        $pdf->setFont('helvetica', 'B', 10);
        // 
        $pdf->SetX(5);
        $pdf->Cell(85, 1, 'PROFORMA INVOICE No. WO  ' . $dto->numeroOr, 0, 0, 'L');

        // 
        $pdf->Cell(60, 1, 'Customer No. :    ' . $this->tronquer($dto->numeroClient, 20), 0, 0, 'C');
        $pdf->Cell(0, 1, 'Date : ' . $dto->dateOr, 0, 1, 'R');

        $pdf->setFont('helvetica', '', 7);
        $pdf->Cell(0, 4, sprintf('Page %d / %d', $pdf->PageNo(), $pdf->getNumPages()), 0, 1, 'R');


        $pdf->Ln(0);
        $yLigne = $pdf->GetY();
        $pdf->Line(8, $yLigne, 200, $yLigne);

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
        $pdf->Ln(2.5);
        $yLigneBottom = $pdf->GetY();
        $pdf->Line(8, $yLigneBottom, 200, $yLigneBottom);
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
        $segmentDesc = $dto->segmentDescription;
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
        $pdf->Cell($totalW, $lineH, '', 0, 1, 'L');

        $pdf->Ln(2);
        $pdf->setFont('helvetica', '', 8);
        $pdf->Cell($totalW, $lineH, '', 0, 1, 'L');
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

        $pdf->Cell($colW[0] + $colW[1], $lineH, '', 0, 0, 'R');
        $pdf->Cell($colW[2], $lineH, 'Total amount', 0, 0, 'L');
        $pdf->Cell($colW[3], $lineH, $this->formaterPrix($dto->montantExclVat), 0, 0, 'L');
        $pdf->setFont('helvetica', '', 8);
        $pdf->Cell($colW[4], $lineH, $this->formaterPrix($dto->montantExclVat), 0, 1, 'R');

        $pdf->Cell($colW[0] + $colW[1] + $colW[2], $lineH, '', 0, 0, 'L');

        // Totaux segment —
        $pdf->setFont('helvetica', 'B', 8);
        $pdf->Cell($colW[3], $lineH + 6, 'Total Segment', 0, 0, 'R');
        $pdf->Cell($colW[4], $lineH + 6, $this->formaterPrix($dto->montantExclVat), 0, 1, 'R');
    }

    /**
     * Ligne de détail.
     */
    private function renderLigneDetail(HeaderPdf $pdf, array $ligne, array $colW): void
    {
        $x = $pdf->GetX();
        $y = $pdf->GetY();

        $pdf->setFont('helvetica', '', 9);

        // Hauteur d'une ligne de texte pour du 9pt (TCPDF utilise ~1.15–1.25 × font)
        $lineHeight = 4.5;
        $padding    = 1;

        // ✅ 1. Calculer la hauteur RÉELLE de la description (peut tenir sur plusieurs lignes)
        $description = (string) ($ligne['description'] ?? '');
        $descHeight  = $pdf->getStringHeight($colW[2], $description);
        $rowHeight   = max($lineHeight, $descHeight) + $padding;

        // ✅ 2. Vérifier le saut de page AVANT de dessiner (contrôle explicite)
        $margins    = $pdf->getMargins();
        $pageBreakY = $pdf->getPageHeight() - $margins['bottom'];

        if ($y + $rowHeight > $pageBreakY) {
            $pdf->AddPage();
            $x = $pdf->GetX();
            $y = $pdf->GetY();
        }

        // ✅ 3. Quantity
        $pdf->SetXY($x, $y);
        $pdf->Cell($colW[0], $rowHeight, (string) ($ligne['quantity'] ?? ''), 0, 0, 'L');

        // ✅ 4. Reference
        $pdf->Cell($colW[1], $rowHeight, (string) ($ligne['reference'] ?? ''), 0, 0, 'L');

        // ✅ 5. Description en MultiCell (gère le multi-lignes)
        $pdf->MultiCell(
            $colW[2],                 // width
            $lineHeight,              // line height
            $description,             // txt
            0,                        // border
            'L',                      // align
            false,                    // fill
            0,                        // ln = 0 (on gère Y nous-mêmes)
            $x + $colW[0] + $colW[1], // x
            $y,                       // y
            true,                     // reseth
            0,                        // stretch
            false,                    // ishtml
            true,                     // autopadding
            $rowHeight,               // maxh : on force la même hauteur que les autres cellules
            'T'                       // valign
        );

        // ✅ 6. Unit price
        $pdf->SetXY($x + $colW[0] + $colW[1] + $colW[2], $y);
        $pdf->Cell($colW[3], $rowHeight, $this->formaterPrix((float) ($ligne['unitPrice'] ?? 0)), 0, 0, 'R');

        // ✅ 7. Net amount
        $pdf->Cell($colW[4], $rowHeight, $this->formaterPrix((float) ($ligne['netAmount'] ?? 0)), 0, 0, 'R');

        // ✅ 8. Avancer à la ligne suivante AVEC la vraie hauteur
        $pdf->SetXY($x, $y + $rowHeight);
    }



    private function renderFooter(HeaderPdf $pdf, OrCsaSoumissionDto $dto): void
    {
        $colW   = [
            self::COL_QUANTITY,
            self::COL_REFERENCE,
            self::COL_DESCRIPTION,
            self::COL_UNIT_PRICE,
            self::COL_NET,
        ];
        $totalW = array_sum($colW);
        $threeColW = $colW[0] + $colW[1] + $colW[2];
        // =====================================================================
        // 1. GESTION DU SAUT DE PAGE (LOGIQUE DE VOTRE DEMANDE)
        // =====================================================================

        $footerHeight = 58;

        $pageHeight = $pdf->getPageHeight();

        $yLimit = $pageHeight - $footerHeight;

        if ($pdf->GetY() > $yLimit) {
            $pdf->AddPage();
        }

        // =====================================================================
        // 2. POSITIONNEMENT EN BAS DE LA PAGE (Page 1 ou Page 2)
        // =====================================================================

        $pdf->SetY(- ($footerHeight));
        $pdf->SetX(10);

        $x = $pdf->GetX();
        $y = $pdf->GetY();
        $pdf->Line($x, $y, $x + $totalW, $y);
        $pdf->Ln(0);


        $pdf->setFont('helvetica', 'B', 8);
        $pdf->SetX(16);
        $pdf->Cell($totalW, 0.5, 'VAT%', 0, 1, 'R');
        $pdf->setFont('helvetica', '', 8);
        $pdf->SetX(16);
        $pdf->Cell($totalW, 0.5, number_format($dto->tauxTva * 100, 2, '.', ''), 0, 1, 'R');

        $pdf->Ln(2);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($x, $pdf->GetY(), $x + $totalW, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->Ln(-0.25);

        $pdf->setFont('helvetica', 'B', 10);
        // Total excl. VAT
        $pdf->Cell($threeColW, 0, '', 0, 0);
        $pdf->Cell($colW[3], 0, 'Total excl. VAT', 0, 0, 'L');
        $pdf->Cell($colW[4], 0, $this->formaterPrix($dto->montantExclVat), 0, 1, 'R');

        // VAT Total
        $pdf->Cell($threeColW, 0, '', 0, 0);
        $pdf->Cell($colW[3], 0, 'VAT Total', 0, 0, 'L');
        $pdf->Cell($colW[4], 0, $this->formaterPrix($dto->montantIncVat - $dto->montantExclVat), 0, 1, 'R');

        // TOTAL incl. VAT
        $pdf->Cell($threeColW, 0, '', 0, 0);
        $pdf->Cell($colW[3], 0, 'TOTAL incl. VAT', 0, 0, 'L');
        $pdf->Cell($colW[4], 0, $this->formaterPrix($dto->montantIncVat), 0, 1, 'R');


        $pdf->setFont('helvetica', 'B', 8);
        // Currency
        $pdf->Cell($threeColW, 0, '', 0, 0);
        $pdf->Cell($colW[3], 0, 'Currency', 0, 0, 'L');
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell($colW[4], 0, 'MUR', 0, 1, 'R');

        // 6. Troisième ligne horizontale (bas du bloc)
        $pdf->Ln(-0.25);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($x, $pdf->GetY(), $x + $totalW, $pdf->GetY());
        $pdf->SetLineWidth(0.2);

        // =====================================================================
        // AJOUT DES IMAGES EN BAS DE PAGE
        // =====================================================================
        $pdf->Ln(-5); // On descend sous la ligne
        // $yBasBloc = $pdf->GetY();
        // $basePath = $_ENV['BASE_PATH_LONG'] ?? '';

        // $footerLogosPath = $basePath . '/Views/assets/footer_scomat.png';
        // if (is_file($footerLogosPath)) {
        //     $imgY =  $yBasBloc + 1.5;
        //     $pdf->Image($footerLogosPath, 10, $imgY, 65, 0, '', '', '', true, 300, '', false, false, 1, false, false, false);
        // }

        // Si QR Code
        // $footerQrPath = $basePath . '/Views/assets/footer_qr.png';
        // if (is_file($footerQrPath)) {
        //     $pdf->Image($footerQrPath, 150, $pdf->GetY() - 2, 35, 0, '', '', '', true, 300, '', false, false, 0, false, false, false);
        // }
    }

    // Page de Garde
    private function renderValidationOrCVA(
        HeaderPdf $pdf,
        OrCsaSoumissionDto $dto,
        array $quelqueAffichage
    ): void {
        $pdf->AddPage();

        // Titre centré
        $pdf->setFont('helvetica', 'B', 17);
        $pdf->Cell(0, 6, 'Validation OR - CVA', 0, 0, 'C', false, '', 0, false, 'T', 'M');
        $pdf->Ln(10, true);

        $pdf->setFont('helvetica', '', 10);
        $startX = $pdf->GetX();

        // --- Ligne 1 : Date soumission (gauche) + N° Devis (droite) ---
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Date soumission : ', 0, 0, 'L');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(
            50,
            6,
            $quelqueAffichage['dateSoumission'] ?? '',
            0,
            1,
            'L'
        );

        $pdf->SetAbsX(130);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'N° Devis :', 0, 0);
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(0, 6, $quelqueAffichage['numDevis'], 0, 0);

        // --- Ligne 2 : Numéro OR (gauche) + Sortie POL (droite) ---
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Numéro OR : ', 0, 0, 'L');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(50, 6, $dto->numeroOr, 0, 1);

        $pdf->SetAbsX(130);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(20, 6, 'Sortie POL : ', 0, 0);
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(0, 6, ' ' . ($quelqueAffichage['pol'] ?? ''), 0, 0);

        // --- Ligne 3 : Version à valider ---
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Version à valider : ', 0, 0, 'L');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(50, 6, $quelqueAffichage['numeroVersion'] ?? '', 0, 1);

        // --- Ligne 4 : Sortie magasin ---
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Sortie magasin : ', 0, 0, 'L');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(50, 6, $quelqueAffichage['sortieMagasin'] ?? '', 0, 1);

        // --- Ligne 5 : Achat locaux ---
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Achat locaux : ', 0, 0, 'L');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(50, 6, $quelqueAffichage['achatLocaux'] ?? '', 0, 1);

        $pdf->Ln(10, true);
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
