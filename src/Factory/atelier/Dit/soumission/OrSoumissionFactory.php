<?php

namespace App\Factory\atelier\Dit\soumission;

use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Model\dit\DitModel;
use App\Model\dit\DitOrSoumisAValidationModel;
use DateTime;

class OrSoumissionFactory
{


    public function initialisation(string $numDit, string $numOr, string $codeSociete): OrSoumissionDto
    {
        $dto = new OrSoumissionDto();
        $dto->numeroDit = $numDit;
        $dto->numeroOr = $numOr;
        $dto->codeSociete = $codeSociete;
        return $dto;
    }



    public function apresSoumission(string $numDit, string $numOr, OrSoumissionDto $dto): OrSoumissionDto
    {

        $modelDitOrSoumisAValidation = new DitOrSoumisAValidationModel();
        $ditModel = new DitModel();

        $id_materiel_ips = $modelDitOrSoumisAValidation->recupNumeroMatricule($numDit, $numOr, $dto->codeSociete);
        $infos_materiel = $ditModel->recupInformationsDit($numDit, $dto->codeSociete);

        $dto->numeroDit = $numDit;
        $dto->numeroOr = $numOr;
        $dto->numeroVersion = $this->getVersion($numDit, $numOr);
        $dto->heureSoumission =  $this->getTime();
        $dto->dateSoumission = new DateTime();
        $dto->originalNamePj1 = $dto->pieceJoint01->getClientOriginalName();
        $dto->id_materiel_ips = $id_materiel_ips;
        $dto->info_materiel = $infos_materiel;
        $dto->statut = $modelDitOrSoumisAValidation->findByStatut($numOr, $dto->codeSociete, $dto->numeroVersion);
        $dto->nmbrOr_soumis = $modelDitOrSoumisAValidation->getNbrOrSoumis($numOr, $dto->codeSociete);
        return $dto;
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


    private function getTime()
    {
        date_default_timezone_set('Indian/Antananarivo');
        return date("H:i");
    }
    private function autoIncrement($num)
    {
        if ($num === null) {
            $num = 0;
        }
        return $num + 1;
    }
}
