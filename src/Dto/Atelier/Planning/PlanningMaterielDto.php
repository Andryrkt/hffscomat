<?php

namespace App\Dto\Atelier\Planning;

class PlanningMaterielDto
{
    public ?string $codeSuc = null;

    public ?string $libsuc = null;

    public ?string $codeServ = null;

    public ?string $libServ = null;

    public ?string $idMat = null;

    public ?string $marqueMat = null;

    public ?string $typeMat = null;

    public ?string $numSerie = null;

    public ?string $numParc = null;

    public ?string $casier = null;

    public ?string $orIntv = null;

    public ?string $numeroOr = null;

    public ?string $commercial = null;

    public ?string $commentaire = null;

    public ?string $plan = null;

    public ?float $qteCdm = 0.0;

    public ?float $qteLiv = 0.0;

    public ?float $qteAll = 0.0;

    public array $moisDetails = [];

    public function addMoisDetails(array $detail)
    {
        $this->moisDetails[] = $detail;
    }

}