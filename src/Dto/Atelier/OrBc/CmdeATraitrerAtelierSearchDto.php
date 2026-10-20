<?php

namespace App\Dto\Atelier\OrBc;

class CmdeATraitrerAtelierSearchDto
{
    public ?string $type_doc = null;

    public ?string $reference = null;

    public ?string $designation = null;

    public ?string $constructeur = null;


    public ?\Datetime $dateDebut = null;

    public ?\DateTime $dateFin = null;
}
