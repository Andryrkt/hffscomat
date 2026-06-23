<?php

namespace App\Service\genererPdf\dit\AcBc;

use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Service\genererPdf\GeneratePdf;
use TCPDF;

class GenererPdfAcSoumis extends GeneratePdf
{
    use FormatageTrait;
    private string $baseDirDitFiles;

    public function __construct(string $baseDirDitFiles)
    {
        parent::__construct();

        $this->baseDirDitFiles = $baseDirDitFiles;
    }

    public function copyToDwAcSoumis(string $fileName)
    {
        $this->copyFile("{$this->baseDirDitFiles}/$fileName", "{$this->baseCheminDocuware}BC ATELIER/{$fileName}");
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
        $pdf->Cell(35, 6, "AR-SCT-{$accuseReceptionDto->numeroVersionMaxByDit}", 0, 0, 'L');
        $pdf->setY(25);
        $html = "
        <style>
            table {width: 100%;}
            h1 {text-align: center;font-size: 18px;}
            p {text-align: justify;line-height: 1.5;}
        </style>
        <table>
            <tr>
                <td>
                    <b>SCOMAT</b><br>
                    Grewals Lane, Pailles,<br>
                    Mauritius<br>
                    +(230) 206 0444
                </td>
                <td style=\"text-align: right; font-weight: bold; font-size: 11px;\">{$accuseReceptionDto->dateCreation->format('d/m/Y')}</td>
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
        <p>Madame, Monsieur,</p>
        <p>Nous accusons réception de votre bon de commande, portant sur <br>{$accuseReceptionDto->descriptionBc}.</p>
        <p>Cette commande fait suite à : <br>Devis : {$accuseReceptionDto->numeroDevis} ({$accuseReceptionDto->numeroDit}) du {$accuseReceptionDto->dateDevis->format('d/m/Y')}<br>Montant HT : {$accuseReceptionDto->getMontantDevisFormatted()} {$accuseReceptionDto->devise}. <br>Nous confirmons que votre commande a été enregistrée.</p>
        <p>Pour toute question ou demande d'information complémentaire concernant votre commande ou les travaux à réaliser, nous restons à votre disposition. Vous pouvez nous contacter par email ou par téléphone.</p>
        <p>Nous vous remercions pour votre confiance et restons à votre service pour toute autre demande.</p>
        <p>Dans l'attente, nous vous prions d'agréer, Madame, Monsieur, l'expression de nos salutations distinguées.</p>";

        $pdf->writeHTML($html, true, false, true, false, '');

        // Création du dossier s'il n'existe pas
        if (!is_dir($this->baseDirDitFiles)) mkdir($this->baseDirDitFiles, 0777, true);

        $pdf->Output("{$this->baseDirDitFiles}/{$accuseReceptionDto->nomFichierAcSoumis}", "F");
    }
}
