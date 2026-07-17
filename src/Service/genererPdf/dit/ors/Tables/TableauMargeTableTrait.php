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
            'tableauMargeAutres' => 'AUTRES',
        ];

        $pdf->SetTextColor(0, 0, 0);
        $this->addTitle($pdf, "TABLEAU DE MARGE", 'arial', 'B', 10, 'L', 0);
        $pdf->setFont('arial', '', 9);

        foreach ($sections as $key => $label) {
            $lignes = $tableauMarge[$key] ?? [];

            $tableGenerator->setOptions([
                'table_attributes' => 'border="0" cellpadding="3" cellspacing="0" align="center" style="font-size: 9px; font-family:arial;"',
                'header_row_style' => 'border-top: 1px solid #000000; border-bottom: 1px solid #000000; background-color: #ffffff;',
                'footer_row_style' => 'border-top: 1px solid #000000; border-bottom: 1px solid #000000; background-color: #ffffff;',
            ]);
            $html = $tableGenerator->generateTable($this->headerTableauMarge($label), $this->normaliserLignesMarge($lignes), []);
            $pdf->writeHTML($html, true, false, true, false, '');
        }
    }

    /**
     * Garantit une ligne "Dispo Stock" et une ligne "Non dispo stock" par tableau,
     * même si l'une des deux ne remonte aucune donnée de la requête.
     */
    private function normaliserLignesMarge(array $lignes): array
    {
        $ligneVide = [
            'nb_ref'              => '',
            'somme_pmp'           => '',
            'somme_pxvteht'       => '',
            'somme_remise'        => '',
            'somme_pxvte_remise'  => '',
            'somme_marge_brute'   => '',
            'pct_marge_brute'     => '',
            'pct_mb_max'          => '',
            'pct_mb_min'          => '',
        ];

        $parDisponibilite = [];
        foreach ($lignes as $ligne) {
            $parDisponibilite[$ligne['disponibilite']] = $ligne;
        }

        return [
            array_merge($ligneVide, $parDisponibilite['DISPONIBLE'] ?? [], ['disponibilite' => 'DISPONIBLE']),
            array_merge($ligneVide, $parDisponibilite['NON_DISPONIBLE'] ?? [], ['disponibilite' => 'NON_DISPONIBLE']),
        ];
    }

    private function headerTableauMarge(string $label): array
    {
        $formatterPourcentage = function ($value) {
            return $value == 0 ?   '-' : round((float) $value, 2) . '%';
        };

        $formatterDispoStock = function ($value, $row) {
            return ($row['disponibilite'] ?? '') === 'DISPONIBLE' ? 'Dispo Stock' : 'Non dispo stock';
        };

        // Trace une ligne entre la ligne "Dispo Stock" et la ligne "Non dispo stock".
        $stylerSeparateur = function ($value, $row) {
            return ($row['disponibilite'] ?? '') === 'DISPONIBLE' ? 'border-bottom: 0.5px solid #D3D3D3;' : '';
        };

        return [
            [
                'key'          => 'disponibilite',
                'label'        => $label,
                'width'        => 70,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: left;',
                'footer_style' => 'font-weight: 900;',
                'formatter'    => $formatterDispoStock,
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'nb_ref',
                'label'        => 'Nb Refs',
                'width'        => 40,
                'style'        => '',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'font-weight: normal; text-align: center;',
                'footer_style' => 'font-weight: 900;',
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'somme_pmp',
                'label'        => 'PMP',
                'width'        => 60,
                'style'        => '',
                'header_style' => 'font-weight: bold; text-align: right; ',
                'cell_style'   => 'font-weight: normal; text-align: right; ',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'somme_pxvteht',
                'label'        => 'PV Brut',
                'width'        => 60,
                'style'        => '',
                'header_style' => 'font-weight: bold; text-align: right; ',
                'cell_style'   => 'font-weight: normal; text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'somme_remise',
                'label'        => 'Mt Remise',
                'width'        => 60,
                'style'        => '',
                'header_style' => 'font-weight: bold; text-align: right;',
                'cell_style'   => 'font-weight: normal; text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'somme_pxvte_remise',
                'label'        => 'PV Net remisé',
                'width'        => 70,
                'style'        => '',
                'header_style' => 'font-weight: bold; text-align: right;',
                'cell_style'   => 'font-weight: normal; text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'somme_marge_brute',
                'label'        => 'MB',
                'width'        => 60,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'pct_marge_brute',
                'label'        => '%MB',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right;',
                'cell_style'   => 'font-weight: bold; text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter'    => $formatterPourcentage,
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'pct_mb_max',
                'label'        => '%MB+',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right;',
                'cell_style'   => 'font-weight: bold; text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter'    => $formatterPourcentage,
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
            [
                'key'          => 'pct_mb_min',
                'label'        => '%MB-',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; ',
                'cell_style'   => 'font-weight: bold; text-align: right;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter'    => $formatterPourcentage,
                'default_value' => '-',
                'styler'       => $stylerSeparateur
            ],
        ];
    }
}
