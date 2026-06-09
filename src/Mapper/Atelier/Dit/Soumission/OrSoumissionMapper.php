<?php

namespace App\Mapper\Atelier\Dit\Soumission;

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
}
