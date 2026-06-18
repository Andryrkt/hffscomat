<?php

namespace App\Service\Atelier\DossierDit;

use App\Dto\Atelier\Dit\DossierDit\DossierInterventionAtelierSearchDto;
use App\Dto\Atelier\Dit\DossierDit\DwDitDto;
use App\Dto\Atelier\Dit\DossierDit\DwDocDto;
use App\Mapper\Atelier\Dit\DossierDit\DwDitMapper;
use App\Mapper\Atelier\Dit\DossierDit\DwDocMapper;
use App\Model\dw\dossierInterventionAtelierModel;

class DossierDitService
{
    private DwDitMapper $dwDitMapper;
    private DwDocMapper $dwDocMapper;
    private dossierInterventionAtelierModel $dwModel;

    public function __construct()
    {
        $this->dwDitMapper = new DwDitMapper();
        $this->dwDocMapper = new DwDocMapper();
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
            return $this->dwDitMapper->mapToDto($item);
        }, $dwDits);
    }

    /**
     * Méthode pour avoir tous les documents rattachés à un DIT
     * 
     * @return array<DwDocDto>
     */
    public function getDwDocs(string $numDit): array
    {
        $dwDocs = $this->dwModel->findAllDwDocs($numDit);

        return array_map(function ($item): DwDocDto {
            return $this->dwDocMapper->mapToDto($item);
        }, $dwDocs);
    }
}
