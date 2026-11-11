<?php

namespace App\Service\genererPdf\atelier\CVA;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\CVA\OrCsaSoumissionDto;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Service\genererPdf\dit\ors\Tables\PieceFaibleActiviteTableTrait;
use App\Service\genererPdf\dit\ors\Tables\RecapitulationOrTableTrait;
use App\Service\genererPdf\dit\ors\Tables\SituationOrTableTrait;
use App\Service\genererPdf\dit\ors\Tables\TableauMargeReferenceTableTrait;
use App\Service\genererPdf\dit\ors\Tables\TableauMargeTableTrait;
use App\Service\genererPdf\GeneratePdf;
use App\Service\genererPdf\HeaderPdf;
use App\Service\genererPdf\PdfTableGeneratorFlexible;

class GenererPdfOrCsa extends GeneratePdf
{
    use FormatageTrait;
    use SituationOrTableTrait;
    use RecapitulationOrTableTrait;
    use PieceFaibleActiviteTableTrait;
    use TableauMargeTableTrait;
    use TableauMargeReferenceTableTrait;

    // ORDRE DE REPARATION (OR)
    public function copyToDw(string $filename, string $numDit, string $langueIps): bool
    {
        $cheminFichierDistant = $langueIps === 'A'
            ? $this->baseCheminDocuware . 'OR/' . $filename
            : $this->baseCheminDocuware . 'OR - Fr/' . $filename;

        $cheminDestinationLocal = $this->baseCheminDuFichier . 'dit/' . $numDit . '/' . $filename;

        return $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    /**
     * Génère le PDF complet :
     *   - Page de garde
     *   - OR Scomat (format PROFORMA INVOICE, sections 1 à 5)
     */
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

        // --------- Page de garde ---------
        $pdf->AddPage();
        $this->renderPageDeGarde($pdf, $dto);

        // --------- OR Scomat ---------
        $pdf->AddPage();
        $this->renderOrScomat($pdf, $dto);

        $pdf->Output($chemin, 'F');
    }

    // =====================================================================
    // Page de garde
    // =====================================================================
    private function renderPageDeGarde(HeaderPdf $pdf, OrCsaSoumissionDto $dto): void
    {
        $pdf->SetY(90);

        $pdf->setFont('helvetica', 'B', 24);
        $pdf->Cell(0, 12, 'ORDRE DE REPARATION', 0, 1, 'C');

        $pdf->setFont('helvetica', '', 14);
        $pdf->Cell(0, 8, 'OR - CSA', 0, 1, 'C');

        $pdf->Ln(20);

        $pdf->setFont('helvetica', 'B', 12);
        $pdf->Cell(0, 8, 'N° OR : ' . $dto->numeroOr, 0, 1, 'C');

        $pdf->setFont('helvetica', '', 11);
        $pdf->Cell(0, 7, 'Réf. demande : ' . $dto->refDemande, 0, 1, 'C');
        $pdf->Cell(0, 7, 'Date : ' . $dto->dateOr, 0, 1, 'C');

        $pdf->Ln(20);

        $pdf->setFont('helvetica', '', 12);
        $pdf->Cell(0, 8, $dto->nomClient, 0, 1, 'C');
    }

    // =====================================================================
    // OR Scomat (PROFORMA INVOICE)
    // =====================================================================
    private function renderOrScomat(HeaderPdf $pdf, OrCsaSoumissionDto $dto): void
    {
        // -------- Titre --------
        $pdf->setFont('helvetica', 'B', 17);
        $pdf->Cell(0, 6, 'PROFORMA INVOICE', 0, 0, 'C');
        $pdf->Ln(12, true);

        // =================================================================
        // Section 1 : Client
        // =================================================================
        $pdf->setFont('helvetica', 'B', 11);
        $pdf->Cell(90, 6, $dto->nomClient, 0, 1, 'L');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell(90, 5, $dto->adresseClient, 0, 1, 'L');
        $pdf->Cell(90, 5, $dto->bureauPostal,  0, 1, 'L');
        $pdf->Cell(90, 5, $dto->pays,          0, 1, 'L');

        $pdf->Ln(6);

        // =================================================================
        // Section 2 : Infos proforma
        // =================================================================
        $labelW = 40;
        $valueW = 60;

        $this->ligneLabelValeur($pdf, 'Customer No. :', $dto->numeroClient, $labelW, $valueW);
        $this->ligneLabelValeur($pdf, 'Invoice No. :',  $dto->numeroOr,     $labelW, $valueW);
        $this->ligneLabelValeur($pdf, 'Date :',        $dto->dateOr,       $labelW, $valueW);

        $pdf->Ln(4);

        // =================================================================
        // Section 3 : Références
        // =================================================================
        $this->ligneLabelValeur($pdf, 'Request reference :', $dto->refDemande,      $labelW, 0);
        $this->ligneLabelValeur($pdf, 'PO :',                $dto->referenceClient, $labelW, 0);

        $pdf->Ln(4);

        // =================================================================
        // Section 4 : Dept. credited / Sort
        // =================================================================
        $this->ligneLabelValeur(
            $pdf,
            'Dept. credited :',
            trim($dto->codeService . ' - ' . $dto->libelleService),
            $labelW,
            0
        );
        $this->ligneLabelValeur(
            $pdf,
            'Sort :',
            trim($dto->natureOperation . ' - ' . $dto->libelleNatureOperation),
            $labelW,
            0
        );

        $pdf->Ln(10);

        // =================================================================
        // Section 5 : Totaux
        // =================================================================
        $pdf->setFont('helvetica', 'B', 11);
        $pdf->Cell(120, 7, 'Total excl. VAT :', 0, 0, 'R');
        $pdf->setFont('helvetica', '', 11);
        $pdf->Cell(55, 7, $this->formatNumber($dto->montantExclVat), 0, 1, 'R');

        $pdf->setFont('helvetica', 'B', 12);
        $pdf->Cell(120, 8, 'TOTAL inc. VAT :', 0, 0, 'R');
        $pdf->setFont('helvetica', '', 12);
        $pdf->Cell(55, 8, $this->formatNumber($dto->montantIncVat), 0, 1, 'R');
    }

    // =====================================================================
    // Helper ligne label / valeur
    // =====================================================================
    private function ligneLabelValeur(
        HeaderPdf $pdf,
        string $label,
        string $valeur,
        int $labelW = 40,
        int $valueW = 0
    ): void {
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell($labelW, 6, $label, 0, 0, 'L');
        $pdf->setFont('helvetica', '', 10);
        $pdf->Cell($valueW, 6, $valeur, 0, 1, 'L');
    }
}
