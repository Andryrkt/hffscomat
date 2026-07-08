<?php

namespace App\Service\genererPdf\dit\ors\Tables;

/**============================================================================
 * -------- Pour le tableau pièces à faible activité d'achat ------------------
 *=============================================================================*/
trait PieceFaibleActiviteTableTrait
{
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
