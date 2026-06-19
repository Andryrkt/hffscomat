<?php

namespace App\Service\genererPdf\dit\AcBc;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Service\genererPdf\GeneratePdf;
use TCPDF;

class GenererPdfAcSoumis extends GeneratePdf
{
    use FormatageTrait;

    public function copyToDwAcSoumis(string $fileName)
    {
        $cheminFichierDistant = $this->baseCheminDocuware . "BC ATELIER/$fileName";
        $cheminDestinationLocal = $this->baseCheminDuFichier . "dit/ac_bc/$fileName";
        $this->copyFile($cheminDestinationLocal, $cheminFichierDistant);
    }

    /** 
     * Méthode pour génerer le PDF de l'accusé de réception
     * 
     * @param AccuseReceptionDto $accuseReceptionDto
     * 
     * @return void
     */
    public function genererPdfAc(AccuseReceptionDto $accuseReceptionDto): void
    {
        $pdf = new TCPDF();

        $pdf->SetMargins(25, 20, 25);
        $pdf->SetAutoPageBreak(TRUE, 20);

        $pdf->setPrintHeader(false);
        $pdf->setPrintFooter(false);

        $pdf->AddPage();

        $pdf->Image("{$_ENV['BASE_PATH_LONG']}/Views/assets/logoHFF.jpg", 27, 10, 40, 0, 'jpg');
        $pdf->SetFont('helvetica', '', 10);

        $pdf->SetTextColor(0, 0, 0);
        $pdf->SetXY(11, 2);
        $pdf->Cell(35, 6, "AR-HFF-{$accuseReceptionDto->numeroVersionMaxByDit}", 0, 0, 'L');
        $pdf->setY(20);
        $html = "
        <style>
            table {
                width: 100%;
            }
            .left {
                text-align: left;
                font-size: 11px;
            }
            .right {
                text-align: right;
                font-size: 11px;
            }
            h1 {
                text-align: center;
                font-size: 18px;
            }
            p {
                text-align: justify;
                line-height: 1.5;
            }
            .footer {
                text-align: center;
                font-size: 10px;
            }
        </style>
        <table>
            <tr>
                <td class='left'>
                    <b>HENRI FRAISE FILS & CIE</b><br>
                    BP 28, 90 Làlana Ravoninahitriniarivo,<br>
                    Antananarivo 101 - Madagascar<br>
                    (+261) 20 22 227 21
                </td>
                <td class='right'>
                    <b>{$accuseReceptionDto->dateCreation->format('d/m/Y')}</b>
                </td>
            </tr>
        </table>

        <h1>ACCUSE DE RECEPTION</h1>

        <p>
            <b>A l'attention de {$accuseReceptionDto->nomClient} </b> <br>
            <b>{$accuseReceptionDto->emailClient}</b><br>
        </p>
        <p>
            <b>Objet : Accusé de réception du bon de commande </b> <br>
            <b>N°BC : </b> {$accuseReceptionDto->numeroBc} <br>
            <b>Date BC : </b> {$accuseReceptionDto->dateBc->format('d/m/Y')}
        </p>
        <p>
            Madame, Monsieur,<br><br>
            Nous accusons réception de votre bon de commande, portant sur <br>{$accuseReceptionDto->descriptionBc}.<br><br>
            Cette commande fait suite à : <br>
            Devis : {$accuseReceptionDto->numeroDevis} ({$accuseReceptionDto->numeroDit}) du {$accuseReceptionDto->dateDevis->format('d/m/Y')}<br>
            Montant HT : {$accuseReceptionDto->getMontantDevisFormatted()} {$accuseReceptionDto->devise}. <br>
            Nous confirmons que votre commande a été enregistrée.<br><br>
            Pour toute question ou demande d'information complémentaire concernant votre commande ou les travaux à réaliser, nous restons à votre disposition. Vous pouvez nous contacter par email ou par téléphone.<br><br>
            Nous vous remercions pour votre confiance et restons à votre service pour toute autre demande.<br><br>
            Dans l'attente, nous vous prions d'agréer, Madame, Monsieur, l'expression de nos salutations distinguées.<br>
        </p>";

        // Écriture du contenu HTML dans le PDF
        $pdf->writeHTML($html, true, false, true, false, '');

        $pdf->Image("{$_ENV['BASE_PATH_LONG']}/Views/assets/footer.png", 27, 265, 160, '', 'PNG');

        $pdf->Output("{$_ENV['BASE_PATH_FICHIER']}/dit/ac_bc/{$accuseReceptionDto->numeroDit}/{$accuseReceptionDto->nomFichierAcSoumis}", 'I');
    }
}
