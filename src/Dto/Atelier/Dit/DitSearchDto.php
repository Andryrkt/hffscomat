<?php

namespace App\Dto\Atelier\Dit;

class DitSearchDto
{
    public ?string $niveauUrgence = null;

    public ?string $statut = null;

    public ?int $idMateriel = 0;

    public ?string $typeDocument = null;

    public ?string $internetExterne = null;

    public ?\Datetime $dateDebut = null;

    public ?\DateTime $dateFin = null;

    public ?string $numParc = null;

    public ?string $numSerie = null;

    public ?int $agenceEmetteur = null;

    public ?int $serviceEmetteur = null;

    public ?int $agenceDebiteur = null;

    public ?int $serviceDebiteur = null;

    public ?string $numDit = null;

    public ?int $numOr = null;

    public ?string $statutOr = null;

    public ?bool $ditSansOr = false;

    public  ?string $categorie;

    public ?string $utilisateur = null;

    public ?string $sectionAffectee = null;

    public ?string $sectionSupport1 = null;

    public ?string $sectionSupport2 = null;

    public ?string $sectionSupport3 = null;

    public ?string $etatFacture = null;

    public ?string $numDevis = null;

    public ?string $reparationRealise = null;
}
