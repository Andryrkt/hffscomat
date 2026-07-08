<?php

namespace App\Service\genererPdf\dit\ors\Tables;

/**===============================================================
 * -------- Pour le tableau Situation de l'OR ------------------
 *================================================================*/
trait SituationOrTableTrait
{
    private function headerSituationOr(): array
    {
        return  [
            [
                'key'          => 'itv',
                'label'        => 'ITV',
                'width'        => 40,
                'style'        => 'font-weight: 900;',
                'header_style' => 'font-weight: 900;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'libelleItv',
                'label'        => 'Libellé ITV',
                'width'        => 150,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: left;',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'datePlanning',
                'label'        => 'Date pla',
                'width'        => 50,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => 'text-align: left;',
                'footer_style' => 'font-weight: bold;',
                'type'         => 'date'
            ],
            [
                'key'          => 'nbLigAv',
                'label'        => 'Nb Lig av',
                'width'        => 50,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: bold;'
            ],
            [
                'key'          => 'nbLigAp',
                'label'        => 'Nb Lig ap',
                'width'        => 50,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: bold;'
            ],
            [
                'key'          => 'mttTotalAv',
                'label'        => 'Mtt Total av',
                'width'        => 80,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mttTotalAp',
                'label'        => 'Mtt total ap',
                'width'        => 80,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ],
            [
                'key'          => 'statut',
                'label'        => 'Statut',
                'width'        => 40,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: left;',
                'footer_style' => 'font-weight: bold; text-align: center;',
                'styler'       => function ($value, $row) {
                    switch ($value) {
                        case 'Supp':
                            return 'background-color: #FF0000;';
                        case 'Modif':
                            return 'background-color: #FFFF00;';
                        case 'Nouv':
                            return 'background-color: #00FF00;';
                        default:
                            return '';
                    }
                }
            ]
        ];
    }

    private function footerSituationOr(array $montantPdf): array
    {
        return [
            'itv'              => '',
            'libelleItv'       => '',
            'datePlanning'     => 'TOTAL',
            'nbLigAv'          => $montantPdf['totalAvantApres']['totalNbLigAv'] ?? '',
            'nbLigAp'          => $montantPdf['totalAvantApres']['totalNbLigAp'] ?? '',
            'mttTotalAv'       => $montantPdf['totalAvantApres']['totalMttTotalAv'] ?? '',
            'mttTotalAp'       => $montantPdf['totalAvantApres']['totalMttTotalAp'] ?? '',
            'statut'           => ''
        ];
    }

    private function getDynamicStyle($key, $value)
    {
        $styles = '';
        if ($key === 'statut') {
            switch ($value) {
                case 'Supp':
                    $styles .= 'background-color: #FF0000;';
                    break;
                case 'Modif':
                    $styles .= 'background-color: #FFFF00;';
                    break;
                case 'Nouv':
                    $styles .= 'background-color: #00FF00;';
                    break;
            }
        }
        return $styles;
    }
}
