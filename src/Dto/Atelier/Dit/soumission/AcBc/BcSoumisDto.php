<?php

namespace App\Dto\Atelier\Dit\soumission\AcBc;

class BcSoumisDto
{
    public ?int $id = null;
    public ?string $numeroDit = null;
    public ?string $numeroDevis = null;
    public ?string $numeroBc = null;
    public int $numeroVersion = 1;
    public ?string $codeSociete = null;
    public ?string $statut = null;
    public ?\DateTime $dateSoumissionBc = null;
    public ?\DateTime $dateBc = null;
    public ?\DateTime $dateDevis = null;
    public ?string $nomFichier = null;
    public float $montantDevis = 0.0;

    public function __construct()
    {
        $this->dateSoumissionBc = new \DateTime();
    }
}
