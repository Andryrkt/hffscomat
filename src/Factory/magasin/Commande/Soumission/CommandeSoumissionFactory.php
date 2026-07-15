<?php

namespace App\Factory\magasin\Commande\Soumission;

use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;

class CommandeSoumissionFactory
{
    public function hydrate(array $data): CommandeSoumissionDTO
    {
        $dto = new CommandeSoumissionDTO;

        $dto->dateJour        = new \DateTime();
        $dto->numeroCommande  = "";
        $dto->codeSociete     = "";
        $dto->typeCde         = "";
        $dto->delaiExpedition = "";
        $dto->numFrn          = "";
        $dto->nomFrn          = "";
        $dto->responsable     = "";
        $dto->libelleAgence   = "";
        $dto->libelleService  = "";

        return $dto;
    }
}
