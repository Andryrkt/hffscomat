<?php

namespace App\Factory\magasin\Commande\Traiter;

use App\Constants\admin\ApplicationConstant;
use App\Dto\Magasin\Commande\Traiter\CommandeTraiterSearchDto;
use App\Service\security\SecurityService;
use App\Service\TableauEnStringService;

class CommandeTraiterSearchFactory
{
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function initialisationSearch(): CommandeTraiterSearchDto
    {
        $agenceUser = "''";

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->securityService->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        if (!$multisuccursale) {
            $agenceServiceAutorises = $this->securityService->getAgenceServices(ApplicationConstant::CODE_MAGASIN);

            // Si l'utilisateur n'a pas d'agence et service autorisé, on prend son agence par défaut
            $codeAgence = empty($agenceServiceAutorises) ? [$this->securityService->getCodeAgenceUser()] : array_column($agenceServiceAutorises, 'agence_code');

            $agenceUser = TableauEnStringService::TableauEnString(',', $codeAgence, '');
        }

        $dto = new CommandeTraiterSearchDto();
        $dto->codeSociete = $this->securityService->getCodeSocieteUser();
        $dto->agenceUser = $agenceUser;

        return $dto;
    }
}
