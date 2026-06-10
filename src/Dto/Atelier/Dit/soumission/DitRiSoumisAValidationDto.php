<?php

namespace App\Dto\Atelier\Dit\soumission;

class DitRiSoumisAValidationDto
{
    public ?string $numeroDit = null;


    public ?string $numeroOr = null;


    public ?string $dateSoumission = null;

    public ?string $heureSoumission = null;


    public int $numeroSoumission = 0;

    public ?string $statut = "";


    public int $numeroItv = 0;

    public $pieceJoint01;

    public $action;

    public ?string $codeSociete = null;

    public array $itvDejaSoumis = [];

    public array $itvAfficher = [];
}
