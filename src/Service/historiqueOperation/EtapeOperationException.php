<?php

namespace App\Service\historiqueOperation;

/**
 * Erreur survenue pendant une étape suivie par SuiviOperation
 */
class EtapeOperationException extends \RuntimeException
{
    private string $etape;

    public function __construct(string $etape, \Throwable $previous)
    {
        $this->etape = $etape;

        parent::__construct("Étape « $etape » : " . $previous->getMessage(), 0, $previous);
    }

    public function getEtape(): string
    {
        return $this->etape;
    }
}
