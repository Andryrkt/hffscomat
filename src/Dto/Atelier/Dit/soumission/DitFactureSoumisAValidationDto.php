<?php

namespace App\Dto\atelier\dit\soumission;

class DitFactureSoumisAValidationDto
{
	public ?string $numeroFact = null;

	public ?string $numeroDit = null;

	public ?string $numeroOr = null;

	public ?string $dateSoumission = null;

	public ?string $heureSoumission = null;

	public int $numeroSoumission = 0;

	public int $numeroItv = 0;

	public float $montantFactureItv = 0.00;

	public ?string $agenceDebiteur = null;

	public ?string $serviceDebiteur = null;

	public ?string $statut = null;

	public ?string $codeSociete = null;

	public ?string $statutItv = null;

	public float $mttItv = 0.00;

	public ?string $libelleItv = null;

	public ?string $agServDebDit = null;

	public ?string $interneExterne = null;

	public $migration;

	public $pieceJoint01;
	public $pieceJoint02;
	public $pieceJoint03;
	public $pieceJoint04;

	public array $infoFac = [];

	public bool $estRi = false;

	public ?string $etatOr = null;

	public ?string $numDevis = null;
}
