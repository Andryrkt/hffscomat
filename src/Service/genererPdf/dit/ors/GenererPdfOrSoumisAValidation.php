<?php

namespace App\Service\genererPdf\dit\ors;

use App\Service\genererPdf\HeaderPdf;
use App\Service\genererPdf\GeneratePdf;
use App\Controller\Traits\FormatageTrait;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Service\genererPdf\PdfTableGeneratorFlexible;
use App\Service\genererPdf\dit\ors\Tables\SituationOrTableTrait;
use App\Service\genererPdf\dit\ors\Tables\RecapitulationOrTableTrait;
use App\Service\genererPdf\dit\ors\Tables\PieceFaibleActiviteTableTrait;
use App\Service\genererPdf\dit\ors\Tables\TableauMargeTableTrait;

class GenererPdfOrSoumisAValidation extends GeneratePdf
{
    use FormatageTrait;
    use SituationOrTableTrait;
    use RecapitulationOrTableTrait;
    use PieceFaibleActiviteTableTrait;
    use TableauMargeTableTrait;

    // ORDRE DE REPARATION (OR)
    public function copyToDw(string $filename, string $numDit)
    {
        $cheminFichierDistant = $this->baseCheminDocuware . 'OR/' . $filename;
        $cheminDestinationLocal = $this->baseCheminDuFichier . 'dit/' . $numDit . '/' . $filename;
        copy($cheminDestinationLocal, $cheminFichierDistant);
    }

    /**
     * generer pdf pour la soumission OR
     */
    function GenererPdf(
        OrSoumissionDto $dto, 
        array $montantPdf, 
        array $quelqueaffichage, 
        string $email, 
        array $pieceFaibleAchat = [], 
        array $tableauMarge, 
        string $nomAvecCheminFichier)
    {
        $pdf = new HeaderPdf($email);
        $tableGenerator = new PdfTableGeneratorFlexible();

        $tableGenerator->setOptions([
            'table_attributes' => 'border="0" cellpadding="0" cellspacing="0" align="center" style="font-size: 8px;"',
            'header_row_style' => 'background-color: #D3D3D3;',
            'footer_row_style' => 'background-color: #D3D3D3;'
        ]);


        $pdf->AddPage();


        $pdf->setFont('helvetica', 'B', 17);
        $pdf->Cell(0, 6, 'Validation OR - SCT', 0, 0, 'C', false, '', 0, false, 'T', 'M');
        $pdf->Ln(10, true);

        // Début du bloc
        $pdf->setFont('helvetica', '', 10);
        $startX = $pdf->GetX();
        $startY = $pdf->GetY();

        $pdf->setFont('helvetica', 'B', 10);
        // Date de soumission
        $pdf->Cell(45, 6, 'Date soumission : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 10);
        $pdf->cell(50, 6, (new \DateTime($dto->dateSoumission))->format('d/m/Y'), 0, 1, '', false, '', 0, false, 'T', 'M');

        // numero devis
        $pdf->setAbsX(130);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->cell(20, 6, 'N° Devis :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 10);
        $pdf->cell(0, 6, $quelqueaffichage['numDevis'][0]['seor_numdev'] === '' ? 0 : $quelqueaffichage['numDevis'][0]['seor_numdev'], 0, 0, '', false, '', 0, false, 'T', 'M');

        // Numéro OR
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Numéro OR : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 10);
        $pdf->cell(50, 6, $dto->numeroOr, 0, 1, '', false, '', 0, false, 'T', 'M');

        //sortie pol
        $pdf->setAbsX(130);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->cell(20, 6, 'Sortie POL : ', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 10);
        $pdf->cell(0, 6, ' ' . $quelqueaffichage['pol'], 0, 0, '', false, '', 0, false, 'T', 'M');

        // Version à valider
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Version à valider : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 10);
        $pdf->cell(50, 6, $dto->numeroVersion, 0, 1, '', false, '', 0, false, 'T', 'M');

        // sortie magasin
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Sortie magasin : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 10);
        $pdf->cell(50, 6, $quelqueaffichage['sortieMagasin'], 0, 1, '', false, '', 0, false, 'T', 'M');

        // Achat locaux
        $pdf->SetXY($startX, $pdf->GetY() + 2);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(45, 6, 'Achat locaux : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 10);
        $pdf->cell(50, 6, $quelqueaffichage['achatLocaux'], 0, 1, '', false, '', 0, false, 'T', 'M');

        // Fin du bloc
        $pdf->Ln(10, true);

        // ================================================================================================
        // Tableau pour la situation de l'OR

        $this->renderSituationOr($pdf, $tableGenerator, $montantPdf);

        //$pdf->Ln(10, true);
        //===========================================================================================
        //Titre: Controle à faire
        $pdf->setFont('helvetica', 'B', 12);
        $pdf->Cell(0, 6, 'Contrôle à faire (par rapport dernière version) : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->Ln(10, true);

        $pdf->setFont('helvetica', '', 10);
        //Nouvelle intervention
        $pdf->Cell(45, 6, ' - Nouvelle intervention : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->cell(50, 5, $montantPdf['nombreStatutNouvEtSupp']['nbrNouv'], 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(5, true);

        //intervention supprimer

        $pdf->Cell(45, 6, ' - Intervention supprimée : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->cell(50, 5, $montantPdf['nombreStatutNouvEtSupp']['nbrSupp'], 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(5, true);

        //nombre ligne modifiée
        $pdf->Cell(45, 6, ' - Nombre ligne modifiée :', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->cell(50, 5, $montantPdf['nombreStatutNouvEtSupp']['nbrModif'], 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(5, true);

        //montant total modifié
        $pdf->Cell(45, 6, ' - Montant total modifié :', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->cell(50, 5, $this->formatNumber($montantPdf['nombreStatutNouvEtSupp']['mttModif']), 0, 0, '', false, '', 0, false, 'T', 'M');

        $pdf->Ln(10, true);

        //==========================================================================================================
        //Titre: Récapitulation de l'OR
        $this->renderRecapitulationOr($pdf, $tableGenerator, $montantPdf);
        //==========================================================================================================
        //Titre: Pièce(s) à faible activité d'achat
        $this->renderPieceFaibleActivite($pdf, $tableGenerator, $pieceFaibleAchat);
        //==========================================================================================================
        //Titre: Tableaux de marge (CAT, MFN, Autres)
        $this->renderTableauxMarge($pdf, $tableGenerator, $tableauMarge);
        //==========================================================================================================
        //Titre: Observation
        $pdf->setFont('helvetica', 'B', 12);
        $pdf->Cell(30, 6, 'Observation : ', 0, 0, 'L', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 9);
        $pdf->MultiCell(164, 60, $dto->observation, 0, '', 0, 0, '', '', true);

        //==========================================================================================================


        $pdf->Output($nomAvecCheminFichier, 'F');
    }
}
