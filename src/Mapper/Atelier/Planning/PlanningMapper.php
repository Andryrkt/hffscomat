<?php

namespace App\Mapper\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningMaterielDto;

class PlanningMapper
{
    public function toDtoArray(array $data, array $back): array
    {
        $res = [];
        foreach ($data as $item) {
            $res[] = $this->mapToMaterielDto($item, $back);
        }
        return $res;
    }

    public function mapToMaterielDto(array $item, array $back): PlanningMaterielDto
    {

        $dto = new PlanningMaterielDto();

        $dto->codeSuc        = $item['code_suc'] ?? '';
        $dto->libSuc         = $item['lib_suc'] ?? '';
        $dto->codeServ       = $item['code_serv'] ?? '';
        $dto->libServ        = $item['lib_serv'] ?? '';
        $dto->commentaire    = $item['commentaire'] ?? '';
        $dto->client         = $item['client'] ?? '';

        $dto->idMat          = $item['id_mat'] ?? '';
        $dto->markMat        = $item['mark_mat'] ?? '';
        $dto->typeMat        = $item['type_mat'] ?? '';
        $dto->numSerie       = $item['num_serie'] ?? '';
        $dto->numParc        = $item['num_parc'] ?? '';
        $dto->casier         = $item['casier'] ?? '';

        $dto->annee          = $item['annee'] ?? null;
        $dto->mois           = $item['mois'] ?? null;
        $dto->orItv          = $item['or_itv'] ?? '';
        $dto->numOr          = $item['num_or'] ?? '';
        $dto->itv            = $item['itv'] ?? '';

        $dto->qteCdm         = (float)($item['qte_cmd'] ?? 0);
        $dto->qteLiv         = (float)($item['qte_liv'] ?? 0);
        $dto->qteAll         = (float)($item['qte_all'] ?? 0);
        $dto->qteReliquat    = (float)($item['qte_reliquat'] ?? 0);
        $dto->qteResOr       = (float)($item['qte_res_or'] ?? 0);

        $dto->statutB        = $item['statut_b'] ?? '';
        $dto->statut         = $item['statut'] ?? '';
        $dto->statutCtrmq    = $item['statut_ctrmq'] ?? '';
        $dto->statutCtrmqCis = $item['statut_ctrmq_cis'] ?? '';
        $dto->datePlanning   = $item['date_planning'] ?? null;
        $dto->dateStatut     = $item['date_statut'] ?? null;
        $dto->cst            = $item['cst'] ?? '';
        $dto->ref            = $item['ref'] ?? '';
        $dto->desi           = $item['desi'] ?? '';
        $dto->numCmd         = $item['num_cmd'] ?? '';
        $dto->numCis         = $item['num_cis'] ?? '';
        $dto->numCmdCis      = $item['num_cmd_cis'] ?? '';
        $dto->message        = $item['message'] ?? '';

        $estBackOrder = !empty($back) && in_array($dto->orItv, $back['num_or_itvs']) && $dto->qteAll + $dto->qteLiv <= 0;
        $estBackOrderCo = $dto->qteReliquat > 0 && $item["nat_cm"] === "O";

        $dto->addMoisDetails([
            'mois'                  => $dto->mois,
            'annee'                 => $dto->annee,
            'orIntv'                => $dto->orItv,
            'commentaire'           => $dto->commentaire,
            'classeStatutLivraison' => $this->calculerClasse($dto, $estBackOrder, $estBackOrderCo),
        ]);

        return $dto;
    }

    /**
     * Classe CSS selon l'état de livraison/disponibilité des pièces
     */
    private function calculerClasse(PlanningMaterielDto $dto, bool $estBackOrder, bool $estBackOrderCo): string
    {
        $cdm = $dto->qteCdm;
        $liv = $dto->qteLiv;
        $all = $dto->qteAll;

        $classe = '';

        if ($cdm == $liv)
            $classe = 'tout-livre bg-success text-white';
        elseif ($liv > 0 && $cdm > $liv + $all)
            $classe = 'partiellement-livre bg-warning text-white';
        elseif ($liv == 0 && $all > 0 && $cdm != $all)
            $classe = 'partiellement-dispo bg-info text-white';
        elseif (($cdm == $all && $liv < $cdm) || ($all > 0 && $cdm == $all + $liv))
            $classe = 'complet-non-livre bg-primary text-white';

        if ($estBackOrder) $classe .= ' back-order border-end border-start border-danger border-3';
        if ($estBackOrderCo) $classe .= ' bo-co bordered-bo-co';

        return $classe;
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
