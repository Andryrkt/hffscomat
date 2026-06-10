<?php

namespace App\Dto\Atelier\Planning;

class PlanningAtelierSearchDto
{
    public ?int $numeroSemaine = null;

    public ?\DateTimeImmutable $dateDebut = null;

    public ?\DateTimeImmutable $dateFin = null;

    public ?string $numeroOr = null;

    public ?string $ressource = null;

    public ?string $section = null;

    public ?string $agenceDeb = null;

    public ?string $agenceEm = null;

    public ?array $serviceDeb = [];
}