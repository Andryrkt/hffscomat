<?php

namespace App\Dto\Magasin\Commande\Soumission;

class CommandeSoumissionDTO
{
    public ?string $numeroCommande = null;
    public ?string $codeSociete    = null;
    public ?\DateTime $dateJour    = null;
    public ?string $typeCde        = null;
    public ?int $delaiExpedition   = null;
    public ?string $numFrn         = null;
    public ?string $nomFrn         = null;
    public ?string $responsable    = null;
    public ?string $libelleAgence  = null;
    public ?string $libelleService = null;

    /** @var list<CommandeSoumissionLigneDTO> */
    public array $lignes           = [];
}
