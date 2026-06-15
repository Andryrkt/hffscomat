<?php

namespace App\Service\historiqueOperation\atelier\dit\Facture;

use App\Entity\admin\historisation\documentOperation\TypeDocument;
use App\Service\historiqueOperation\HistoriqueOperationService;
use Doctrine\ORM\EntityManagerInterface;

class HistoriqueOperationFACService extends HistoriqueOperationService
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, TypeDocument::TYPE_DOCUMENT_FAC_ID);
    }
}
