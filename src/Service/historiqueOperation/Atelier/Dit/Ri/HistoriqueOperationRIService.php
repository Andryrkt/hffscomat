<?php

namespace App\Service\historiqueOperation\atelier\dit\Ri;

use App\Entity\admin\historisation\documentOperation\TypeDocument;
use App\Service\historiqueOperation\HistoriqueOperationService;
use Doctrine\ORM\EntityManagerInterface;

class HistoriqueOperationRIService extends HistoriqueOperationService
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, TypeDocument::TYPE_DOCUMENT_RI_ID);
    }
}
