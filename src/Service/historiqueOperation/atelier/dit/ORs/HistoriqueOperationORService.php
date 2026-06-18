<?php

namespace App\Service\historiqueOperation\Atelier\Dit\ORs;

use App\Entity\admin\historisation\documentOperation\TypeDocument;
use App\Service\historiqueOperation\HistoriqueOperationService;
use Doctrine\ORM\EntityManagerInterface;


class HistoriqueOperationORService extends HistoriqueOperationService
{
    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct($em, TypeDocument::TYPE_DOCUMENT_OR_ID);
    }
}
