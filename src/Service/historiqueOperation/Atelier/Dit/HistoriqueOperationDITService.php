<?php

namespace App\Service\historiqueOperation\Atelier\Dit;


use App\Entity\admin\historisation\documentOperation\TypeDocument;
use App\Service\historiqueOperation\HistoriqueOperationService;
use Doctrine\ORM\EntityManagerInterface;

class HistoriqueOperationDITService extends HistoriqueOperationService
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, TypeDocument::TYPE_DOCUMENT_DIT_ID);
    }
}
