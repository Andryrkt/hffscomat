<?php

namespace App\Factory\atelier\Dit\Soumission;

use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\atelier\dit\soumission\ORs\TraitementFichierService;
use DateTime;

class OrSoumissionFactory
{
    public const invalidPositions = ['FC', 'FE', 'CP', 'ST'];


    public function initialisation(string $numDit, ?string $numOr, string $codeSociete): OrSoumissionDto
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $ditModel = new DitModel();

        $dto = new OrSoumissionDto();
        $dto->numeroDit = $numDit;
        $dto->numeroOr = $numOr;
        $dto->codeSociete = $codeSociete;
        $dto->idCategorieDemande = (int) $ditModel->findIdCategorieByNumeroDit($numDit, $codeSociete);
        $dto->typeOr = $ditOrsoumisAValidationModel->recupTypeOr($numOr);

        return $dto;
    }


    public function fromArrayToDto(array $data): OrSoumissionDto
    {
        $dto = new OrSoumissionDto();

        // =========================
        // IDENTIFIANTS
        // =========================
        $dto->numeroDit = $row['numerodit'] ?? null;
        $dto->numeroOr = $row['numeroor'] ?? null;
        $dto->numeroItv = isset($row['numeroitv']) ? (int) $row['numeroitv'] : 0;
        $dto->codeSociete = $row['code_societe'] ?? null;

        // =========================
        // VERSION / STATUT
        // =========================
        $dto->numeroVersion = isset($row['numeroversion'])
            ? (int) $row['numeroversion']
            : 0;

        $dto->statut = $row['statut'] ?? null;
        $dto->migration = $row['migration'] ?? null;

        // =========================
        // DATES / HEURES
        // =========================
        $dto->dateSoumission = isset($row['date_soumission']) && $row['date_soumission']
            ? new \DateTime($row['date_soumission'])
            : null;

        $dto->heureSoumission = $row['heure_soumission'] ?? null;

        // =========================
        // COMPTEURS
        // =========================
        $dto->nombreLigneItv = isset($row['nombre_ligne_itv'])
            ? (int) $row['nombre_ligne_itv']
            : 0;

        $dto->nmbrOr_soumis = isset($row['nmbr_or_soumis'])
            ? (int) $row['nmbr_or_soumis']
            : 0;

        // =========================
        // MONTANTS
        // =========================
        $dto->montantItv = isset($row['montant_itv'])
            ? (float) $row['montant_itv']
            : 0.0;

        $dto->montantPiece = isset($row['montant_piece'])
            ? (float) $row['montant_piece']
            : 0.0;

        $dto->montantMo = isset($row['montant_mo'])
            ? (float) $row['montant_mo']
            : 0.0;

        $dto->montantAchatLocaux = isset($row['montant_achat_locaux'])
            ? (float) $row['montant_achat_locaux']
            : 0.0;

        $dto->montantFraisDivers = isset($row['montant_frais_divers'])
            ? (float) $row['montant_frais_divers']
            : 0.0;

        $dto->montantLubrifiants = isset($row['montant_lubrifiants'])
            ? (float) $row['montant_lubrifiants']
            : 0.0;

        // =========================
        // TEXTES
        // =========================
        $dto->libellelItv = $row['libelle_itv'] ?? null;
        $dto->observation = $row['observation'] ?? null;

        // =========================
        // MATERIEL / METADATA
        // =========================
        $dto->id_materiel_ips = $row['id_materiel_ips'] ?? null;
        $dto->info_materiel = $row['info_materiel'] ?? null;

        // =========================
        // PIECES (IMPORTANT)
        // =========================
        // Ne jamais hydrater UploadedFile depuis SQL
        $dto->originalNamePj1 = $row['original_name_pj1'] ?? null;

        // =========================

        $dto->isExistDatePlaning = isset($row['is_exist_date_planning'])
            ? (bool) $row['is_exist_date_planning']
            : false;

        return $dto;
    }

    public function fromFirstResult(?array $results): ?OrSoumissionDto
    {
        return !empty($results)
            ? $this->fromArrayToDto($results[0])
            : null;
    }



    public function apresSoumission(string $numDit, string $numOr, OrSoumissionDto $dto): OrSoumissionDto
    {

        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $ditModel = new DitModel();

        $idMaterielIps = $ditOrsoumisAValidationModel->recupNumeroMatricule($numDit, $numOr, $dto->codeSociete);
        $dit = $ditModel->recupInformationsDit($numDit, $dto->codeSociete);
        $agServInformix = $ditModel->recupAgenceServiceDebiteur($numOr, $dto->codeSociete);
        $pos = $ditOrsoumisAValidationModel->recupPositonOr($numOr, $dto->codeSociete);
        $refClient = $ditOrsoumisAValidationModel->recupRefClient($numOr, $dto->codeSociete);
        $countAgServDeb = $ditOrsoumisAValidationModel->countAgServDebit($numOr, $dto->codeSociete);
        $numclient = $ditOrsoumisAValidationModel->getNumcli($numOr, $dto->codeSociete);
        $existeNumclient = $ditOrsoumisAValidationModel->numcliExiste($numclient, $dto->codeSociete);


        $dto->numeroDit = $numDit;
        $dto->numeroOr = $numOr;
        $dto->numeroVersion = $this->getVersion($numDit, $numOr);
        $dto->heureSoumission =  date('H:i');
        $dto->dateSoumission = date('Y-m-d');
        $dto->originalNamePj1 = $dto->pieceJoint01->getClientOriginalName();
        $dto->estIdMaterielDifferent = (int)$dit['id_materiel'] !== $idMaterielIps;
        $dto->statut = $ditOrsoumisAValidationModel->findByStatut($numOr, $dto->codeSociete, $dto->numeroVersion);
        $dto->nmbrOr_soumis = $ditOrsoumisAValidationModel->getNbrOrSoumis($numOr, $dto->codeSociete);

        $dto->isVerifiedDatePlanning = $this->verificationDatePlanning($numOr, $dto->codeSociete);
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
