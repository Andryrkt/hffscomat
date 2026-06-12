<?php

namespace App\Mapper\Atelier\Dit\Soumission;

use App\Dto\atelier\dit\soumission\DitFactureSoumisAValidationDto;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;

class DItFactureSoumisAValidationMapper
{
    public static function map(DitFactureSoumisAValidationDto $dto)
    {
        return array_map(function ($item) use ($dto) {
            return [
                'numero_fact' => $item['numerofac'],
                'numero_dit' => $dto->numeroDit,
                'numero_or' => $item['numeroor'],
                'date_soumission' => $dto->dateSoumission,
                'numero_soumission' => $dto->numeroSoumission,
                'numero_itv' => $item['numeroitv'],
                'montant_factureitv' => $item['montantfactureitv'],
                'agence_debiteur' => $item['agencedebiteur'],
                'service_debiteur' => $item['servicedebiteur'],
                'statut' => '',
                'heure_soumission' => $dto->heureSoumission,
                'code_societe' => $dto->codeSociete
            ];
        }, $dto->infoFac);
    }

    public static function mapFacture(DitFactureSoumisAValidationDto $dto)
    {
        return array_map(function ($item) use ($dto) {
            $dtoFac = new DitFactureSoumisAValidationDto();
            $dtoFac->numeroDit = $dto->numeroDit;
            $dtoFac->numeroOr = $item['numeroor'];
            $dtoFac->numeroFact = $item['numerofac'];
            $dtoFac->heureSoumission = $dto->heureSoumission;
            $dtoFac->dateSoumission = $dto->dateSoumission;
            $dtoFac->numeroSoumission = $dto->numeroSoumission;
            $dtoFac->numeroItv = $item['numeroitv'];
            $dtoFac->montantFactureItv = $item['montantfactureitv'];
            $dtoFac->agenceDebiteur = $item['agencedebiteur'];
            $dtoFac->serviceDebiteur = $item['servicedebiteur'];
            $dtoFac->mttItv = $item['montant'];
            $dtoFac->codeSociete = $dto->codeSociete;
            $dtoFac->libelleItv = $item['libelleitv'] === null ? '' : $item['libelleitv'];
            $dtoFac->statut = '';
            $dtoFac->statutItv = $dto->statutItv;
            $dtoFac->agServDebDit = $dto->agenceDebiteur;
            return $dtoFac;
        }, $dto->infoFac);
    }

    public static function mapOR(array $dataOr)
    {
        return array_map(function ($item) {
            /** @var OrSoumissionDto $dtoOr  */
            $dtoOr = new OrSoumissionDto();
            $dtoOr->numeroOr = $item['numeroor'];
            $dtoOr->numeroItv = $item['numeroitv'];
            $dtoOr->nombreLigneItv = $item['nombreligneitv'];
            $dtoOr->montantItv = $item['montantitv'];
            $dtoOr->numeroVersion = $item['numeroversion'];
            $dtoOr->montantPiece = $item['montantpiece'];
            $dtoOr->montantMo = $item['montantmo'];
            $dtoOr->montantAchatLocaux = $item['montantachatlocaux'];
            $dtoOr->montantFraisDivers = $item['montantfraisdivers'];
            $dtoOr->montantLubrifiants = $item['montantlubrifiants'];
            $dtoOr->libellelItv = $item['libellelitv'];
            $dtoOr->dateSoumission = $item['datesoumission'];
            $dtoOr->heureSoumission = $item['heuresoumission'];
            $dtoOr->statut = $item['statut'];
            $dtoOr->migration = $item['migration'];
            $dtoOr->numeroDit = $item['numerodit'];
            $dtoOr->observation = $item['observation'];
            $dtoOr->codeSociete = $item['code_societe'];

            return $dtoOr;
        }, $dataOr);
    }


    public static function updateDit(string $etatFacture): array
    {
        return [ 'etat_facturation' => $etatFacture];
    }
}
