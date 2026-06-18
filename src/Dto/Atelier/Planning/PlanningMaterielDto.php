<?php

namespace App\Dto\Atelier\Planning;

use DateTimeInterface;

class PlanningMaterielDto
{
    public ?string $codeSuc = null;
    public ?string $libSuc = null;           // was libsuc
    public ?string $codeServ = null;
    public ?string $libServ = null;
    public ?string $commentaire = null;

    public ?string $idMat = null;
    public ?string $markMat = null;
    public ?string $typeMat = null;
    public ?string $numSerie = null;
    public ?string $numDit = null;
    public ?string $numParc = null;
    public ?string $casier = null;

    public ?int $annee = null;
    public ?int $mois = null;

    public ?string $orItv = null;
    public ?string $numOr = null;
    public ?string $itv = null;

    // Quantities
    public ?float $qteCdm = 0.0;      // qte_cmd
    public ?float $qteLiv = 0.0;      // qte_liv
    public ?float $qteAll = 0.0;      // qte_all
    public ?float $qteReliquant = 0.0;
    public ?float $qteResOr = 0.0;

    // Status & Planning
    public ?string $statutB = null;           // statut_b (global status)
    public ?string $statut = null;
    public ?string $statutCtrmq = null;
    public ?string $statutCtrmqCis = null;

    public ?DateTimeInterface $datePlanning = null;
    public ?DateTimeInterface $dateStatut = null;

    public ?string $plan = null;

    // Piece details
    public ?string $cst = null;
    public ?string $ref = null;
    public ?string $desi = null;

    public ?string $numCmd = null;
    public ?string $numCis = null;
    public ?string $numCmdCis = null;

    public ?string $message = null;

    // Commercial / other
    public ?string $commercial = null;

    // Grouping
    public array $moisDetails = [];

    public function addMoisDetails(array $detail): void
    {
        $this->moisDetails[] = $detail;
    }

    // Optional helper
    public function getTotalQteCdm(): float
    {
        return array_sum(array_column($this->moisDetails, 'qteCdm'));
    }

}