<?php

namespace App\Dto\Magasin\OrBc;

class CmdeATraitrerMagasinSearchDto
{
    public ?\Datetime $dateDebut = null;

    public ?\DateTime $dateFin = null;

    public ?string $type_doc = null;
    public ?string $reference = null;
    public ?string $designation = null;

    public ?string $constructeur = null;

    public ?string $numero_doc = null;
}
