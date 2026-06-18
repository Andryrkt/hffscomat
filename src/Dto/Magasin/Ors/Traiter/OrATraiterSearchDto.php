<?php

namespace App\Dto\Magasin\Ors\Traiter;

class OrATraiterSearchDto
{
    public ?string $niveauUrgence = null;
    public ?string $numDit = null;
    public ?string $numOr = null;
    public ?string $referencePiece = null;
    public ?string $designation = null;
    public ?\DateTime $dateDebut = null;
    public ?\DateTime  $dateFin = null;
    public ?string $pieces = null;
    public ?string $agence = null;
    public ?string $service = null;
    public ?string $agenceUser = null;
    public ?string $agenceUserHidden = null;
    public ?string $codeSociete = null;
    public ?string $numMat = null;
}
