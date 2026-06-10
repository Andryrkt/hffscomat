<?php

namespace App\Dto\Atelier\Dit;


use App\Entity\admin\Agence;
use App\Entity\admin\Service;
use DateTime;

class DitDto
{


    public ?string $numero_demande_dit = null;
    public ?string $objetDemande = null;
    public ?string $detailDemande = null;
    public ?string $typeDocument = null;
    public ?string $categorieDemande = null;
    public string $livraisonPartiel = 'NON';
    public string $demandeDevis = 'NON';
    public string $avisRecouvrement = 'NON';

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
    public ?string $clientSousContrat = null;
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


    //agence et service
    public ?string $agenceEmetteur = null;
    public ?string $serviceEmetteur = null;
    public ?Agence $agence = null;
    public ?Service $service = null;
    public ?string $agenceServiceEmetteur = null;
    public ?string $agenceServiceDebiteur = null;

    // section
    public ?string $sectionAffectee = null;

    // devis
    public ?string $numeroDevisRattacher = null;
    public ?string $statutDevis = null;

    // OR
    public ?string $numeroOr = null;
    public ?string $statutOr = null;
    public ?string $montantOr = null;
    public ?string $dateSoumissionOr = null;

    // facturation
    public ?string $etatFacturation = null;

    // RI
    public ?string $ri = null;
}
