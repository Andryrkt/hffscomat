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

    /**
     * Indique si la quantité demandée n'est pas totalement couverte par ce qui est déjà à livrer/livré
     */
    public function estIncomplet(): bool
    {
        return $this->quantiteDemandee > ($this->quantiteALivrer + $this->quantiteLivree);
    }

    /**
     * Classe CSS à appliquer à la ligne lorsque la quantité demandée n'est pas totalement couverte
     */
    public function getClasseLigne(): string
    {
        return $this->estIncomplet() ? 'textColor' : '';
    }

    public function getDatePlanningFormatee(): string
    {
        return $this->datePlanning === null ? '--' : $this->datePlanning->format('d/m/Y');
    }

    public function getDateCreationFormatee(): string
    {
        return $this->dateCreation === null ? '--' : $this->dateCreation->format('d/m/Y');
    }

    public function getNumeroInterventionFormate(): string
    {
        return number_format($this->numeroIntervention ?? 0, 0);
    }

    public function getQuantiteDemandeeFormatee(): string
    {
        return empty($this->quantiteDemandee) ? '-' : number_format($this->quantiteDemandee, 0);
    }

    public function getQuantiteALivrerFormatee(): string
    {
        return empty($this->quantiteALivrer) ? '-' : number_format($this->quantiteALivrer, 0);
    }

    public function getQuantiteLivreeFormatee(): string
    {
        return empty($this->quantiteLivree) ? '-' : number_format($this->quantiteLivree, 0);
    }
}
