<?php

namespace App\Dto\Atelier\Dit\DossierDit;

use DateTime;

class DossierInterventionAtelierSearchDto
{
    public ?string $numDit = null;
    public ?string $numOr = null;
    public ?string $numDev = null;
    public ?string $typeIntervention = null;
    public ?DateTime $dateDebut = null;
    public ?DateTime $dateFin = null;
    public ?string $idMateriel = null;
    public ?string $designation = null;
    public ?string $numSerie = null;
    public ?string $numParc = null;
}
