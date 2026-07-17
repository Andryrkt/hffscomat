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
        $this->addTitle($pdf, "Tableau de marge", 'helvetica', 'B', 10, 'L', 0);

        foreach ($sections as $key => $label) {
            $lignes = $tableauMarge[$key] ?? [];

            
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
            return $value == 0 ?   '-' : round((float) $value, 2) . '%' ;
        };

        $formatterDispoStock = function ($value, $row) {
            return ($row['disponibilite'] ?? '') === 'DISPONIBLE' ? 'Dispo Stock' : 'Non dispo stock';
        };

        return [
            [
                'key'          => 'disponibilite',
                'label'        => $label,
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'font-size: 6px;',
                'footer_style' => 'font-weight: 900;',
                'formatter'    => $formatterDispoStock
            ],
            [
                'key'          => 'nb_ref',
                'label'        => 'Nb Refs',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: center; font-size: 6px;',
                'cell_style'   => 'text-align: center; font-size: 6px;',
                'footer_style' => 'font-weight: 900;',
                'default_value' => '-'
            ],
            [
                'key'          => 'somme_pmp',
                'label'        => 'PMP',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; padding-right:6px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-'
            ],
            [
                'key'          => 'somme_pxvteht',
                'label'        => 'PV Brut',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-'
            ],
            [
                'key'          => 'somme_remise',
                'label'        => 'Mt Remise',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-'
            ],
            [
                'key'          => 'somme_pxvte_remise',
                'label'        => 'PV Net remisé',
                'width'        => 50,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-'
            ],
            [
                'key'          => 'somme_marge_brute',
                'label'        => 'MB',
                'width'        => 45,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'default_value' => '-'
            ],
            [
                'key'          => 'pct_marge_brute',
                'label'        => '%MB',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter'    => $formatterPourcentage,
                'default_value' => '-'
            ],
            [
                'key'          => 'pct_mb_max',
                'label'        => '%MB+',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter'    => $formatterPourcentage,
                'default_value' => '-'
            ],
            [
                'key'          => 'pct_mb_min',
                'label'        => '%MB-',
                'width'        => 30,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold; text-align: right; font-size: 6px;',
                'cell_style'   => 'text-align: right; font-size: 6px; margin-right:2px;',
                'footer_style' => 'font-weight: 900;',
                'type'         => 'number',
                'formatter'    => $formatterPourcentage,
                'default_value' => '-'
            ],
        ];
    }
}
