<?php

namespace App\Service\historiqueOperation\Atelier\Dit;

use App\Service\historiqueOperation\HistoriqueOperationService;
use Doctrine\ORM\EntityManagerInterface;

class HistoriqueOperationDITService extends HistoriqueOperationService
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, 1);
    }
}
