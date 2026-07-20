<?php

namespace App\Dto\Atelier\Dit\soumission\Devis;

class DitDevisSoumisAValidationDto
{
    public ?string $numeroDit = null;

    public ?string $numeroDevis = null;
    
    public ?string $numeroDevisDeux = null;

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

    public ?string $statut = null;

    public ?string $natureOperation = null;

    public ?string  $type = null;

    public $pieceJoint01;

    public $pieceJoint02;

    public $pieceJoint03;

    public $pieceJoint04;

    public ?string $nomClient = null;

    public ?string $numeroClient = null;

    public ?string $objetDit = null;

    public string $devisVenteOuForfait;

    public ?string $devise = '';

    public ?float $montantVente = 0.00;

    public int $nombreLignePiece = 0;

    public ?string $tacheValidateur = null;

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
