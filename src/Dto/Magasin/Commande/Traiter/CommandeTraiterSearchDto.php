<?php

namespace App\Dto\Magasin\Commande\Traiter;

class CommandeTraiterSearchDto
{
    public ?string $agenceUser = null;
    public ?string $agence = null;
    public ?string $agenceUserHidden = null;
    public ?array $service = null;
    public ?string $codeClient = null;
    public ?string $numCommande = null;
    public ?string $numDevis = null;
    public ?\DateTime $dateDebut = null;
    public ?\DateTime  $dateFin = null;
    public ?string $referencePiece = null;
    public ?string $codeSociete = null;
    public ?string $constructeur = null;
}
