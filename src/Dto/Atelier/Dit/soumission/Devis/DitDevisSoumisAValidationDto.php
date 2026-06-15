<?php

namespace App\Dto\Atelier\Dit\soumission\Devis;

class DitDevisSoumisAValidationDto
{
    public ?string $numeroDit = "";

    public ?string $numeroDevis = "";

    public int $numeroItv = 0;

    public  $dateHeureSoumission;


    public ?int $nombreLigneItv = 0;

    public ?float $montantItv = 0.00;


    public ?int $numeroVersion = 0;

    public ?float $montantPiece = 0.00;

    public ?float $montantMo = 0.00;

    public ?float $montantAchatLocaux = 0.00;

    public ?float $montantFraisDivers = 0.00;

    public ?float $montantLubrifiants = 0.00;

    public ?float $montantForfait = 0.00;

    public ?string $libellelItv = '';

    public $statut;

    public $natureOperation;

    public ?string  $type = null;

    public $pieceJoint01;

    public $pieceJoint02;

    public $pieceJoint03;

    public $pieceJoint04;

    public $nomClient;

    public $numeroClient;

    public $objetDit;

    public string $devisVenteOuForfait;

    public ?string $devise = '';

    public ?float $montantVente = 0.00;

    public int $nombreLignePiece = 0;

    public $tacheValidateur;

    public ?string $codeSociete = null;

    public bool $estCeVente = false;

    public int $nbPieceSortieMagasin = 0;

    public int $uneDevisEstDejaValide = 0;

    public array $infoDit = [];

    public array $infoDevisIps = [];

    // Comparaison des objets par leur numero d'intervention
    public function estEgalParNumero(self $autre)
    {
        return $this->numeroItv === $autre->numeroItv;
    }
}
