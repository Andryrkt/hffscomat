<?php

namespace App\Dto\Magasin\OrBc;

class CmdeATraitrerMagasinSearchDto
{
    public function __construct()
    {
        $this->dateDebut = new \DateTime('-30 days');
        $this->dateFin   = new \DateTime();
    }
    public ?\Datetime $dateDebut = null;
    public ?\DateTime $dateFin = null;

    public ?string $type_doc = null;
    public ?string $reference = null;
    public ?string $designation = null;

    public ?string $constructeur = null;

    public ?string $numero_doc = null;
}
