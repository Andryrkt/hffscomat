<?php

namespace App\Mapper\Atelier\Dit;

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;

class DitMapper
{
    public static function toArrayDit(OrSoumissionDto $dto, array $ors)
    {
        return array_map(function ($item) use ($dto) {

            return [
                'numeroDit' => $dto->numeroDit,
                'numeroOr' => $dto->numeroOr,
                'numeroItv' => $item['NUMERO_ITV'],
                'dateSoumission' => $dto->dateSoumission,
                'heureSoumission' => $dto->heureSoumission,
                'nombreLigneItv' => $item['NOMBRE_LIGNE'],
                'montantItv' => $item['MONTANT_ITV'],
                'numeroVersion' => $dto->numeroVersion,
                'montantPiece' => $item['MONTANT_PIECE'],
                'montantMo' => $item['MONTANT_MO'],
                'montantAchatLocaux' => $item['MONTANT_ACHATS_LOCAUX'],
                'montantFraisDivers' => $item['MONTANT_DIVERS'],
                'montantLubrifiants' => $item['MONTANT_LUBRIFIANTS'],
                'libellelItv' => $item['LIBELLE_ITV'],
                'observation' => $dto->observation,
                'statut' => ConstantStatutOr::STATUT_SOUMIS_A_VALIDATION,
                'pieceFaibleActiviteAchat' => $dto->pieceFaibleActiviteAchat,
                'codeSociete' => $dto->codeSociete,
            ];
        }, $ors);
    }

    public static function toArrayUpdateDit(string $statut)
    {

        return [
            'statut_or' => $statut,
        ];
    }
    public static function toArrayUpdateDitNumeroOr(string $statut, int $numeroOr)
    {

        return [
            'statut_or' => $statut,
            'numeroOr' => $numeroOr,
        ];
    }

    public function toExcelArray(array $dtis): array
    {
        $data = [];

        foreach ($dtis as $dti) {
            $data[] = [
                'numeroDit' => $dti->getNumeroDit(),
                'statut' => $dti->getStatut(),
                'date' => $dti->getDateCreation(),
            ];
        }

        return $data;
    }
}
