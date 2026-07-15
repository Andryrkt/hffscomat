<?php

namespace App\Dto\Magasin\Commande\Soumission;

class CommandeSoumissionDetailDTO
{
    public ?string    $numDoc       = null;
    public ?string    $refClient    = null;
    public ?string    $numClient    = null;
    public ?string    $nomClient    = null;
    public ?string    $rmqClient    = null;
    public ?\DateTime $datePlanning = null;
}
