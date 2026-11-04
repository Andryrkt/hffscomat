<?php

namespace App\Dto\Atelier\CVA;

class OrCsaATraiterSearchDto
{
    public ?\Datetime $leadTimeDu = null;
    public ?\DateTime $leadTimeAu = null;
    public ?string $client = null;
    public ?string $referenceClient = null;
    public ?string $fenetreAllocation = 'TOUS';
    public ?string $numOr = null;
    public ?string $typeOr = null;
}
