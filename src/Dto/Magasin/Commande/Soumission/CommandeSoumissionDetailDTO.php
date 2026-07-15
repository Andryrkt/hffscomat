<?php

namespace App\Dto\Magasin\Commande\Soumission;

class CommandeSoumissionDetailDTO
{
    public ?string    $numDoc       = null;
    public ?string    $refClient    = null;
    public ?string    $numClient    = null;
    public ?string    $nomClient    = null;
    public ?string    $rmqClient    = null;
    public ?\DateTime $datePlanning = null;

    public function getDatePlanningFormatted(): string
    {
        if (!$this->datePlanning) return "";

        return $this->datePlanning->format("d/m/Y");
    }
}
