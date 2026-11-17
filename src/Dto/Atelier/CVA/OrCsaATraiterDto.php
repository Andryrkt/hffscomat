<?php

namespace App\Dto\Atelier\CVA;

class OrCsaATraiterDto
{
    public ?string $numOr = null;

    public ?string $client = null;

    public ?string $referenceClient = null;

    public ?string $typeOr = null;

    public ?\DateTime $leadTime = null;

    public int $nbJoursDispo = 0;

    public ?string $nbJoursDispoLib = null;

    public ?string $statutAllocation = null;

    public ?string $codeCouleur = null;
}
