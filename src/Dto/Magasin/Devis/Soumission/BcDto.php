<?php

namespace App\Dto\Magasin\Devis\Soumission;


class BcDto
{
    public string $numeroDevis;
    public ?string $numeroBc;
    public $montantDevis;
    public $montantBc;
    public $numeroVersion;
    public $statutBc;
    public $observation;
    public $utilisateur;
    public $dateCreation;
    public $dateModification;
    public $dateBc;
    public $codeSociete;

    public $pieceJoint01;
    public $pieceJoint2;
    public $lignes;

    public $userMail;
    public $codeClient;
    public $nomClient;
    public $modePayement;

    public $numeroVersionDevis;

    public ?string $dateEnvoiDevisClient = null;
}
