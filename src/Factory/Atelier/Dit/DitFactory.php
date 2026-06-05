<?php

namespace App\Factory\Atelier\Dit;


use App\Dto\Atelier\Dit\DitDto;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use Doctrine\ORM\EntityManagerInterface;

class DitFactory
{
    private EntityManagerInterface $entityManager;
    private DitModel $ditModel;
    private HistoriqueOperationDITService $historiqueOperation;

    public function __construct(
        EntityManagerInterface $entityManager,
        DitModel $ditModel,
        HistoriqueOperationDITService $historiqueOperation
    ) {
        $this->entityManager = $entityManager;
        $this->ditModel = $ditModel;
        $this->historiqueOperation = $historiqueOperation;
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

    private function getP2NiveauUrgence(): ?string
    {
        $worNiveauUrgenceModel = new WorNiveauUrgenceModel();
        return $worNiveauUrgenceModel->getP2Description();
    }
}
