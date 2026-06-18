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

    public function mapToMaterielDto(array $item, array $orItvBack): PlanningMaterielDto
    {

        $dto = new PlanningMaterielDto();

        $dto->codeSuc     = $item['code_suc'] ?? '';
        $dto->libSuc      = $item['lib_suc'] ?? '';
        $dto->codeServ    = $item['code_serv'] ?? '';
        $dto->libServ     = $item['lib_serv'] ?? '';
        $dto->commentaire = $item['commentaire'] ?? '';

        $dto->idMat       = $item['id_mat'] ?? '';
        $dto->markMat     = $item['mark_mat'] ?? '';
        $dto->typeMat     = $item['type_mat'] ?? '';
        $dto->numSerie    = $item['num_serie'] ?? '';
        $dto->numParc     = $item['num_parc'] ?? '';
        $dto->casier      = $item['casier'] ?? '';

        $dto->annee       = $item['annee'] ?? null;
        $dto->mois        = $item['mois'] ?? null;
        $dto->orItv       = $item['or_itv'] ?? '';
        $dto->numOr       = $item['num_or'] ?? '';
        $dto->itv         = $item['itv'] ?? '';

        $dto->qteCdm      = (float)($item['qte_cmd'] ?? 0);
        $dto->qteLiv      = (float)($item['qte_liv'] ?? 0);
        $dto->qteAll      = (float)($item['qte_all'] ?? 0);
        $dto->qteReliquant = (float)($item['qte_reliquant'] ?? 0);
        $dto->qteResOr    = (float)($item['qte_res_or'] ?? 0);

        $dto->statutB     = $item['statut_b'] ?? '';
        $dto->statut      = $item['statut'] ?? '';
        $dto->statutCtrmq = $item['statut_ctrmq'] ?? '';
        $dto->statutCtrmqCis = $item['statut_ctrmq_cis'] ?? '';
        $dto->datePlanning = $item['date_planning'] ?? null;
        $dto->dateStatut = $item['date_statut'] ?? null;

        $dto->cst         = $item['cst'] ?? '';
        $dto->ref         = $item['ref'] ?? '';
        $dto->desi        = $item['desi'] ?? '';
        $dto->numCmd      = $item['num_cmd'] ?? '';
        $dto->numCis      = $item['num_cis'] ?? '';
        $dto->numCmdCis   = $item['num_cmd_cis'] ?? '';
        $dto->message     = $item['message'] ?? '';

        $detail = [
            'mois'       => $dto->mois,
            'annee'      => $dto->annee,
            'orIntv'     => $dto->orItv,
            'qteCdm'     => $dto->qteCdm,
            'qteLiv'     => $dto->qteLiv,
            'qteAll'     => $dto->qteAll,
            'commentaire'=> $dto->commentaire,
            'back'       => in_array($dto->orItv, $orItvBack, true),
            'statut'     => $dto->statut,
        ];
        $dto->addMoisDetails($detail);

        return $dto;
    }

    /**
     * Regroupe par idMat
     * @param PlanningMaterielDto[] $dtos
     * @return PlanningMaterielDto[]
     */
    public function groupByMateriel(array $dtos): array
    {
        $grouped = [];
        foreach ($dtos as $dto) {
            $key = $dto->idMat;
            if (!isset($grouped[$key])) {
                $grouped[$key] = $dto;
            } else {
                $grouped[$key]->addMoisDetails($dto->moisDetails[0] ?? []);
            }
        }
        return $grouped;
    }

}