<?php

namespace App\Service\historiqueOperation\magasin\commandeFrn;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\historiqueOperation\HistoriqueOperationService;
use App\Entity\admin\historisation\documentOperation\TypeDocument;

class HistoriqueOperationCdeFrnService extends HistoriqueOperationService
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, TypeDocument::TYPE_DOCUMENT_CDE_ID);
    }
}
