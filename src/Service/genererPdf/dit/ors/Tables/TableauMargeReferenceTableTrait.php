<?php

namespace App\Service\genererPdf\dit\ors\Tables;

use TCPDF;
use App\Service\genererPdf\PdfTableGeneratorFlexible;

/**============================================================================
 * -------- Pour le tableau de marge ------------------
 *=============================================================================*/
trait TableauMargeReferenceTableTrait
{
    /**
     * Affiche les tableaux de marge (CAT, MFN, Autres) s'ils contiennent des lignes.
     */
    private function renderTableauxMargeReference(TCPDF $pdf, PdfTableGeneratorFlexible $tableGenerator, array $tableauMarge): void
    {
        if (empty($tableauMarge)) {
            return;
        }

        $sections = [
            'tableauMargeCat'    => 'CAT',
            'tableauMargeMfn'    => 'MFN',
            'tableauMargeAutres' => 'AUTRES',
        ];

        $pdf->SetTextColor(0, 0, 0);
        $this->addTitle($pdf, "TABLEAU DE MARGE PAR REFERENCE", 'helvetica', 'B', 10, 'L', 0);
        $pdf->setFont('helvetica', '', 8);

        foreach ($sections as $key => $label) {
            $lignes = $tableauMarge[$key] ?? [];

            $tableGenerator->setOptions([
                'table_attributes' => 'border="0" cellpadding="3" cellspacing="0" align="center" style="font-size: 9px; font-family:helvetica;"',
                'header_row_style' => 'background-color: #ffffff;',
                'footer_row_style' => 'background-color: #ffffff;',
            ]);

            $headerConfig = $this->headerTableauMargeReference($label);
            foreach ($headerConfig as &$col) {
                // Ajouter les bordures pour le header
                $colHeaderStyle = $col['header_style'] ?? $col['style'] ?? '';
                $col['header_style'] = rtrim($colHeaderStyle, '; ') . '; border-top: 0.5px solid #000000; border-bottom: 0.5px solid #000000;';

                // Ajouter les bordures pour le footer
                $colFooterStyle = $col['footer_style'] ?? $col['style'] ?? '';
                $col['footer_style'] = rtrim($colFooterStyle, '; ') . '; border-top: 0.5px solid #000000;';
            }
            unset($col);

            if (!empty($lignes)) {
                $html = $tableGenerator->generateTable($headerConfig, $lignes, []);
                $pdf->writeHTML($html, true, false, true, false, '');
            }
        }
    }

    private function headerTableauMargeReference(string $label = 'CAT'): array
    {
        $formatterPourcentage = function ($value) {
            return $value == 0 ?   '-' : round((float) $value) . '%';
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
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: left; ',
                'footer_style' => 'font-weight: 900;',
                'formatter'    => $formatterDispoStock
            ],
            [
                'key'          => 'nb_ref',
                'label'        => 'Qte stock',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: center; ',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'quantite_demander',
                'label'        => 'Qte dem',
                'width'        => 25,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: center; ',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'reference',
                'label'        => 'Ref',
                'width'        => 60,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'pmp',
                'label'        => 'PMP',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  padding-right:6px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
            ],
            [
                'key'          => 'pv_brut',
                'label'        => 'PV Brut',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
            ],
            [
                'key'          => 'mt_remise',
                'label'        => 'Mt Remise',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
            ],
            [
                'key'          => 'pv_net_remise',
                'label'        => 'PV Net remisé',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
            ],
            [
                'key'          => 'mb',
                'label'        => 'MB',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
            ],
            [
                'key'          => 'mb_p',
                'label'        => '%MB',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter' => $formatterPourcentage,
            ],
            [
                'key'          => 'max_mb',
                'label'        => 'MB+',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
            ],
            [
                'key'          => 'max_mb_p',
                'label'        => '%MB+',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; ',
                'cell_style'   => 'text-align: right;  margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter' => $formatterPourcentage,
            ],
            [
                'key'          => 'min_mb',
                'label'        => 'MB-',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
            ],
            [
                'key'          => 'min_mb_p',
                'label'        => '%MB-',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter' => $formatterPourcentage,
            ],

        ];
    }
}
