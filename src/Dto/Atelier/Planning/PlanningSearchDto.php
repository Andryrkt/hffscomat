<?php

namespace App\Dto\Atelier\Planning;

class PlanningSearchDto
{
    public ?string $agence = null;
    public ?string $annee = null;
    public ?string $interneExterne = 'TOUS';
    public ?string $facture = 'ENCOURS';
    public ?string $planning = 'PLANIFIE';
    public ?string $numOr = null;
    public ?string $numSerie = null;
    public ?string $idMat = null;
    public ?string $numParc = null;
    public ?string $agenceDebite = null;
    public ?string $typeLigne = 'TOUTES';
    public ?string $casier = null;
    public ?string $section = null;
    public ?string $reparationRealise = null;
    public ?int $niveauUrgence = 0;
    public ?int $months = 3;
    public ?int $typeDocument = 0;
    public ?\DateTimeInterface $dateDebut = null;
    public ?\DateTimeInterface $dateFin = null;
    public bool $orNonValiderDw = false;
    public bool $orBackOrder = false;
    public array $serviceDebite = [];

    public function toArray(): array
    {
        return get_object_vars($this);
    }

}