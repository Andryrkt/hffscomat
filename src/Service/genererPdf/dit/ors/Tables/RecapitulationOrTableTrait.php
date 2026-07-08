<?php

namespace App\Service\genererPdf\dit\ors\Tables;

use TCPDF;
use App\Service\genererPdf\PdfTableGeneratorFlexible;

/**===============================================================
 * -------- Pour le tableau Recapitulation de l'OR ------------------
 *================================================================*/
trait RecapitulationOrTableTrait
{
    /**
     * Affiche le titre et le tableau de récapitulation de l'OR.
     */
    private function renderRecapitulationOr(TCPDF $pdf, PdfTableGeneratorFlexible $tableGenerator, array $montantPdf): void
    {
        $this->addTitle($pdf, "Récapitulation de l'OR", 'helvetica', 'B', 10, 'L', 5);

        $pdf->setFont('helvetica', '', 12);
        $html = $tableGenerator->generateTable(
            $this->headerRecapitulationOR(),
            $montantPdf['recapOr'],
            $this->footerRecapitulationOR($montantPdf)
        );

        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Ln(10, true);
    }

    private function headerRecapitulationOR(): array
    {
        return [
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
                'key'          => 'mttTotal',
                'label'        => 'Mtt Total',
                'width'        => 70,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mttPieces',
                'label'        => 'Mtt Pièces',
                'width'        => 60,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mttMo',
                'label'        => 'Mtt MO',
                'width'        => 60,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mttSt',
                'label'        => 'Mtt ST',
                'width'        => 80,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mttLub',
                'label'        => 'Mtt LUB',
                'width'        => 80,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ],
            [
                'key'          => 'mttAutres',
                'label'        => 'Mtt Autres',
                'width'        => 80,
                'style'        => 'font-weight: bold; text-align: center;',
                'header_style' => 'font-weight: bold; text-align: center;',
                'cell_style'   => 'text-align: right;',
                'footer_style' => 'font-weight: bold; text-align: right;',
                'type'         => 'number'
            ]
        ];
    }

    private function footerRecapitulationOR(array $montantPdf): array
    {
        return [
            'itv'              => 'TOTAL',
            'mttTotal'         => $montantPdf['totalRecapOr']['montant_itv'] ?? '',
            'mttPieces'        => $montantPdf['totalRecapOr']['montant_piece'] ?? '',
            'mttMo'            => $montantPdf['totalRecapOr']['montant_mo'] ?? '',
            'mttSt'            => $montantPdf['totalRecapOr']['montant_achats_locaux'] ?? '',
            'mttLub'           => $montantPdf['totalRecapOr']['montant_lubrifiants'] ?? '',
            'mttAutres'        => $montantPdf['totalRecapOr']['montant_frais_divers'] ?? ''
        ];
    }
}
