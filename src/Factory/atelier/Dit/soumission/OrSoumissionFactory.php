<?php

namespace App\Factory\atelier\Dit\Soumission;

use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\atelier\dit\soumission\ORs\TraitementFichierService;
use App\Service\security\SecurityService;

class OrSoumissionFactory
{
    public const invalidPositions = ['FC', 'FE', 'CP', 'ST'];


    public function initialisation(string $numDit, SecurityService $securityService): OrSoumissionDto
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $ditModel = new DitModel();

        $dto = new OrSoumissionDto();
        $dto->numeroDit = $numDit;
        $dto->codeSociete = $securityService->getCodeSocieteUser();
        $dto->numeroOr = $ditOrsoumisAValidationModel->recupNumeroOr($numDit, $dto->codeSociete);
        $dto->idCategorieDemande = (int) $ditModel->findIdCategorieByNumeroDit($numDit, $dto->codeSociete);
        $dto->typeOr = $ditOrsoumisAValidationModel->recupTypeOr($dto->numeroOr);

        return $dto;
    }




    public function apresSoumission(string $numDit, OrSoumissionDto $dto): OrSoumissionDto
    {

        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $ditModel = new DitModel();

        $idMaterielIps = $ditOrsoumisAValidationModel->recupNumeroMatricule($numDit, $dto->numeroOr, $dto->codeSociete);
        $dit = $ditModel->recupInformationsDit($numDit, $dto->codeSociete);
        $agServInformix = $ditModel->recupAgenceServiceDebiteur($dto->numeroOr, $dto->codeSociete);
        $pos = $ditOrsoumisAValidationModel->recupPositonOr($dto->numeroOr, $dto->codeSociete);
        $refClient = $ditOrsoumisAValidationModel->recupRefClient($dto->numeroOr, $dto->codeSociete);
        $countAgServDeb = $ditOrsoumisAValidationModel->countAgServDebit($dto->numeroOr, $dto->codeSociete);
        $numclient = $ditOrsoumisAValidationModel->getNumcli($dto->numeroOr, $dto->codeSociete);
        $existeNumclient = $ditOrsoumisAValidationModel->numcliExiste($numclient, $dto->codeSociete);


        $dto->numeroDit = $numDit;
        $dto->numeroOr = $ditOrsoumisAValidationModel->recupNumeroOr($numDit, $dto->codeSociete);
        $dto->numeroVersion = $this->getVersion($numDit, $dto->numeroOr);
        $dto->heureSoumission =  date('H:i');
        $dto->dateSoumission = date('Y-m-d');
        $dto->originalNamePj1 = $dto->pieceJoint01->getClientOriginalName();
        $dto->estIdMaterielDifferent = (int)$dit['id_materiel'] !== $idMaterielIps;
        $dto->statut = $ditOrsoumisAValidationModel->findByStatut($dto->numeroOr, $dto->codeSociete, $dto->numeroVersion);
        $dto->nmbrOr_soumis = $ditOrsoumisAValidationModel->getNbrOrSoumis($dto->numeroOr, $dto->codeSociete);

        $dto->isVerifiedDatePlanning = $this->verificationDatePlanning($dto->numeroOr, $dto->codeSociete);
        $dto->isAgenceIriumInIPS = !in_array($dit['agence_service_debiteur'], $agServInformix); // TRUE si le code agence et service debiteur (80-INF) du DIT est dans IPS
        $dto->refClient = empty($refClient); // TRUE si une tableau vide
        $dto->countAgServDebit = $countAgServDeb;
        $dto->existeNumclient = $existeNumclient != 'existe_bdd'; // TRUE si différent 'existe_bdd'
        $dto->isValidPosition = in_array($pos, self::invalidPositions); // retourne TRUE si la position est parmis 'FC', 'FE', 'CP', 'ST'


        $dto->pieceFaibleActiviteAchat = $this->pieceFaibleAchat($dto->numeroOr, $dto->codeSociete);

        return $dto;
    }

    private  function pieceFaibleAchat(string $numeroOr, string $codeSociete)
    {
        $pieceFaibleAchat = (new TraitementFichierService)->preparationDesPiecesFaibleAchat($numeroOr, $codeSociete);
        return empty($pieceFaibleAchat) ? false : true;
    }

    private function getVersion(string $numeroOr, string $codeSociete): int
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $numeroVersionMax = $ditOrsoumisAValidationModel->findNumeroVersionMax($numeroOr, $codeSociete);
        return $this->autoIncrement($numeroVersionMax);
    }



    private function verificationDatePlanning(string $numOr, string $codeSociete): bool
    {

        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();

        $datePlannig1 = $ditOrsoumisAValidationModel->recupDatePlanning1($numOr, $codeSociete);
        $datePlannig2 = $ditOrsoumisAValidationModel->recupNbDatePlanningVide($numOr, $codeSociete);

        $aBlocker = false;
        if (empty($datePlannig1)) {
            if ((int)$datePlannig2[0]['nbplanning'] > 0) {
                $aBlocker = true;
            }
        }

        return $aBlocker;
    }


    private function autoIncrement($num)
    {
        if ($num === null) {
            $num = 0;
        }
        return $num + 1;
    }
}
