<?php

namespace App\Mapper\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningMaterielDto;
use App\Model\Atelier\Dit\DitModel;

class PlanningMapper
{

    private DitModel $ditModel;

    public function __construct()
    {
        $this->ditModel = new DitModel();
    }

    public function toDtoArray(array $data, array $orItvBack): array
    {
        $res = [];
        foreach ($data as $item) {
            $res[] = $this->mapToMaterielDto($item, $orItvBack);
        }
        return $res;
    }

    /**
     * @param array<PlanningMaterielDto> $data
     * @param array $orItvBack
     * @return array
     */
    public function getMaterielData(array $data, array $orItvBack): array
    {
        $data = $this->toDtoArray($data, $orItvBack);
        $res = [];
        foreach ($data as $item) {
            $key = $item->idMat;
            if (!isset($res[$key])) { $res[$key] = $item; }
            else { $res[$key]->addMoisDetails($item->moisDetails); }
        }
        return $res;
    }

    public function mapToMaterielDto(array $item, array $orItvBack): PlanningMaterielDto
    {

        $dto = new PlanningMaterielDto();

        $dto->codeSuc = $item['codesuc'] ?? '';
        $dto->libsuc = $item['libsuc'] ?? '';
        $dto->codeServ = $item['codeserv'] ?? '';
        $dto->libServ = $item['libserv'] ?? '';
        $dto->idMat = $item['idmat'] ?? '';
        $dto->commentaire = $item['commentaire'] ?? '';
        $dto->markMat = $item['markmat'] ?? '';
        $dto->typeMat = $item['typemat'] ?? '';
        $dto->numSerie = $item['numserie'] ?? '';
        $dto->numParc = $item['numparc'] ?? '';
        $dto->casier = $item['casier'] ?? '';
        $dto->annee = $item['annee'] ?? null;
        $dto->mois = $item['mois'] ?? null;
        $dto->orIntv = $item['orintv'] ?? '';
        $dto->qteCdm = $item['qtecmd'] ?? 0.0;
        $dto->qteLiv = $item['qtliv'] ?? 0.0;
        $dto->qteAll = $item['qteall'] ?? 0.0;

        $detail = [
            'mois' => $dto->mois,
            'annee' => $dto->annee,
            'orIntv' => $dto->orIntv,
            'qteCdm' => $dto->qteCdm,
            'qteLiv' => $dto->qteLiv,
            'qteAll' => $dto->qteAll,
            //TODO - add num dit
            //TODO - add migration
            'commentaire' => $dto->commentaire,
            'back' => in_array($dto->orIntv, $orItvBack)
        ];
        $dto->addMoisDetails($detail);

        return $dto;
    }

}