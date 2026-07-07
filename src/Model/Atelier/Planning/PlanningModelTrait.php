<?php

namespace App\Model\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Service\GlobalVariablesService;

trait PlanningModelTrait
{
    private SelectWhereCondition $selectCond;

    private function getFactureCondition(PlanningSearchDto $dto): string
    {
        $all = ['FC', 'FE', 'CP', 'ST', 'EC'];
        $noEc = ['FC', 'FE', 'CP', 'ST'];
        switch ($dto->facture) {
            case "TOUS":
                return $this->selectCond->in('sitv_pos', $all);
            case "FACTURE":
                return $this->selectCond->in('sitv_pos', $noEc);
            case "ENCOURS":
                return $this->selectCond->ni('sitv_pos', $all);
        }
        return '';
    }

    private function getSectionCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->in('sitv_typitv', $dto->section);
    }

    private function getTypeLigneCondition(PlanningSearchDto $dto)
    {
        switch ($dto->typeLigne) {
            case "PIECES_MAGASIN":
                return " AND slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') AND slor_typlig = 'P' AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL') ";
                break;
            case "ACHAT_LOCAUX":
                $constructeurAchatLocaux = GlobalVariablesService::get('achat_locaux');
                return " AND slor_constp in ( $constructeurAchatLocaux )";
                break;
            case "LUBRIFIANTS":
                $constructeurLub = GlobalVariablesService::get('lub');
                return " AND slor_constp in ( $constructeurLub )  AND slor_typlig = 'P'";
                break;
            case "PNEUMATIQUES":
                $constructeurPneumatique = GlobalVariablesService::get('pneumatique');
                return " AND slor_constp in ( $constructeurPneumatique ) ";
                break;
        }
        return '';
    }

    private function getTypeLignePieceCondition(PlanningSearchDto $dto): string
    {
        switch ($dto->typeLigne)
        {
            case "PIECES_MAGASIN":
                return "and slor_constp <> 'LUB' and slor_constp not like 'Z%' and slor_typlig = 'P'";
            case "ACHAT_LOCAUX":
                return "and slor_constp = 'ZST'";
            case "LUBRIFIANTS":
                return "and slor_constp = 'LUB' and slor_typlig = 'P'";
        }
        return '';
    }

    private function getAnneeSelectByPlanning(PlanningSearchDto $dto): string
    {
        $yearsDatePlanifier = "CASE WHEN 
                                   YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) ) is Null 
                               THEN
                                   YEAR(DATE(sitv_datepla)  )
                               ELSE
                                   YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) )
                               END";

        $yearsDateNonPlanifier = " YEAR ( DATE(sitv_datdeb) ) ";

        switch ($dto->planning) {
            case "PLANIFIE":
                return $yearsDatePlanifier;
            case "NON_PLANIFIE":
                return $yearsDateNonPlanifier;
        }
        return  '';
    }

    private function getPlanningSansDateCondition(PlanningSearchDto $dto)
    {
        $condition = " AND CASE WHEN
                                    YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) ) is Null
                                THEN
                                    YEAR(DATE(sitv_datepla)  )
                                ELSE
                                    YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) )
                                END is null";
        if ($dto->planning == "NON_PLANIFIE") {
            return $condition;
        }
        return '';
    }

    private function getMonthSelectByPlanning(PlanningSearchDto $dto)
    {
        $monthDatePlanifier = " CASE WHEN 
                                    MONTH ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) ) is Null 
                                THEN
                                    MONTH(DATE(sitv_datepla)  )
                                ELSE
                                    MONTH ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) )
                                END  ";
        $monthDateNonPlanifier =  " MONTH ( DATE(sitv_datdeb) ) ";
        switch ($dto->planning) {
            case "PLANIFIE":
                return $monthDatePlanifier;
            case "NON_PLANIFIE":
                return $monthDateNonPlanifier;
        }
        return  '';
    }

    private function getDateDebutCondition(PlanningSearchDto $dto)
    {
        if (!empty($dto->dateDebut)) {
            $monthDatePlanifier = " CASE WHEN
                                        (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id )  is Null 
                                    THEN
                                        DATE(sitv_datepla)  
                                    ELSE
                                         (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) 
                                    END";
            $monthDateNonPlanifier =  "  DATE(sitv_datdeb)  ";
            switch ($dto->planning) {
                case "PLANIFIE":
                    return " AND " . $monthDatePlanifier . " >= '" . $dto->dateDebut->format("d/m/Y") . "'";
                case "NON_PLANIFIE":
                    return " AND " . $monthDateNonPlanifier . " >= '" . $dto->dateDebut->format("d/m/Y") . "'";
            }
        }
        return '';
    }

    private function getDateFinCondition(PlanningSearchDto $dto)
    {

        if (!empty($dto->dateFin)) {
            $monthDatePlanifier = " CASE WHEN 
                                    (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id )  is Null 
                                THEN
                                    DATE(sitv_datepla)  
                                ELSE
                                     (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) 
                                END  ";
            $monthDateNonPlanifier =  " DATE(sitv_datdeb)  ";
            switch ($dto->planning) {
                case "PLANIFIE":
                    return " AND " . $monthDatePlanifier . " <= '" . $dto->dateFin->format("d/m/Y") . "'";
                    break;
                case "NON_PLANIFIE":
                    return " AND " . $monthDateNonPlanifier . " <= '" . $dto->dateFin->format("d/m/Y") . "'";
            }
        }
        return '';
    }

    private function getInterneExterneCondition(PlanningSearchDto $dto)
    {
        switch ($dto->interneExterne) {
            case "INTERNE":
                return " AND SITV_NATOP = 'CES'  and SITV_TYPEOR not in ('501','601','602','603','604','605','606','607','608','609','610','611','701','702','703','704','705','706')";
            case "EXTERNE":
                return "AND seor_numcli >1 ";
        }
        return '';
    }

    private function getAgenceCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->eq('seor_succ', $dto->agence);
    }
    private function getAgenceDebiteCondition(PlanningSearchDto $dto)
    {
        // AND sitv_succdeb in ('01','02','90','92','40','60','50','40','30','20')
        return $this->selectCond->eq('sitv_succdeb', $dto->agenceDebite);
    }
    private function getServiceDebiteCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->in('sitv_servdeb', $dto->serviceDebite);
    }
    private function getIdMaterielCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->eq('mmat_nummat', $dto->idMat);
    }
    private function getNumOrCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->eq('slor_numor', $dto->numOr);
    }
    private function getNumSerieCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->eq('mmat_numserie', $dto->numSerie);
    }

    private function getNumParcCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->eq('mmat_recalph', $dto->numParc);
    }
    private function getCasierCondition(PlanningSearchDto $dto)
    {
        return $this->selectCond->like('mmat_numparc', $dto->casier);
    }

    private function getOrValidBackOrderCondition(PlanningSearchDto $dto, array $orItvBack, array $orsValides, array $orsSoumis): string
    {
        if (!empty($orsValides))
        {
            if ($dto->orNonValiderDw)
                return $this->selectCond->ni('cast(seor_numor as varchar(10))', $orsSoumis);
            return $this->selectCond->in('cast(seor_numor as varchar(10))', $orsValides);
        }
        return '';
    }

    private function getStatutBExpression(): string
    {
        return "
        CASE 
            WHEN q.qte_cmd = q.qte_liv THEN 'TOUT LIVRE'
            WHEN q.qte_liv > 0 AND q.qte_liv < q.qte_cmd THEN 'PARTIELLEMENT LIVRE'
            WHEN q.qte_all > 0 AND q.qte_liv = 0 THEN 'COMPLET NON LIVRE'
            ELSE ''
        END
    ";
    }

    private function getPieceStatutExpression(): string
    {
        return "
        CASE 
            WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) AND slor_qterel > 0 
                THEN 'A LIVRER'
            WHEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) = slor_qteres 
                 AND slor_qterel = 0 AND slor_qterea = 0 
                THEN 'DISPO STOCK'
            WHEN slor_qterea = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) 
                THEN 'LIVRE'
            ELSE COALESCE((
                SELECT libelle_type 
                FROM gcot_acknow_cat 
                WHERE Numero_PO = slor_numcf 
                  AND Parts_Number = slor_refp 
                  AND Parts_CST = slor_constp 
                  AND Line_Number = slor_noligncm 
                  AND id_gcot_acknow_cat = (
                      SELECT MAX(id_gcot_acknow_cat)
                      FROM gcot_acknow_cat 
                      WHERE Numero_PO = slor_numcf 
                        AND Parts_Number = slor_refp 
                        AND Parts_CST = slor_constp 
                        AND Line_Number = slor_noligncm
                  )
            ), '')
        END
    ";
    }

    protected function getDateStatutExpression(): string
    {
        return "
            CASE 
                WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) 
                     AND slor_qterel > 0 THEN
                    TO_CHAR((
                        SELECT MIN(spic_datepic)
                        FROM sav_pic
                        WHERE spic_numor = slor_numor
                          AND spic_refp = slor_refp
                          AND spic_nolign = slor_nolign
                    ), '%Y-%m-%d')
                
                WHEN slor_qterea = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN
                    TO_CHAR((
                        SELECT sliv_date 
                        FROM sav_liv 
                        WHERE sliv_numor = slor_numor 
                          AND sliv_nolign = slor_nolign
                    ), '%Y-%m-%d')
                
                WHEN slor_natcm = 'C' THEN
                    TO_CHAR((
                        SELECT date_creation
                        FROM gcot_acknow_cat 
                        WHERE CAST(Numero_PO as varchar(10)) = CAST(slor_numcf as varchar(10))
                          AND Parts_Number = slor_refp  
                          AND Parts_CST = slor_constp 
                          AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign)
                          AND id_gcot_acknow_cat = (
                              SELECT MAX(id_gcot_acknow_cat) 
                              FROM gcot_acknow_cat 
                              WHERE CAST(Numero_PO as varchar(10)) = CAST(slor_numcf as varchar(10))
                                AND Parts_Number = slor_refp  
                                AND Parts_CST = slor_constp 
                                AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign)
                          )
                    ), '%Y-%m-%d')
                
                WHEN slor_typcf = 'CIS' THEN
                    TO_CHAR((
                        SELECT date_creation
                        FROM gcot_acknow_cat 
                        WHERE CAST(Numero_PO as varchar(10)) = CAST(nlig_numcf as varchar(10))
                          AND Parts_Number = slor_refp  
                          AND Parts_CST = slor_constp 
                          AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm)
                          AND id_gcot_acknow_cat = (
                              SELECT MAX(id_gcot_acknow_cat) 
                              FROM gcot_acknow_cat 
                              WHERE CAST(Numero_PO as varchar(10)) = CAST(nlig_numcf as varchar(10))
                                AND Parts_Number = slor_refp  
                                AND Parts_CST = slor_constp 
                                AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm)
                          )
                    ), '%Y-%m-%d')
                ELSE NULL
            END
        ";
    }

    /**
     * Expression pour le message
     */
    protected function getMessageExpression(): string
    {
        return "
            CASE 
                WHEN slor_qterea <> (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN 
                    (
                        SELECT message 
                        FROM gcot_acknow_cat 
                        WHERE CAST(Numero_PO as varchar(10)) = CAST(slor_numcf as varchar(10))
                          AND Parts_Number = slor_refp  
                          AND Parts_CST = slor_constp 
                          AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign)
                          AND id_gcot_acknow_cat = (
                              SELECT MAX(id_gcot_acknow_cat) 
                              FROM gcot_acknow_cat 
                              WHERE CAST(Numero_PO as varchar(10)) = CAST(slor_numcf as varchar(10))
                                AND Parts_Number = slor_refp  
                                AND Parts_CST = slor_constp 
                                AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign)
                          )
                    )
                WHEN slor_typcf = 'CIS' THEN 
                    (
                        SELECT message 
                        FROM gcot_acknow_cat 
                        WHERE CAST(Numero_PO as varchar(10)) = CAST(nlig_numcf as varchar(10))
                          AND Parts_Number = slor_refp  
                          AND Parts_CST = slor_constp 
                          AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm)
                          AND id_gcot_acknow_cat = (
                              SELECT MAX(id_gcot_acknow_cat) 
                              FROM gcot_acknow_cat 
                              WHERE CAST(Numero_PO as varchar(10)) = CAST(nlig_numcf as varchar(10))
                                AND Parts_Number = slor_refp  
                                AND Parts_CST = slor_constp 
                                AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm)
                          )
                    )
                ELSE ''
            END
        ";
    }

}