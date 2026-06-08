<?php

namespace App\Factory\Atelier\Dit;

use App\Constants\atelier\dit\StatutDitConstant;
use App\Dto\Atelier\Dit\DitDto;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Service\security\SecurityService;

class DitFactory
{

    private SecurityService $securityService;

    public function __construct(

        SecurityService $securityService
    ) {

        $this->securityService = $securityService;
    }

    public function initialisation(array $agenceService, string $codeSociete): DitDto
    {
        $dto = new DitDto();
        $dto->agenceEmetteur = $agenceService['agenceIps']->getCodeAgence() . ' ' . $agenceService['agenceIps']->getLibelleAgence();
        $dto->serviceEmetteur = $agenceService['serviceIps']->getCodeService() . ' ' . $agenceService['serviceIps']->getLibelleService();
        $dto->agence = $agenceService['agenceIps'];
        $dto->service = $agenceService['serviceIps'];
        $dto->worNiveauUrgence = $this->getP2NiveauUrgence();
        $dto->codeSociete = $codeSociete;

        return $dto;
    }

    public function apresSoumission(DitDto $dto): DitDto
    {
        $dto->utilisateurDemandeur = $this->securityService->getDataService()->getUserName();
        $dto->heureDemande = $this->securityService->getDataService()->getTime();
        $dto->dateDemande = new \DateTime();
        $dto->mailDemandeur = $this->securityService->getDataService()->getUserMail();
        $dto->statutDemande = StatutDitConstant::STATUT_A_AFFECTER;

        // TODO
        $dto->numeroDemandeIntervention = '';


        return $dto;
    }

    private function getP2NiveauUrgence(): ?string
    {
        $worNiveauUrgenceModel = new WorNiveauUrgenceModel();
        return $worNiveauUrgenceModel->getP2Description();
    }
}
