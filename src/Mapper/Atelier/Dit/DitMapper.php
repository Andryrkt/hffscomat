<?php

namespace App\Mapper\Atelier\Dit;

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Service\atelier\dit\soumission\ORs\TraitementFichierService;

class DitMapper
{
    public static function toArrayDit(OrSoumissionDto $dto, array $ors)
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
