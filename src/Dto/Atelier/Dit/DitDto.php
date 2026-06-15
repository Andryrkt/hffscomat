<?php

namespace App\Dto\Atelier\Dit;


use App\Entity\admin\Agence;
use App\Entity\admin\Service;
use DateTime;

class DitDto
{

    public ?int $id  = null;
    public ?string $numeroDemandeIntervention = null;
    public ?string $objetDemande = null;
    public ?string $detailDemande = null;
    public ?string $typeDocument = null;
    public ?string $categorieDemande = null;
    public string $livraisonPartiel = 'NON';
    public ?string $demandeDevis = 'NON';
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
    public ?string $clientSousContrat = 'NON';

    // PIECE JOINTE
    public $pieceJoint01 = null;
    public $pieceJoint02 = null;
    public $pieceJoint03 = null;
    public $nbrPj = null;

    public ?int $id_statut_demande = null;
    public ?string $statutDemande = null;
    public ?string $mailDemandeur = null;
    public ?string $dateDemande = null;
    public ?string $heureDemande = null;
    public ?string $utilisateurDemandeur = null;
    public ?string $codeSociete = null;
    public bool $estDitAvoir = false;
    public bool $estDitRefacturation = false;
    public bool $estAtePolTana = false;
    public bool $estAnnulable = false;
    public bool $estOrASoumi = false;


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


    // Quantite
    public ?int $quantiteDemanderOr = 0;
    public ?int $quantiteLivreeOr = 0;
    public ?int $quantiteReserverOr = 0;
    public ?int $qteLivOr = 0;
    public ?int $quantiteReliquatOr = 0;
    public ?string $etatLivraison = null;

    public function getEtatLivraison(): string
    {

        $qteDem = $this->quantiteDemanderOr ?? 0;
        $qteLiv = $this->quantiteLivreeOr ?? 0;
        $qteRes = $this->quantiteReserverOr ?? 0;

        if ($qteDem === $qteLiv && $qteDem !== 0) {
            return 'Tout livré';
        }
        if ($qteLiv > 0 && $qteLiv !== $qteDem && $qteDem !== 0) {
            return 'Partiellement livré';
        }
        if ($qteRes !== $qteDem && $qteLiv === 0 && $qteRes > 0) {
            return 'Partiellement disponible';
        }
        if ($qteDem === $qteRes && $qteLiv < $qteDem && $qteDem !== 0) {
            return 'Complet non livré';
        }

        return 'Non Livré';
    }


    // facturation
    public ?string $etatFacturation = null;

    // RI
    public ?string $ri = null;

    // INFO MATERIEL
    public ?string $idMateriel = null;
    public ?string $numParc = null;
    public ?string $numSerie = null;
    public ?string $designation = null;
    public ?string $modele = null;
    public ?string $constructeur = null;
    public ?string $casier = null;
    public ?string $heure = null;
    public ?string $km = null;
    // Bilan Financiere
    public float $coutAcquisition = 0.0;
    public float $amortissement = 0.0;
    public float $valeurNetComptable = 0.0;
    public float $chargeEntretient = 0.0;
    public float $chargeLocative = 0.0;
    public float $chiffreAffaire = 0.0;
    public float $resultatExploitation = 0.0;
}
