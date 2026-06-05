<?php

namespace App\Dto\atelier\dit\soumission;

class OrSoumissionDto
{

    public ?string $numeroDit = null;


    public ?string $numeroOR = '';


    public int $numeroItv = 0;


    public  $dateSoumission;


    public $heureSoumission;


    public ?int $nombreLigneItv = 0;


    public ?float $montantItv = 0.00;



    public ?int $numeroVersion = 0;


    public ?float $montantPiece = 0.00;


    public ?float $montantMo = 0.00;


    public ?float $montantAchatLocaux = 0.00;


    public ?float $montantFraisDivers = 0.00;


    public ?float $montantLubrifiants = 0.00;

    public ?string $libellelItv = '';


    public ?string $observation = '';



    public $pieceJoint01;

    public $pieceJoint02;

    public $pieceJoint03;

    public $pieceJoint04;

    public $statut;


    public $migration;


    public $pieceFaibleActiviteAchat;


    public $codeSociete;
}
