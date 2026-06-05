<?php

namespace App\Dto\Atelier\Dit;

use App\Dto\Atelier\Dit\WorTypeDocumentDto;
use App\Entity\admin\Agence;
use App\Entity\admin\Service;
use App\Entity\admin\StatutDemande;
use DateTime;

class DitDto
{
    public ?string $objetDemande = null;
    public ?string $detailDemande = null;
    public ?WorTypeDocumentDto $typeDocument = null;
    public ?string $categorieDemande = null;
    public string $livraisonPartiel = 'NON';
    public string $demandeDevis = 'NON';
    public string $avisRecouvrement = 'NON';
    public ?string $agenceEmetteur = null;
    public ?string $serviceEmetteur = null;
    public ?Agence $agence = null;
    public ?Service $service = null;
    public ?string $worNiveauUrgence = null;
    public ?DateTime $datePrevueTravaux = null;
    public ?string $typeReparation = null;
    public ?string $reparationRealise = null;
    public ?string $internetExterne = null;
    // INFO CLIENT
    public ?string $numeroClient = null;
    public ?string $nomClient = null;
    public ?string $numeroTel = null;
    public ?string $mailClient = null;
    public string $clientSousContrat = 'NON';
    // INFO MATERIEL
    public ?string $idMateriel = null;
    public ?string $numParc = null;
    public ?string $numSerie = null;
    // PIECE JOINTE
    public $pieceJoint01 = null;
    public $pieceJoint02 = null;
    public $pieceJoint03 = null;

    public ?string $statutDemande = null;
    public ?string $numeroDemandeIntervention = null;
    public ?string $mailDemandeur = null;
    public ?DateTime $dateDemande = null;
    public ?string $heureDemande = null;
    public ?string $utilisateurDemandeur = null;
    public ?string $codeSociete = null;
    public bool $estDitAvoir = false;
    public bool $estDitRefacturation = false;
    public bool $estAtePolTana = false;
}
