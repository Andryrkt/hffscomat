<?php

namespace App\Service\genererPdf\dit\ors\Tables;

/**============================================================================
 * -------- Pour le tableau de marge ------------------
 *=============================================================================*/
trait TableauMargeTableTrait
{
    private function headerTableauMarge(string $label = 'CAT'): array
    {
        $formatterPourcentage = function ($value) {
            return  round($value) . '%';
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
                'default_value' => 'Dispo Stock'
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
                'key'          => 'pmp',
                'label'        => 'PMP',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
            ],
            [
                'key'          => 'pv_brut',
                'label'        => 'PV Brut',
                'width'        => 40,
                'style'        => 'font-weight: bold;',
                'header_style' => 'font-weight: bold;',
                'cell_style'   => '',
                'footer_style' => 'font-weight: 900;'
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
