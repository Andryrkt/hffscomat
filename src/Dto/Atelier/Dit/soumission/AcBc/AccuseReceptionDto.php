<?php

namespace App\Dto\Atelier\Dit\soumission\AcBc;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class AccuseReceptionDto
{
    public ?string $numeroDit = null;
    public ?string $numeroDevis = null;
    public ?string $statutDevis = null;
    public ?\DateTime $dateDevis = null; // date de soumission de devis
    public ?\DateTime $dateCreation = null;
    public float $montantDevis = 0.0;
    public ?string $devise = null;
    public ?string $interneExterne = null;
    public ?string $numeroClient = null;
    public ?string $codeSociete = null;
    public ?string $nomClient = null;
    public ?string $emailClient = null;
    public ?string $numeroBc = null;
    public ?string $descriptionBc = null;
    public ?\DateTime $dateBc = null;
    public ?UploadedFile $pieceJoint01 = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }
}
