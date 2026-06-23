<?php

namespace App\Service\genererPdf\dit;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\DitDto;
use App\Service\genererPdf\GeneratePdf;
use TCPDF;

class GenererPdfDit extends GeneratePdf
{
    use FormatageTrait;

    public function copyToDOCUWARE(string $fileName, string $numDit): bool
    {
        $cheminFichierDistant = rtrim($this->baseCheminDocuware, '/\\') . '/DIT/' . $fileName;
        $cheminDestinationLocal = rtrim($this->baseCheminDuFichier, '/\\') . '/dit/' . $numDit . '/' . $fileName;
        return $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    /**
     * GENERER PDF DEMANDE D'INTERVENTION
     *
     * @return void
     */
    public function genererPdfDit(DitDto $dto, array $historiqueMateriel, string $filePath)
    {
        $pdf = new TCPDF();

        $pdf->AddPage();

        $pdf->setFont('helvetica', 'B', 14);
        $pdf->setAbsY(11);
        $logoPath =  $_ENV['BASE_PATH_LONG'] . '/Views/assets/logoHff.jpg';
        $pdf->Image($logoPath, '', '', 45, 12);
        $pdf->setAbsX(55);
        //$pdf->Cell(45, 12, 'LOGO', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Cell(110, 6, 'DEMANDE D\'INTERVENTION - SCT', 0, 0, 'C', false, '', 0, false, 'T', 'M');


        $pdf->setAbsX(170);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->Cell(35, 6, $dto->numeroDemandeIntervention, 0, 0, 'L', false, '', 0, false, 'T', 'M');

        $pdf->Ln(6, true);

        $pdf->setFont('helvetica', 'B', 12);
        $pdf->setAbsX(55);

        $pdf->cell(110, 6, $dto->typeDocument, 0, 0, 'C', false, '', 0, false, 'T', 'M');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->setAbsX(170);
        $pdf->cell(35, 6, 'Le : ' . (new \DateTime($dto->dateDemande))->format('d/m/Y'), 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);

        //========================================================================================
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);
        $pdf->cell(25, 6, 'Objet :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 9);
        $pdf->cell(0, 6, $dto->objetDemande, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);

        $pdf->setFont('helvetica', 'B', 10);
        $pdf->cell(25, 6, 'Détails :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setFont('helvetica', '', 9);
        $pdf->MultiCell(164, 100, $dto->detailDemande, 1, '', 0, 0, '', '', true);
        //$pdf->cell(165, 10, , 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(3, true);
        $pdf->setAbsY(133);

        $pdf->setFont('helvetica', 'B', 10);
        $pdf->MultiCell(25, 6, "Catégorie :", 0, 'L', false, 0);

        $pdf->cell(55, 6, $dto->categorieDemande, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(95);
        $pdf->MultiCell(40, 6, "Client Sous Contrat :", 0, 'L', false, 0);
        $pdf->cell(15, 6, $dto->clientSousContrat, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(155);
        $pdf->cell(30, 6, 'Devis demandé :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->demandeDevis, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(6, true);
        //=========================================================================================================
        /** INTERVENTION */

        $this->renderTextWithLine($pdf, 'Intervention');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);

        $pdf->cell(25, 6, 'Date prévue :', 0, 0, '', false, '', 0, false, 'T', 'M');
        if ($dto->datePrevueTravaux !== null && !empty($dto->datePrevueTravaux)) {
            $pdf->cell(50, 6, $dto->datePrevueTravaux->format('d/m/Y'), 1, 0, '', false, '', 0, false, 'T', 'M');
        } else {
            $pdf->cell(50, 6, $dto->datePrevueTravaux, 1, 0, '', false, '', 0, false, 'T', 'M');
        }
        $pdf->setAbsX(130);
        $pdf->cell(20, 6, 'Urgence :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->worNiveauUrgence, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(6, true);
        //===================================================================================================
        /**AGENCE-SERVICE */

        $this->renderTextWithLine($pdf, 'Agence - Service');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);

        $pdf->cell(25, 6, 'Emetteur :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(50, 6, $dto->agenceServiceEmetteur, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(130);
        $pdf->cell(20, 6, 'Débiteur :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->agenceServiceDebiteur, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(6, true);
        //====================================================================================================
        /**REPARATION */

        $this->renderTextWithLine($pdf, 'Réparation');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);

        $pdf->cell(25, 6, 'Type :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(30, 6, $dto->internetExterne, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(70);
        $pdf->cell(23, 6, 'Réparation :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(35, 6, $dto->typeReparation, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(130);
        $pdf->cell(25, 6, 'Réalisé par :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->reparationRealise, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(6, true);
        //===================================================================================================
        /**CLIENT */

        $this->renderTextWithLine($pdf, 'Client');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);

        $pdf->cell(25, 6, 'Numéro :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(50, 6, $dto->numeroClient, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(90);
        $pdf->cell(15, 6, 'Nom :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $nomClient = $dto->nomClient;
        if (mb_strlen($nomClient) > 40) {
            $nomClient = mb_substr($nomClient, 0, 37) . '...';
        }
        $pdf->cell(0, 6, $nomClient, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);

        $pdf->cell(25, 6, 'N° tel :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(50, 6, $dto->numeroTel, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(90);
        $pdf->cell(15, 6, 'Email :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->mailClient, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);


        //========================================================================================================
        /** CARACTERISTIQUE MATERIEL */

        $this->renderTextWithLine($pdf, 'Caractéristiques du matériel');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);


        $pdf->cell(25, 6, 'Désignation :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(70, 6, $dto->designation, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(140);
        $pdf->cell(20, 6, 'N° Série :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->numSerie, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);


        $pdf->cell(25, 6, 'N° Parc :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(30, 6, $dto->numParc, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(70);
        $pdf->cell(21, 6, 'Modèle :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(37, 6, $dto->modele, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(130);
        $pdf->cell(30, 6, 'Constructeur :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->constructeur, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);

        $pdf->cell(25, 6, 'Casier :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $casier = $dto->casier;
        if (mb_strlen($casier) > 17) {
            $casier = mb_substr($casier, 0, 15) . '...';
        }
        $pdf->cell(40, 6, $casier, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(80);
        $pdf->cell(23, 6, 'Id Matériel :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(20, 6, $dto->idMateriel, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(130);
        $pdf->cell(33, 6, 'livraison partielle :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->livraisonPartiel, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(6, true);

        //===================================================================================================
        /** ETAT MACHINE */

        $this->renderTextWithLine($pdf, 'Etat machine');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);

        $pdf->MultiCell(25, 6, "Heures :", 0, 'L', false, 0);
        $pdf->cell(30, 6, $dto->heure, 1, 0, '', false, '', 0, false, 'T', 'M');

        $pdf->setAbsX(135);
        $pdf->cell(25, 6, 'Kilométrage :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->km, 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(6, true);
        //========================================================================================
        /** BILANT FINANCIERE */

        $this->renderTextWithLine($pdf, 'Valeur (MGA)');

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setFont('helvetica', 'B', 10);



        $pdf->MultiCell(43, 6, "Cout d'Acquisition :", 0, 'L', false, 0);
        $pdf->cell(30, 6, $this->formatNumberDecimal($dto->coutAcquisition), 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->MultiCell(40, 6, "Amort :", 0, 'R', false, 0);
        $pdf->cell(30, 6, $this->formatNumberDecimal($dto->amortissement), 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(155);
        $pdf->cell(15, 6, 'Vnc :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $this->formatNumberDecimal($dto->valeurNetComptable), 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);

        $pdf->MultiCell(43, 6, "Charge d'entretien :", 0, 'L', false, 0);
        $pdf->cell(30, 6, $this->formatNumberDecimal($dto->chargeEntretient), 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->MultiCell(40, 6, "Charge Locative :", 0, 'R', false, 0);
        $pdf->cell(30, 6, $this->formatNumberDecimal($dto->chargeLocative), 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->setAbsX(155);
        $pdf->cell(15, 6, 'CA :', 0, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->cell(0, 6, $dto->modele == 'IMMODIV' ? 0 : $this->formatNumberDecimal($dto->chiffreAffaire), 1, 0, '', false, '', 0, false, 'T', 'M');
        $pdf->Ln(7, true);

        $pdf->MultiCell(43, 6, "Résultat d'exploitation : ", 0, 'L', false, 0);
        $pdf->cell(30, 6, $dto->modele == 'IMMODIV' ? 0 : $this->formatNumberDecimal($dto->resultatExploitation), 1, 0, '', false, '', 0, false, 'T', 'M');

        //=========================================================================================

        // entête email
        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetFont('helvetica', 'BI', 10);
        $pdf->SetXY(110, 2);
        $pdf->Cell(35, 6, "email : " . $dto->mailDemandeur, 0, 0, 'L');

        //=================================================================================================
        /**DEUXIEME PAGE */
        if (!in_array((int)$dto->idMateriel, [14571, 7669, 7670, 7671, 7672, 7673, 7674, 7675, 7677, 9863, 7711])) {
            $this->affichageHistoriqueMateriel($pdf, $historiqueMateriel);
        }


        $pdf->Output($filePath, 'F');
    }

    private function affichageHistoriqueMateriel(TCPDF $pdf, array $historiqueMateriel)
    {
        $pdf->AddPage();

        $header1 = ['Agences', 'Services', 'Date', 'numor', 'interv', 'commentaire', 'pos', 'Sommes'];

        // Commencer le tableau HTML
        $html = '<h2 style="text-align:center">HISTORIQUE DE REPARATION</h2>';

        $html .= '<table border="0" cellpadding="0" cellspacing="0" align="center" style="font-size: 8px; ">';

        $html .= '<thead>';
        $html .= '<tr>';
        foreach ($header1 as $key => $value) {
            if ($key === 0) {
                $html .= '<th style="width: 40px; font-weight: 900;" >' . $value . '</th>';
            } elseif ($key === 1) {
                $html .= '<th style="width: 40px; font-weight: bold;" >' . $value . '</th>';
            } elseif ($key === 2) {
                $html .= '<th style="width: 50px; font-weight: bold;" >' . $value . '</th>';
            } elseif ($key === 3) {
                $html .= '<th style="width: 50px; font-weight: bold;" >' . $value . '</th>';
            } elseif ($key === 4) {
                $html .= '<th style="width: 30px; font-weight: bold;" >' . $value . '</th>';
            } elseif ($key === 5) {
                $html .= '<th style="width: 250px; font-weight: bold;" >' . $value . '</th>';
            } elseif ($key === 6) {
                $html .= '<th style="width: 30px; font-weight: bold; text-align: center;" >' . $value . '</th>';
            } elseif ($key === 7) {
                $html .= '<th style="width: 50px; font-weight: bold;" >' . $value . '</th>';
            } else {
                $html .= '<th >' . $value . '</th>';
            }
        }
        $html .= '</tr>';
        $html .= '</thead>';
        $html .= '<tbody>';
        // Ajouter les lignes du tableau
        foreach ($historiqueMateriel as $row) {
            $html .= '<tr>';
            foreach ($row as $key => $cell) {

                if ($key === 'codeagence') {
                    $html .= '<td style="width: 40px"  >' . $cell . '</td>';
                } elseif ($key === 'codeservice') {
                    $html .= '<td style="width: 40px"  >' . $cell . '</td>';
                } elseif ($key === 'datedebut') {
                    $html .= '<td style="width: 50px"  >' . $cell . '</td>';
                } elseif ($key === 'numeroor') {
                    $html .= '<td style="width: 50px"  >' . $cell . '</td>';
                } elseif ($key === 'numerointervention') {
                    $html .= '<td style="width: 30px"  >' . $cell . '</td>';
                } elseif ($key === 'commentaire') {
                    $html .= '<td style="width: 250px; text-align: left;"  >' . $cell . '</td>';
                } elseif ($key === 'somme') {
                    $html .= '<td style="width: 50px; text-align: right;"  >' . $cell . '</td>';
                } elseif ($key === 'pos') {
                    $html .= '<td style="width: 30px; text-align: right; text-align: center;"  >' . $cell . '</td>';
                }
                // else {
                //     $html .= '<td  >' . $cell . '</td>';
                // }
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';


        $pdf->writeHTML($html, true, false, true, false, '');
    }
}
