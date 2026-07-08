<?php

namespace App\Service\genererPdf\dit\ors\Tables;

use TCPDF;
use App\Service\genererPdf\PdfTableGeneratorFlexible;

/**============================================================================
 * -------- Pour le tableau pièces à faible activité d'achat ------------------
 *=============================================================================*/
trait PieceFaibleActiviteTableTrait
{
    /**
     * Affiche l'avertissement et le tableau des pièces à faible activité d'achat, si présentes.
     */
    private function renderPieceFaibleActivite(TCPDF $pdf, PdfTableGeneratorFlexible $tableGenerator, array $pieceFaibleAchat): void
    {
        $pdf->SetTextColor(255, 0, 0);
        $this->addTitle($pdf, empty($pieceFaibleAchat) ? '' : "Attention : les prix des pièces ci-dessous sont susceptibles d’augmenter. Merci de les confirmer auprès du service Magasin.", 'helvetica', 'B', 10, 'L', 1);

        $pdf->SetTextColor(0, 0, 0);
        if (!empty($pieceFaibleAchat)) {
            $pdf->setFont('helvetica', '', 12);
            $html = $tableGenerator->generateTable(
                $this->headerPieceFaibleActivite(),
                $pieceFaibleAchat,
                []
            );

            $pdf->writeHTML($html, true, false, true, false, '');
        }
    }

    private function headerPieceFaibleActivite()
    {
        return [
            [
                'key'          => 'numero_itv',
                'label'        => 'ITV',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'libelle_itv',
                'label'        => 'Libellé ITV',
                'width'        => 150,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: left;',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'constructeur',
                'label'        => 'Const',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'reference',
                'label'        => 'Réfp.',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'designation',
                'label'        => 'Designation',
                'width'        => 150,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: left;',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'pmp',
                'label'        => 'Pmp',
                'width'        => 80,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'date_derniere_cde',
                'label'        => 'Date dern cmd',
                'width'        => 50,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: center;',
                'footer_style' => 'font-weight: bold;',
                'default_value' => 'jamais commandé',
                'type'         => 'date'
            ],
        ];
    }
}
