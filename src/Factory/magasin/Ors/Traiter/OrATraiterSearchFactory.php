<?php

namespace App\Factory\magasin\Ors\Traiter;

use App\Constants\admin\ApplicationConstant;
use App\Dto\Magasin\Ors\Traiter\OrATraiterSearchDto;
use App\Model\magasin\Ors\Traiter\OrTraiterModel;
use App\Service\security\SecurityService;
use App\Service\TableauEnStringService;

class OrATraiterSearchFactory
{
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function initialisationSearch(): OrATraiterSearchDto
    {
        $agenceUser = "''";

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->securityService->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        if (!$multisuccursale) {
            $agenceServiceAutorises = $this->securityService->getAgenceServices(ApplicationConstant::CODE_MAGASIN);

            // Si l'utilisateur n'a pas d'agence et service autorisé, on prend son agence par défaut
            $codeAgence = empty($agenceServiceAutorises) ? [$this->securityService->getCodeAgenceUser()] : array_column($agenceServiceAutorises, 'agence_code');

            $agenceUser = TableauEnStringService::TableauEnString(',', $codeAgence);
        }

        $dto = new OrATraiterSearchDto();
        $dto->codeSociete = $this->securityService->getCodeSocieteUser();
        $dto->agenceUser = $agenceUser;

        return $dto;
    }
}
