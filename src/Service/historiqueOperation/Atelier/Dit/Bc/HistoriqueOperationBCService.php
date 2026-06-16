<?php

namespace App\Service\historiqueOperation\Atelier\Dit\Bc;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\historiqueOperation\HistoriqueOperationService;
use App\Entity\admin\historisation\documentOperation\TypeDocument;

class HistoriqueOperationBCService extends HistoriqueOperationService
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, TypeDocument::TYPE_DOCUMENT_BC_ID);
    }
}
