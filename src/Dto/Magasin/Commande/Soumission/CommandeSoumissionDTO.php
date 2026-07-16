<?php

namespace App\Dto\Magasin\Commande\Soumission;

class CommandeSoumissionDTO
{
    public ?string    $numeroCommande  = null;
    public ?\DateTime $dateCde         = null;
    public ?string    $typeCde         = null;
    public ?int       $delaiExpedition = null;
    public ?string    $numFrn          = null;
    public ?string    $nomFrn          = null;
    public ?string    $responsable     = null;
    public ?string    $libelleAgence   = null;
    public ?string    $libelleService  = null;

    /** @var list<CommandeSoumissionLigneDTO> */
    public array      $lignes          = [];

    public function getDateCdeFormatted(): string
    {
        if (!$this->dateCde) return "";

        $dateFormatter = new \IntlDateFormatter(
            'fr_FR',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            null,
            null,
            "EEEE dd MMMM yyyy"
        );

        return $dateFormatter->format($this->dateCde);
    }
}
