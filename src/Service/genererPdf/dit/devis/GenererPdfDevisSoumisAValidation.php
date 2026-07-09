<?php

namespace App\Service\genererPdf\dit\devis;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationDto;
use App\Service\genererPdf\dit\ors\Tables\TableauMargeTableTrait;
use App\Service\genererPdf\GeneratePdf;
use App\Service\genererPdf\HeaderPdf;
use App\Service\genererPdf\PdfTableGenerator;
use App\Service\genererPdf\PdfTableGeneratorFlexible;

class GenererPdfDevisSoumisAValidation extends GeneratePdf
{
    use FormatageTrait;
    use TableauMargeTableTrait;

    public function copyToDWDevisSoumis(string $fileName)
    {
        $cheminFichierDistant = $this->baseCheminDocuware . 'ORDRE_DE_MISSION/' . $fileName;
        $cheminDestinationLocal = $this->baseCheminDuFichier . 'dit/dev/' . $fileName;
        $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    public function copyToDWFichierDevisSoumis(string $fileName)
    {
        $cheminFichierDistant = $this->baseCheminDocuware . 'DEVIS ATELIER/' . $fileName;
        $cheminDestinationLocal = $this->baseCheminDuFichier . 'dit/dev/fichiers/' . $fileName;
        $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    public function copyToDWFichierDevisSoumisVp(string $fileName)
    {
        $cheminFichierDistant = $this->baseCheminDocuware . 'VERIFICATION_PRIX/' . $fileName;
        $cheminDestinationLocal = $this->baseCheminDuFichier . 'dit/dev/fichiers/' . $fileName;
        $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    /**
     * GENERATION PDF POUR DEVIS VENTE
     */
    function GenererPdfDevisVente(
        DitDevisSoumisAValidationDto $dto,
        array $montantPdf,
        array $quelqueaffichage,
        array $variationPrixRefPiece,
        string $email,
        string $nomFichierCtrl
    ): void {
        $pdf = new HeaderPdf($email);
        $generator = new PdfTableGenerator();

        $pdf->AddPage();

        $pdf->setFont('helvetica', 'B', 17);
        $pdf->Cell(0, 6, 'Validation DEVIS VENTE', 0, 0, 'C', false, '', 0, false, 'T', 'M');
        $pdf->Ln(10, true);

        //L'ENTETE
        $detailsBloc = [
            'Date soumission' => (new \DateTime($dto->dateHeureSoumission))->format('d/m/Y'),
            'Numéro du client' => $dto->infoDit['numero_client'] . ' - ' . $dto->infoDit['libelle_client'],
            'Numéro DIT' => $dto->infoDit['numero_demande_dit'] . ' - ' . $dto->infoDit['objet_demande'],
            'Numéro DEVIS' => $dto->numeroDevis,
            'Version à valider' => $dto->numeroVersion,
            'Sortie magasin' => $quelqueaffichage['sortieMagasin'] ?? 'NON',
            'Achat locaux' => $quelqueaffichage['achatLocaux'] ?? 'NON',
        ];

        $this->addDetailsBlock($pdf, $detailsBloc, 'helvetica', 45, 50, 6, 2, 5);

        $this->generateSeparateLine($pdf);

        // ================================================================================================
        $headerConfig1 = [
            ['key' => 'itv', 'label' => 'ITV', 'width' => 40, 'style' => 'font-weight: bold;'],
            ['key' => 'libelleItv', 'label' => 'Libellé ITV', 'width' => 200, 'style' => 'font-weight: bold; text-align: left;'],
            ['key' => 'nbLigAv', 'label' => 'Nb Lig av', 'width' => 50, 'style' => 'font-weight: bold;'],
            ['key' => 'nbLigAp', 'label' => 'Nb Lig ap', 'width' => 50, 'style' => 'font-weight: bold;'],
            ['key' => 'mttTotalAv', 'label' => 'Mtt Total av', 'width' => 80, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttTotalAp', 'label' => 'Mtt Total ap', 'width' => 80, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'statut', 'label' => 'Statut', 'width' => 40, 'style' => 'font-weight: bold; text-align: center;'],
        ];


        $html1 = $generator->generateTable($headerConfig1, $montantPdf['avantApresVte'], $montantPdf['totalAvantApresVte']);
        $pdf->writeHTML($html1, true, false, true, false, '');

        //===========================================================================================
        //Titre: Controle à faire
        $this->addTitle($pdf, 'Contrôle à faire (par rapport dernière version) :');

        $details = [
            'Nouvelle intervention' => $montantPdf['nombreStatutNouvEtSuppVte']['nbrNouv'],
            'Intervention supprimée' => $montantPdf['nombreStatutNouvEtSuppVte']['nbrSupp'],
            'Nombre ligne modifiée' => $montantPdf['nombreStatutNouvEtSuppVte']['nbrModif'],
            'Montant total modifié' => $this->formatNumber($montantPdf['nombreStatutNouvEtSuppVte']['mttModif']),
        ];

        $this->addSummaryDetails($pdf, $details);

        //==========================================================================================================
        /**=====================================================
         * RECAPITULATION DE DEVIS
         *=====================================================*/
        //Titre
        $this->addTitle($pdf, 'Récapitulation du devis');
        //tableau
        $pdf->setFont('helvetica', '', 12);
        $headerConfig2 = [
            ['key' => 'itv', 'label' => 'ITV', 'width' => 40, 'style' => 'font-weight: bold;'],
            ['key' => 'mttTotal', 'label' => 'Mtt Total', 'width' => 70, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttPieces', 'label' => 'Mtt Pièces', 'width' => 60, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttMo', 'label' => 'Mtt MO', 'width' => 60, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttSt', 'label' => 'Mtt ST', 'width' => 80, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttLub', 'label' => 'Mtt LUB', 'width' => 80, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttAutres', 'label' => 'Mtt Autres', 'width' => 80, 'style' => 'font-weight: bold; text-align: right;'],
        ];

        $html2 = $generator->generateTable($headerConfig2, $montantPdf['recapVte'], $montantPdf['totalRecapVte']);
        $pdf->writeHTML($html2, true, false, true, false, '');
      //=====================================================================================================================
        /**=====================================================
         * tableau de variation de prix des références de pièces
         *=====================================================*/
        //Titre
        $this->addTitle($pdf, 'Variation de prix de pièce par référence');
        // Configuration des entêtes du tableau
        $headerConfig = [
            ['key' => 'cst', 'label' => 'CST', 'width' => 40, 'style' => 'text-align: center; font-weight: bold;'],
            ['key' => 'refPieces', 'label' => 'Ref. Pièce', 'width' => 50, 'style' => 'text-align: left; font-weight: bold;'],
            ['key' => 'pu1', 'label' => 'PU 1', 'width' => 60, 'style' => 'text-align: right; font-weight: bold;'],
            ['key' => 'datePu1', 'label' => 'Date PU 1', 'width' => 60, 'style' => 'text-align: center; font-weight: bold;'],
            ['key' => 'pu2', 'label' => 'PU 2', 'width' => 60, 'style' => 'text-align: right; font-weight: bold;'],
            ['key' => 'datePu2', 'label' => 'Date PU 2', 'width' => 60, 'style' => 'text-align: center; font-weight: bold;'],
            ['key' => 'pu3', 'label' => 'PU 3', 'width' => 60, 'style' => 'text-align: right; font-weight: bold;'],
            ['key' => 'datePu3', 'label' => 'Date PU 3', 'width' => 60, 'style' => 'text-align: center; font-weight: bold;'],
        ];

        // Générer le HTML du tableau
        $htmlVariationPrix = $generator->generateTable($headerConfig, $variationPrixRefPiece, []);
        $pdf->writeHTML($htmlVariationPrix, true, false, true, false, '');
        //====================================================================================================================

        //=====================================================================================================================

        $Dossier = $_ENV['BASE_PATH_FICHIER'] . '/dit/dev/';

        $filePath = $nomFichierCtrl;

        $pdf->Output($Dossier . $filePath, 'F');
    }


    /**
     * Methode de création de pdf pour le devis forfait
     *
     * @param DitDevisSoumisAValidationDto $dto
     * @param array $montantPdf
     * @param array $quelqueaffichage
     * @param array $variationPrixRefPiece
     * @param string $email
     * @return void
     */
    function GenererPdfDevisForfait(DitDevisSoumisAValidationDto $dto, array $montantPdf, array $quelqueaffichage, array $variationPrixRefPiece, string $email, string $nomFichierCtrl)
    {
        // $pdf = new TCPDF();
        $generator = new PdfTableGenerator();
        $pdf = new HeaderPdf($email);

        $pdf->AddPage();

        $pdf->setFont('helvetica', 'B', 17);
        $pdf->Cell(0, 6, 'Validation DEVIS FORFAIT', 0, 0, 'C', false, '', 0, false, 'T', 'M');
        $pdf->Ln(10, true);

        //L'ENTETE
        $detailsBloc = [
            'Date soumission' => (new \DateTime($dto->dateHeureSoumission))->format('d/m/Y'),
            'Numéro du client' => $dto->infoDit['numero_client'] . ' - ' . $dto->infoDit['libelle_client'],
            'Numéro DIT' => $dto->infoDit['numero_demande_dit'] . ' - ' . $dto->infoDit['objet_demande'],
            'Numéro DEVIS' => $dto->numeroDevis,
            'Version à valider' => $dto->numeroVersion,
            'Sortie magasin' => $quelqueaffichage['sortieMagasin'] ?? 'NON',
            'Achat locaux' => $quelqueaffichage['achatLocaux'] ?? 'NON',
        ];

        $this->addDetailsBlock($pdf, $detailsBloc, 'helvetica', 45, 50, 6, 2, 5);
        $this->generateSeparateLine($pdf);

        // ================================================================================================
        //FORFAIT CLIENT
        $this->addTitle($pdf, 'FORFAIT client');
        $pdf->setFont('helvetica', '', 12);
        //FORFAIT CLIENT (tableau)
        $headerConfig1 = [
            ['key' => 'itv', 'label' => 'ITV', 'width' => 40, 'style' => 'font-weight: bold;'],
            ['key' => 'libelleItv', 'label' => 'Libellé ITV', 'width' => 200, 'style' => 'font-weight: bold; text-align: left;'],
            ['key' => 'nbLigAv', 'label' => 'Nb Lig av', 'width' => 50, 'style' => 'font-weight: bold;'],
            ['key' => 'nbLigAp', 'label' => 'Nb Lig ap', 'width' => 50, 'style' => 'font-weight: bold;'],
            ['key' => 'mttTotalAv', 'label' => 'Mtt Total av', 'width' => 80, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttTotalAp', 'label' => 'Mtt Total ap', 'width' => 80, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'statut', 'label' => 'Statut', 'width' => 40, 'style' => 'font-weight: bold; text-align: center;'],
        ];

        $html1 = $generator->generateTable($headerConfig1, $montantPdf['avantApresForfait'], $montantPdf['totalAvantApresForfait']);
        $pdf->writeHTML($html1, true, false, true, false, '');



        // ================================================================================================
        //VARIATION ET MARGE
        $this->addTitle($pdf, 'VARIATION ET MARGE');
        $pdf->setFont('helvetica', '', 12);
        //DETAIL VENTE par rapport au REVIENT (tableau)
        $headerVenteRevient = [
            ['key' => 'description', 'label' => 'Description', 'width' => 150, 'style' => 'font-weight: bold;text-align: left;'],
            ['key' => 'mttTotalAv', 'label' => 'Mtt av', 'width' => 60, 'style' => 'font-weight: bold; text-align: right; '],
            ['key' => 'mttTotalAp', 'label' => 'Mtt ap', 'width' => 60, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'mttEcart', 'label' => 'Mtt écart', 'width' => 60, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'nbecart', 'label' => '% écart', 'width' => 50, 'style' => 'font-weight: bold; text-align: center;'],

        ];
        $totals = [];
        $html2 = $generator->generateTable($headerVenteRevient, $montantPdf['variationVenteForfait'], $totals, true);
        $pdf->writeHTML($html2, true, false, true, false, '');
        //==============================================================================================================
        $this->generateSeparateLine($pdf);
        //===============================================================================================================
        /**
         * TABLEAU DE VARIATION DE PRIX
         */
        $this->addTitle($pdf, 'Variation de prix de vente par référence(3 dernières ventes facturées de plus anciennes au plus récentes)');
        $pdf->setFont('helvetica', '', 12);
        $headerConfig3 = [
            ['key' => 'lineType', 'label' => 'Type de ligne', 'width' => 60, 'style' => 'font-weight: bold;'],
            ['key' => 'cst', 'label' => 'CST', 'width' => 60, 'style' => 'font-weight: bold; text-align: center;'],
            ['key' => 'refPieces', 'label' => 'Réf. Pièce', 'width' => 60, 'style' => 'font-weight: bold; text-align: left;'],
            ['key' => 'pu1', 'label' => 'PU 1', 'width' => 60, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'datePu1', 'label' => 'Date PU 1', 'width' => 60, 'style' => 'font-weight: bold; text-align: center;'],
            ['key' => 'pu2', 'label' => 'PU 2', 'width' => 60, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'datePu2', 'label' => 'Date PU 2', 'width' => 60, 'style' => 'font-weight: bold; text-align: center;'],
            ['key' => 'pu3', 'label' => 'PU 3', 'width' => 60, 'style' => 'font-weight: bold; text-align: right;'],
            ['key' => 'datePu3', 'label' => 'Date PU 3', 'width' => 60, 'style' => 'font-weight: bold; text-align: center;'],
        ];
        // dd($variationPrixRefPiece);
        $totals = [];
        $html3 = $generator->generateTable($headerConfig3, $variationPrixRefPiece, $totals, true);
        $pdf->writeHTML($html3, true, false, true, false, '');


        $Dossier = $_ENV['BASE_PATH_FICHIER'] . '/dit/dev/';

        $filePath = $Dossier . $nomFichierCtrl;

        $pdf->Output($filePath, 'F');
    }

    public function genererPdfVerificationPrix(array $tableauMarge, string $nomFichierCtrl)
    {
        $pdf = new HeaderPdf(null);
        $tableGenerator = new PdfTableGeneratorFlexible();
        $tableGenerator->setOptions([
            'table_attributes' => 'border="0" cellpadding="0" cellspacing="0" align="center" style="font-size: 8px;"',
            'header_row_style' => 'background-color: #D3D3D3;',
            'footer_row_style' => 'background-color: #D3D3D3;'
        ]);

        $pdf->AddPage();

        //==========================================================================================================
        //Titre: Tableaux de marge (CAT, MFN, Autres)
        $this->renderTableauxMarge($pdf, $tableGenerator, $tableauMarge);
        //==========================================================================================================

        $Dossier = $_ENV['BASE_PATH_FICHIER'] . '/dit/dev/';

// Vérifier et créer le dossier si nécessaire
    if (!is_dir($Dossier)) {
        mkdir($Dossier, 0777, true); // true pour créer récursivement
    }

        $filePath = $Dossier . $nomFichierCtrl;

        $pdf->Output($filePath, 'F');
    }
}
