<?php

namespace App\Mapper\Atelier\Dit;

use App\Dto\atelier\dit\soumission\OrSoumissionDto;

class DitMapper
{
    public static function toArrayDit(OrSoumissionDto $dto)
    {
        return [
            'numeroDit' => $dto->numeroDit,
            'numeroOr' => $dto->numeroOr,
            'numeroItv' => $dto->numeroItv,
            'dateSoumission' => $dto->dateSoumission,
            'heureSoumission' => $dto->heureSoumission,
            'nombreLigneItv' => $dto->nombreLigneItv,
            'montantItv' => $dto->montantItv,
            'numeroVersion' => $dto->numeroVersion,
            'montantPiece' => $dto->montantPiece,
            'montantMo' => $dto->montantMo,
            'montantAchatLocaux' => $dto->montantAchatLocaux,
            'montantFraisDivers' => $dto->montantFraisDivers,
            'montantLubrifiants' => $dto->montantLubrifiants,
            'libellelItv' => $dto->libellelItv,
            'observation' => $dto->observation,

            'pieceJoint01' => $dto->pieceJoint01 ?? null,
            'pieceJoint02' => $dto->pieceJoint02 ?? null,
            'pieceJoint03' => $dto->pieceJoint03 ?? null,
            'pieceJoint04' => $dto->pieceJoint04 ?? null,


            'statut' => $dto->statut,
            'migration' => $dto->migration,
            'pieceFaibleActiviteAchat' => $dto->pieceFaibleActiviteAchat,
            'codeSociete' => $dto->codeSociete,
            'isExistDatePlaning' => $dto->isExistDatePlaning,
        ];
    }
    public static function toArrayUpdateDit(string $statut)
    {

        return [
            'statut_or' => $statut,
        ];
    }
}
