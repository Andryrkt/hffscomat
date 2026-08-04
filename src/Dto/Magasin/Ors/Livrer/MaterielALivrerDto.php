<?php

namespace App\Dto\Magasin\Ors\Livrer;

class MaterielALivrerDto
{
    public ?string $referenceDit = null;
    public ?string $numeroOr = null;
    public ?\DateTime $datePlanning = null;
    public ?string $niveauUrgence = null;
    public ?\DateTime $dateCreation = null;
    public ?string $agenceCrediteur = null;
    public ?string $serviceCrediteur = null;
    public ?string $agenceDebiteur = null;
    public ?string $serviceDebiteur = null;
    public ?int $numeroIntervention = null;
    public ?int $numeroLigne = null;
    public ?string $constructeur = null;
    public ?string $referencePiece = null;
    public ?string $designation = null;
    public ?int $quantiteDemandee = null;
    public ?int $quantiteALivrer = null;
    public ?int $quantiteLivree = null;
    public ?string $nomPrenom = null;
    public ?string $situation = null;
    public ?string $idUser = null;
    public ?string $nomUtilisateur = null;
    public ?string $idMateriel = null;
    public ?string $numeroSerie = null;
    public ?string $numeroParc = null;
    public ?string $marque = null;
    public ?string $casier = null;
    public ?string $numeroCommande = null;
}
