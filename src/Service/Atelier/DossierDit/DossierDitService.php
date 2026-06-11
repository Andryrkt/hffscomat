<?php

namespace App\Service\Atelier\DossierDit;

use App\Dto\Atelier\Dit\DossierDit\DossierInterventionAtelierSearchDto;
use App\Dto\Atelier\Dit\DossierDit\DwDitDto;
use App\Mapper\Atelier\Dit\DossierDit\DwDitMapper;
use App\Model\dw\dossierInterventionAtelierModel;

class DossierDitService
{
    private DwDitMapper $mapper;
    private dossierInterventionAtelierModel $dwModel;

    public function __construct()
    {
        $this->mapper = new DwDitMapper();
        $this->dwModel = new dossierInterventionAtelierModel();
    }

    /** 
     * Méthode pour avoir la liste des DwDIT
     * 
     * @return array<DwDitDto>
     */
    public function getFilteredDwDit(DossierInterventionAtelierSearchDto $searchDto): array
    {
        $dwDits = $this->dwModel->findAllDwDit($searchDto);

        return array_map(function ($item): DwDitDto {
            return $this->mapper->mapToDto($item);
        }, $dwDits);
    }
}
