<?php

namespace App\Dto\Magasin\Commande\Soumission;

class BcSoumisMagasinDTO
{
    public ?string    $numeroCommande      = null;
    public ?string    $statut              = null;
    public ?string    $operateur           = null;
    public ?\DateTime $dateHeureSoumission = null;
    public bool       $deposerDw           = false;
}
