<?php

namespace App\Dto\Magasin\Commande\Soumission;

class CommandeSoumissionDTO
{
    public ?string    $numeroCommande  = null;
    public ?\DateTime $dateCde         = null;
    public ?string    $typeCde         = null;
    public ?\DateTime $dateExpedition  = null;
    public ?string    $numFrn          = null;
    public ?string    $nomFrn          = null;
    public ?string    $devise          = null;
    public ?string    $responsable     = null;
    public ?string    $libelleAgence   = null;
    public ?string    $libelleService  = null;
    public float      $poidsTotal      = 0.00;
    public float      $montantTotal    = 0.00;
    public array      $allValidatedOR  = [];
    public array      $allValidatedPO  = [];

    /** @var list<CommandeSoumissionLigneDTO> */
    public array      $lignes          = [];

    private function formatDateLongue(\DateTime $date): string
    {
        return (new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            null,
            null,
            "EEEE dd MMMM yyyy"
        ))->format($date);
    }

    public function getDateCdeFormatted(): string
    {
        if (!$this->dateCde) return "";

        return "du {$this->formatDateLongue($this->dateCde)}";
    }

    public function getDateExpeditionFormatted(): string
    {
        if (!$this->dateExpedition) return "-";

        return $this->formatDateLongue($this->dateExpedition);
    }

    public function getAgenceService(): string
    {
        return "{$this->libelleAgence} - {$this->libelleService}";
    }

    public function getPoidsTotal(): string
    {
        if ($this->poidsTotal === null) return "";

        return number_format($this->poidsTotal, 2, ',', ' ');
    }

    public function getMontantTotal(): string
    {
        if ($this->montantTotal === null) return "";

        return number_format($this->montantTotal, 2, ',', ' ');
    }

    public function getAllValidatedOR(): string
    {
        if (empty($this->allValidatedOR)) return "";

        return implode("; ", $this->allValidatedOR);
    }

    public function getAllValidatedPO(): string
    {
        if (empty($this->allValidatedPO)) return "";
        return implode("; ", $this->allValidatedPO);
    }
}
