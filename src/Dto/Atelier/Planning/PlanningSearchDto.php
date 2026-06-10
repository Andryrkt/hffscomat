<?php

namespace App\Dto\Atelier\Planning;

class PlanningSearchDto
{
    public ?string $agence = null;
    public ?string $annee = null;
    public ?string $interneExterne = null;
    public ?string $facture = null;
    public ?string $plan = null;
    public ?\DateTimeInterface $dateDebut = null;
    public ?\DateTimeInterface $dateFin = null;
    public ?string $numOr = null;
    public ?string $numSerie = null;
    public ?string $idMat = null;
    public ?string $numParc = null;
    public ?string $agenceDebite = null;
    public array $serviceDebite = [];
    public ?string $typeLigne = null;
    public ?string $casier = null;
    public ?int $niveauUrgence = 0;
    public ?string $section = null;
    public ?int $months = null;
    public bool $orBackOrder = false;
    public ?int $typeDocument = 0;
    public ?string $reparationRealise = null;
    public bool $orNonValiderDw = false;

    public function toArray(): array
    {
        return get_object_vars($this);
    }

}