<?php

namespace App\Dto\Atelier\OrBc;

use DateTime;

class CmdeATraitrerAtelierDto
{

    public ?string $type_doc = null;
    public ?string $numero_doc = null;
    public ?string $numero_ligne = null;
    public ?string $constructeur = null;
    public ?string $reference = null;
    public ?string $designation = null;

    public float $qte_demandee = 0.0;
    public float $qte_reliquat = 0.0;
    public  ?DateTime $date_alloc = null;
    public float $qte_reservee = 0.0;
}
