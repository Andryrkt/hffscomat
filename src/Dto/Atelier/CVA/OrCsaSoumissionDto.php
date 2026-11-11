<?php

namespace App\Dto\Atelier\CVA;

class OrCsaSoumissionDto
{
    // Section 1 - Client
    public string $nomClient           = ''; // cbse_nomcli
    public string $adresseClient       = ''; // cbse_adr1
    public string $bureauPostal        = ''; // cbse_bureauptt
    public string $pays                = 'MAURITUS'; // valeur par défaut

    // Section 2 - Proforma Invoice
    public string $numeroOr            = ''; // seor_numor
    public string $numeroClient        = ''; // seor_numcli
    public string $dateOr              = ''; // seor_dateor formatée JJ/MM/AAAA

    // Section 3 - Références
    public string $referenceClient     = ''; // seor_lib
    public string $refDemande          = ''; // seor_refdem (N° DIT)

    // Section 4 - Dept / Sort
    public string $codeService         = ''; // seor_servcrt
    public string $libelleService      = ''; // atab_lib
    public string $natureOperation     = ''; // seor_natop
    public string $libelleNatureOperation = ''; // lib_natop

    // Section 5 - Totaux
    public float  $montantExclVat      = 0.0;
    public float  $montantIncVat       = 0.0;

    // Langue (pour le nom du dossier DW)
    public string $langueIps           = 'F';

    public string $operator           = '';
    public string $quoteNo            = '';
    public string $salesman           = '';
    public string $segmentDescription = '';
    public array $lignes = [];
}
