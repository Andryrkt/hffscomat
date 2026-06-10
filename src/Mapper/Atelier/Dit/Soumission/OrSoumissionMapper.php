<?php

namespace App\Mapper\Atelier\Dit\Soumission;

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;

class OrSoumissionMapper
{
    public static function dataToDto(array $data)
    {
        return array_map(function ($item) {
            $dto = new OrSoumissionDto();
            $dto->numeroOr = $item['numeroor'];
            $dto->numeroItv = $item['numeroitv'];
            $dto->nombreLigneItv = $item['nombreligneitv'];
            $dto->montantItv = $item['montantitv'];
            $dto->numeroVersion = $item['numeroversion'];
            $dto->montantPiece = $item['montantpiece'];
            $dto->montantMo = $item['montantmo'];
            $dto->montantAchatLocaux = $item['montantachatlocaux'];
            $dto->montantFraisDivers = $item['montantfraisdivers'];
            $dto->montantLubrifiants = $item['montantlubrifiants'];
            $dto->libellelItv = $item['libellelitv'];
            $dto->dateSoumission = $item['datesoumission'];
            $dto->heureSoumission = $item['heuresoumission'];
            $dto->statut = $item['statut'];
            $dto->numeroDit = $item['numerodit'];
            $dto->observation = $item['observation'];
            $dto->codeSociete = $item['code_societe'];

            return $dto;
        }, $data);
    }

    public static function DtotoArrayOr(OrSoumissionDto $dto, array $ors)
    {
        return array_map(function ($item) use ($dto) {
            return [
                'numerodit' => $dto->numeroDit,
                'numeroor' => $dto->numeroOr,
                'numeroitv' => $item['numero_itv'],
                'datesoumission' => $dto->dateSoumission,
                'heuresoumission' => $dto->heureSoumission,
                'nombreligneitv' => $item['nombre_ligne'],
                'montantitv' => $item['montant_itv'],
                'numeroversion' => $dto->numeroVersion,
                'montantpiece' => $item['montant_piece'],
                'montantmo' => $item['montant_mo'],
                'montantachatlocaux' => $item['montant_achats_locaux'],
                'montantfraisdivers' => $item['montant_divers'],
                'montantlubrifiants' => $item['montant_lubrifiants'],
                'libellelitv' => $item['libelle_itv'],
                'observation' => $dto->observation,
                'statut' => ConstantStatutOr::STATUT_SOUMIS_A_VALIDATION,
                'code_societe' => $dto->codeSociete,
                'piece_faible_activite_achat' => $dto->pieceFaibleActiviteAchat

            ];
        }, $ors);
    }

    public static function toArrayUpdateDit(OrSoumissionDto $dto)
    {
        return [
            'statut_or' => ConstantStatutOr::STATUT_SOUMIS_A_VALIDATION,
            'numero_or' => $dto->numeroOr,
            'id_statut_demande' => 53 // on change le statut de la DIT en 'CLOTUREE VALIDEE'
        ];
    }
}
