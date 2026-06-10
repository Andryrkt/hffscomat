<?php

namespace App\Dto\atelier\dit\soumission;

use Symfony\Component\HttpFoundation\File\UploadedFile;

class DitFactureSoumisAValidationDto
{
	public ?string $numeroFact = null;

	public ?string $numeroDit = null;

	public ?string $numeroOr = null;

    public ?\DateTime $dateSoumission = null;

    public ?\DateTime $heureSoumission = null;

	public int $numeroSoumission = 0;

	public int $numeroItv = 0;

	public float $montantFactureItv = 0.00;

	public ?string $agenceDebiteur = null;

	public ?string $serviceDebiteur = null;

	public ?string $statut = '';

	public ?string $codeSociete = null;

	public ?string $statutItv = null;

	public float $mttItv = 0.00;

	public ?string $libelleItv = '';

	public ?string $agServDebDit = '';

	public UploadedFile $pieceJoint = [];
}