<?php

namespace App\Dto\atelier\dit\soumission;


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

    public  ?string $statut = null;


    public $migration;


    public $pieceFaibleActiviteAchat;


    public string $codeSociete;


    public bool $isExistDatePlaning = false;

    public bool $estIdMaterielDifferent = false;

    public $info_materiel;

    public int $nmbrOr_soumis;

    public bool $isVerifiedDatePlanning = false;
    public bool $isAgenceIriumInIPS = false;
    public bool $isValidPosition = false;

    public  bool $refClient = false;
    public  $countAgServDebit;
    public bool $existeNumclient = false;


    public int $idCategorieDemande = 0;
    public int $typeOr = 0;

    public $id_materiel_ips;





    public function estEgalParNumero(self $other): bool
    {
        return $this->numeroOr === $other->numeroOr
            && $this->numeroItv === $other->numeroItv;
    }




    public function getLibellelItv(): ?string
    {
        return $this->libellelItv;
    }

    public function getNombreLigneItv(): int
    {
        return $this->nombreLigneItv ?? 0;
    }

    public function getMontantItv(): float
    {
        return $this->montantItv ?? 0.0;
    }
}
