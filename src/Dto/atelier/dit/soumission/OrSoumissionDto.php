<?php

namespace App\Dto\atelier\dit\soumission;

use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class OrSoumissionDto
{

    public ?string $numeroDit = null;


    public ?string $numeroOr = null;


    public int $numeroItv = 0;


    public $dateSoumission;


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



    public UploadedFile $pieceJoint01;

    public ?string $originalNamePj1 = null;

    public UploadedFile $pieceJoint02;

    public UploadedFile $pieceJoint03;

    public UploadedFile $pieceJoint04;

    public  $statut;


    public $migration;


    public $pieceFaibleActiviteAchat;


    public $codeSociete;


    public bool $isExistDatePlaning = false;

    public $id_materiel_ips;

    public $info_materiel;

    public int $nmbrOr_soumis;
}
