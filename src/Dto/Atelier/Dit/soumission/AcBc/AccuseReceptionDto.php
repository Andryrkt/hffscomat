<?php

namespace App\Dto\Atelier\Dit\soumission\AcBc;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class AccuseReceptionDto
{
    public ?string $nomClient = null;
    public ?string $emailClient = null;
    public ?string $numeroDit = null;
    public ?string $numeroDevis = null;
    public ?string $numeroBc = null;
    public ?string $descriptionBc = null;
    public int $numeroVersion = 1;
    public ?string $codeSociete = null;
    public ?string $statutDevis = null;
    public ?\DateTime $dateCreation = null;
    public ?\DateTime $dateBc = null;
    public ?\DateTime $dateDevis = null;
    public ?UploadedFile $pieceJoint01 = null;
    public float $montantDevis = 0.0;
    public ?string $devise = null;
}
