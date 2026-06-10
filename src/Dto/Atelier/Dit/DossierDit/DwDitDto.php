<?php

namespace App\Dto\Atelier\Dit\DossierDit;

use DateTime;

class DwDitDto
{
    public string $numeroDit;
    public string $idMateriel;
    public string $numeroParc;
    public string $numeroSerie;
    public string $numeroOR;
    public string $designationMateriel;
    public string $typeIntervention;
    public DateTime $dateCreation;
    public int $nbDoc = 0;
}
