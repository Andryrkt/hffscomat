<?php

namespace App\Factory\Atelier\Dit\soumission\Devis;

use App\Dto\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationDto;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Service\security\SecurityService;

class DitDevisSoumisAValidationFactory
{
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
    }

    public function initialisation(string $numDit, string $type): DitDevisSoumisAValidationDto
    {
        $ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();
        $dto = new DitDevisSoumisAValidationDto();
        $dto->numeroDit = $numDit;
        $dto->type = $type;
        $dto->codeSociete = $this->securityService->getCodeSocieteUser();
        $dto->numeroDevis = $ditDevisSoumisAValidationModel->recupNumeroDevis($dto->numeroDit, $dto->codeSociete);

        return $dto;
    }

    public function apresSoumission(DitDevisSoumisAValidationDto $dto): DitDevisSoumisAValidationDto
    {
        $dto->dateHeureSoumission = date('Y-m-d H:i:s');
        $ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();
        $dto->estCeVente = $this->estCeVente($dto->numeroDevis, $dto->codeSociete);
        $dto->numeroVersion = $ditDevisSoumisAValidationModel->recupNumeroVersion($dto->numeroDevis, $dto->codeSociete);
        $dto->nbPieceSortieMagasin = $ditDevisSoumisAValidationModel->recupNbPieceMagasin($dto->numeroDevis, $dto->codeSociete);
        $dto->infoDit = $ditDevisSoumisAValidationModel->recupInfoDit($dto->numeroDit, $dto->numeroDevis, $dto->codeSociete);
        $dto->infoDevisIps = $ditDevisSoumisAValidationModel->recupDevisSoumisValidation($dto->numeroDevis, $dto->codeSociete);
        return $dto;
    }

    /**
     * Methode qui permet de savoir si la soumission
     * est une Devis vente ou forfait
     *
     * @param string $numDevis
     * @return boolean
     */
    private function estCeVente(string $numDevis, string $codeSociete): bool
    {
        $ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();

        $recupConstRefPremDev = $ditDevisSoumisAValidationModel->recupConstRefPremDev($numDevis, $codeSociete);
        $recupNbrItvDev = $ditDevisSoumisAValidationModel->recupNbrItvDev($numDevis, $codeSociete);

        if (
            !is_array($recupConstRefPremDev) || empty($recupConstRefPremDev)
        ) {
            return false; // Devis forfait
        }

        if ($recupConstRefPremDev[0]['contructeur'] === 'ZDI-FORFAIT' && (int)$recupNbrItvDev[0]['itv'] > 0) {
            return false; //Devis forfait
        } else {
            return true; //Devis vente
        }
    }
}
