<?php

namespace App\Dto\Atelier\Dit\soumission\AcBc;

use App\Controller\Traits\FormatageTrait;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AccuseReceptionDto
{
    use FormatageTrait;

    public ?string $numeroDit = null;
    public ?string $numeroDevis = null;
    public ?string $statutDevis = null;
    public ?\DateTime $dateDevis = null; // date de soumission de devis
    public ?\DateTime $dateCreation = null;
    public int $numeroVersionMaxByDit = 0;
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
    public ?string $nomFichierAcSoumis = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

    /**
     * Get the value of montantDevis formatted
     */
    public function getMontantDevisFormatted(): string
    {
        return $this->formatNumberGeneral($this->montantDevis, ' ', '.', 2);
    }
}
