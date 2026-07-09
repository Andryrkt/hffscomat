<?php

namespace App\Service\genererPdf\dit\ors\Tables;

use TCPDF;
use App\Service\genererPdf\PdfTableGeneratorFlexible;

/**============================================================================
 * -------- Pour le tableau de marge ------------------
 *=============================================================================*/
trait TableauMargeTableTrait
{
    /**
     * Affiche les tableaux de marge (CAT, MFN, Autres) s'ils contiennent des lignes.
     */
    private function renderTableauxMarge(TCPDF $pdf, PdfTableGeneratorFlexible $tableGenerator, array $tableauMarge): void
    {
        if (empty($tableauMarge)) {
            return;
        }

        $sections = [
            'tableauMargeCat'    => 'CAT',
            'tableauMargeMfn'    => 'MFN',
            'tableauMargeAutres' => 'Autres',
        ];

        $lineBreak = 0;
        foreach ($sections as $key => $label) {
            $lignes = $tableauMarge[$key] ?? [];

            $pdf->SetTextColor(0, 0, 0);
            $this->addTitle($pdf, empty($lignes) ? '' : "Tableau de marge pour pièce '$label' .", 'helvetica', 'B', 10, 'L', $lineBreak);
            $lineBreak = 1;

            if (!empty($lignes)) {
                $pdf->setFont('helvetica', '', 12);
                $html = $tableGenerator->generateTable($this->headerTableauMarge($label), $lignes, []);
                $pdf->writeHTML($html, true, false, true, false, '');
            }
        }
    }

    private function headerTableauMarge(string $label = 'CAT'): array
    {
        $formatterPourcentage = function ($value) {
            return  round($value) . '%';
        };

        $formatterDispoStock = function ($value, $row) {
            return (int) ($row['nb_ref'] ?? 0) === 0 ? 'Non dispo stock' : 'Dispo stock';
        };

        return [
            [
                'key'          => '',
                'label'        => $label,
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: center;',
                'footer_style' => 'font-weight: 900;',
                'formatter'    => $formatterDispoStock
            ],
            [
                'key'          => 'nb_ref',
                'label'        => 'Nb refs',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: center;',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'reference',
                'label'        => 'Ref',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'pmp',
                'label'        => 'PMP',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'pv_brut',
                'label'        => 'PV Brut',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mt_remise',
                'label'        => 'Mt Remise',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'pv_net_remise',
                'label'        => 'PV Net remisé',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mb',
                'label'        => 'MB',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mb_p',
                'label'        => '%MB',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter' => $formatterPourcentage,
            ],
            [
                'key'          => 'max_mb',
                'label'        => 'MB+',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'max_mb_p',
                'label'        => '%MB+',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter' => $formatterPourcentage,
            ],
            [
                'key'          => 'min_mb',
                'label'        => 'MB-',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number'
            ],
            [
                'key'          => 'min_mb_p',
                'label'        => '%MB-',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter' => $formatterPourcentage,
            ],

        ];
    }
}
