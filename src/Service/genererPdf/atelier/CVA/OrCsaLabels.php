<?php


namespace App\Service\genererPdf\atelier\CVA;

/**
 * Libellés du PDF "Validation OR - CVA".
 * - langueUps = "A" => anglais
 * - sinon           => français
 */
final class OrCsaLabels
{
    private const FR = [
        'proforma_invoice'   => 'FACTURE PROFORMA N° WO',
        'customer_no'        => 'N° Client:    ',
        'date'               => 'Date',
        'page'               => 'Page',
        'customer_reference' => 'Référence client',
        'request_reference'  => 'Référence demandée',
        'operator'           => 'Opérateur',
        'quote_no'           => 'N° Devis',
        'salesman'           => 'Commercial',
        'segment'            => 'Segment',
        'quantity'           => 'Quantité',
        'reference'          => 'Référence',
        'description'        => 'Description',
        'unit_price'         => 'Prix unitaire',
        'net_amt'            => 'Montant net',
        'dept_credited'      => 'Serv. crédité',
        'sort'               => 'Nature',
        'total_amount'       => 'Montant total',
        'total_segment'      => 'Total Segment',
        'total_excl_vat'     => 'Total HT',
        'vat_total'          => 'Total TVA',
        'total_incl_vat'     => 'TOTAL TTC',
        'currency'           => 'Devise',
    ];

    private const EN = [
        'proforma_invoice'   => 'PROFORMA INVOICE No. WO',
        'customer_no'        => 'Customer No. :    ',
        'date'               => 'Date',
        'page'               => 'Page',
        'customer_reference' => 'Customer reference',
        'request_reference'  => 'Request reference',
        'operator'           => 'Operator',
        'quote_no'           => 'Quote No.',
        'salesman'           => 'Salesman',
        'segment'            => 'Segment',
        'quantity'           => 'Quantity',
        'reference'          => 'Reference',
        'description'        => 'Description',
        'unit_price'         => 'Unit price',
        'net_amt'            => 'Net Amt',
        'dept_credited'      => 'Dept. credited',
        'sort'               => 'Sort',
        'total_amount'       => 'Total amount',
        'total_segment'      => 'Total Segment',
        'total_excl_vat'     => 'Total excl. VAT',
        'vat_total'          => 'VAT Total',
        'total_incl_vat'     => 'TOTAL incl. VAT',
        'currency'           => 'Currency',
    ];

    /**
     * Retourne le jeu de libellés correspondant au code langue.
     */
    public static function forLangue(string $langueUps): array
    {
        return $langueUps === 'A' ? self::EN : self::FR;
    }
}
