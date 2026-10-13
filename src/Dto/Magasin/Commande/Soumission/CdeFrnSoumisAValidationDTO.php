<?php

namespace App\Dto\Magasin\Commande\Soumission;

class CdeFrnSoumisAValidationDTO
{
    public ?string    $numCde       = null;
    public ?string    $codeFrn      = null;
    public ?string    $libelleFrn   = null;
    public ?int       $numVersion   = null;
    public ?string    $statut       = null;
    public ?string    $urlPDFCourt  = null;
    public ?string    $urlPDFLong   = null;
    public ?string    $token        = null;
    public bool       $pdfDeposerDw = false;
    public ?\DateTime $dateDepotDw  = null;

    public function __construct()
    {
        $this->statut = 'Soumis à validation';
    }
}
