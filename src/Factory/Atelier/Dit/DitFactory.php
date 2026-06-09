<?php

namespace App\Factory\Atelier\Dit;

use App\Constants\admin\ApplicationConstant;
use App\Constants\atelier\dit\StatutDitConstant;
use App\Dto\Atelier\Dit\DitDto;
use App\Entity\admin\Application;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Service\autres\AutoIncDecService;
use App\Service\security\SecurityService;
use Doctrine\ORM\EntityManagerInterface;

class DitFactory
{

    private SecurityService $securityService;
    private EntityManagerInterface $em;

    public function __construct(
        SecurityService $securityService,
        EntityManagerInterface $em
    ) {

        $this->securityService = $securityService;
        $this->em = $em;
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
        $dto->heureDemande = date('H:i');
        $dto->dateDemande = date('Y-m-d');
        $dto->mailDemandeur = $this->securityService->getDataService()->getUserMail();
        $dto->statutDemande = StatutDitConstant::STATUT_A_AFFECTER;
        $dto->numeroDemandeIntervention = $this->genererNumeroDit();
        $dto->clientSousContrat = $dto->clientSousContrat === null ? 'NON' : $dto->clientSousContrat;
        $dto->demandeDevis = $dto->demandeDevis === null ? 'NON' : $dto->demandeDevis;

        // Agence et Service
        $dto->agenceServiceEmetteur = trim(explode(' ', $dto->agenceEmetteur)[0]) . '-' . trim(explode(' ', $dto->serviceEmetteur)[0]);
        $dto->agenceServiceDebiteur = $dto->agence->getCodeAgence() . '-' . $dto->service->getCodeService();

        // info materiel
        $ditModel = new DitModel();
        $infoMaterielEtBilanFinancier = $ditModel->findAll($dto->idMateriel, $dto->numParc, $dto->numSerie)[0] ?? [];
        $dto->designation = $infoMaterielEtBilanFinancier['designation'];
        $dto->modele = $infoMaterielEtBilanFinancier['modele'];
        $dto->constructeur = $infoMaterielEtBilanFinancier['constructeur'];
        $dto->casier = $infoMaterielEtBilanFinancier['casier_emetteur'];
        $dto->heure = $infoMaterielEtBilanFinancier['heure'];
        $dto->km = $infoMaterielEtBilanFinancier['km'];
        // Bilan Financiere
        $dto->coutAcquisition = (float)$infoMaterielEtBilanFinancier['prix_achat'];
        $dto->amortissement = (float)$infoMaterielEtBilanFinancier['amortissement'];
        $dto->valeurNetComptable = $dto->coutAcquisition - $dto->amortissement;
        $dto->chargeEntretient = (float)$infoMaterielEtBilanFinancier['chargeentretien'];
        $dto->chargeLocative = (float)$infoMaterielEtBilanFinancier['chargelocative'];
        $dto->chiffreAffaire = (float)$infoMaterielEtBilanFinancier['chiffreaffaires'];
        $dto->resultatExploitation = $dto->chiffreAffaire - ($dto->chargeEntretient + $dto->chargeLocative);

        return $dto;
    }

    /**
     * Recupératin du niveau d'urence P2
     *
     * @return string|null
     */
    private function getP2NiveauUrgence(): ?string
    {
        $worNiveauUrgenceModel = new WorNiveauUrgenceModel();
        return $worNiveauUrgenceModel->getP2Description();
    }

    private function genererNumeroDit(): string
    {
        $application = $this->em->getRepository(Application::class)->findOneBy(['codeApp' => ApplicationConstant::CODE_DIT]);
        // 1. recuperation du dernière numero demande d'intervention
        $dernierNumeroDit = $application->getDerniereId();

        // 2. recupération du nouveau numeéro DIT  décrémenter par le  dernière numero d'intervention recupérer dans la table applications
        $numeroDemandeIntervention = AutoIncDecService::autoGenerateNumero(ApplicationConstant::CODE_DIT, $dernierNumeroDit, false);

        // 3. mise à jour du colonne dernier_id dans la table application par le nouveau numero DIT
        $application->setDerniereId($numeroDemandeIntervention);
        $this->em->persist($application);
        $this->em->flush();

        return $numeroDemandeIntervention;
    }
}
