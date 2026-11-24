<?php

namespace App\Model\Atelier\Planning;

use App\Model\Model;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Model\Informix\SelectWhereCondition;

class PlanningMaterielModel extends Model
{
    use PlanningModelTrait;

    public function __construct()
    {
        parent::__construct();
        $this->selectCond = new SelectWhereCondition();
    }

    public function getMaterielPlanifier(array $numOrs, array $orSoumis, PlanningSearchDto $searchDto, string $codeSoc = 'HF'): array
    {
        if (empty($numOrs)) return [];
        $statement = "SELECT 
                trim(seor_succ)                               AS code_suc, 
                trim(asuc_lib)                                AS lib_suc, 
                trim(seor_servcrt)                            AS code_serv, 
                trim(ser.atab_lib)                            AS lib_serv, 
                trim(sitv_comment)                            AS commentaire,
                mmat_nummat                                   AS id_mat,
                trim(mmat_marqmat)                            AS mark_mat,
                trim(mmat_typmat)                             AS type_mat,
                trim(mmat_numserie)                           AS num_serie,
                trim(mmat_recalph)                            AS num_parc,
                trim(mmat_numparc)                            AS casier,
                {$this->getAnneeSelectByPlanning($searchDto)} AS annee,
                {$this->getMonthSelectByPlanning($searchDto)} AS mois,
                seor_numor ||'-'||sitv_interv                 AS or_itv,
                (
                    select
                        sum(
                            CASE WHEN slor_typlig = 'P' {$this->getTypeLigneCondition($searchDto)}  THEN
                                slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec
                            ELSE slor_qterea END
                        )
                    from {$this->dbIps}.sav_lor as A, {$this->dbIps}.sav_itv as B
                    where  A.slor_numor = B.sitv_numor
                        and  B.sitv_interv = TRUNC(A.slor_nogrp/100)
                        and A.slor_numor = C.slor_numor
                        and B.sitv_interv = D.sitv_interv 
                        and A.slor_refp is not null
                        {$this->getTypeLigneCondition($searchDto)}
                )                                             AS qte_cmd,
                (
                    select sum(slor_qterea)
                    from {$this->dbIps}.sav_lor as A, {$this->dbIps}.sav_itv as B
                    where  A.slor_numor = B.sitv_numor
                        and  B.sitv_interv = TRUNC(A.slor_nogrp/100)
                        and A.slor_numor = C.slor_numor 
                        and B.sitv_interv = D.sitv_interv 
                        and A.slor_refp is not null 
                        {$this->getTypeLigneCondition($searchDto)}
                )                                             AS qte_liv,
                (
                    select sum(slor_qterel)
                    from {$this->dbIps}.sav_lor as A, {$this->dbIps}.sav_itv as B
                    where  A.slor_numor = B.sitv_numor
                        and  B.sitv_interv = TRUNC(A.slor_nogrp/100)
                        and A.slor_numor = C.slor_numor
                        and B.sitv_interv = D.sitv_interv 
                        and A.slor_refp is not null
                        and A.slor_numcf > 0
                        and A.slor_natcm = 'C'
                        {$this->getTypeLigneCondition($searchDto)}
                )                                             AS qte_reliquat,
                (
                    select sum(slor_qteres)
                    from {$this->dbIps}.sav_lor as A, {$this->dbIps}.sav_itv as B
                    where A.slor_numor = B.sitv_numor
                        and B.sitv_interv = TRUNC(A.slor_nogrp/100)
                        and A.slor_numor = C.slor_numor
                        and B.sitv_interv = D.sitv_interv 
                        and A.slor_refp is not null
                        {$this->getTypeLigneCondition($searchDto)}
                )                                             AS qte_all,
                (
                    select cbse_numcli ||'-'|| TRIM(cbse_nomcli) 
                    from {$this->dbIps}.cli_bse 
                    where cbse_numcli = seor_numcli
                )                                             AS client,
                CASE 
                    WHEN MAX(CASE WHEN C.slor_natcm = 'C' AND C.slor_numcf > 0 THEN 1 ELSE 0 END) = 1 THEN 'O' 
                    ELSE 'N' 
                END                                           AS nat_cm
            FROM {$this->dbIps}.sav_eor, 
                {$this->dbIps}.sav_lor as C,
                {$this->dbIps}.sav_itv as D,
                {$this->dbIps}.agr_succ,
                {$this->dbIps}.agr_tab ser,
                outer {$this->dbIps}.mat_mat,
                {$this->dbIps}.agr_tab ope,
                outer {$this->dbIps}.agr_tab sec
            WHERE seor_numor = slor_numor
                AND slor_refp NOT LIKE '%-L'
                AND slor_refp NOT LIKE '%-CTRL'
                AND seor_serv <> 'DEV'
                AND seor_soc = '$codeSoc'
                AND sitv_numor = slor_numor 
                AND sitv_interv = TRUNC(slor_nogrp/100)
                AND (seor_succ = asuc_num)
                AND (seor_servcrt = ser.atab_code AND ser.atab_nom = 'SER')
                AND (sitv_typitv = sec.atab_code AND sec.atab_nom = 'TYI')
                AND (seor_ope = ope.atab_code AND ope.atab_nom = 'OPE')
                {$this->getFactureCondition($searchDto)}
                AND (seor_nummat = mmat_nummat)
                {$this->getOrValidBackOrderCondition($searchDto,$numOrs,$orSoumis)}
                {$this->getTypeLigneCondition($searchDto)}
                {$this->getPlanningSansDateCondition($searchDto)}
                {$this->getAgenceCondition($searchDto)}
                {$this->getInterneExterneCondition($searchDto)}
                {$this->getAgenceDebiteCondition($searchDto)}
                {$this->getServiceDebiteCondition($searchDto)}
                {$this->getDateDebutCondition($searchDto,$codeSoc)}
                {$this->getDateFinCondition($searchDto,$codeSoc)}
                {$this->getNumParcCondition($searchDto)}
                {$this->getIdMaterielCondition($searchDto)}
                {$this->getNumOrCondition($searchDto)}
                {$this->getNumSerieCondition($searchDto)}
                {$this->getCasierCondition($searchDto)}
                {$this->getSectionCondition($searchDto)}
            group by 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19
            order by 10
        ";
        // dd($statement);
        $results = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($results);
    }

    public function getDetailPieceInformix(string $numOrItv, PlanningSearchDto $searchDto): array
    {

        $statement = " SELECT '$searchDto->planning'                    as planning,
                slor_numor                                              as num_or,
                slor_numcf                                              as num_cis,
                sitv_interv                                             as num_itv,
                trim(sitv_comment)                                      as commentaire,
                CASE WHEN
                    (select DATE(Min(ska_d_start))
                    from {$this->dbIps}.ska, {$this->dbIps}.skw
                    where ofh_id = slor_numor
                        and ofs_id=sitv_interv
                        and skw.skw_id = ska.skw_id
                    ) is Null 
                THEN
                    DATE(sitv_datepla) 
                ELSE
                    (select DATE(Min(ska_d_start))
                    from {$this->dbIps}.ska, {$this->dbIps}.skw
                    where ofh_id = slor_numor
                        and ofs_id=sitv_interv
                        and skw.skw_id = ska.skw_id
                )
                END                                                     as date_planning,
                trim(slor_constp)                                       as cst,
                trim(slor_refp)                                         as ref,
                trim(slor_desi)                                         as desi,
                slor_qterel                                             as qte_reliquat,
                CASE WHEN slor_typlig = 'P'
                    THEN
                        (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                    ELSE
                        slor_qterea
                    END                                                 as qte_res_or,
                slor_qterea                                             as qte_liv,
                slor_qteres                                             as qte_all,
                CASE 
                    WHEN slor_natcm = 'C' THEN 'COMMANDE'
                    WHEN slor_natcm = 'L' THEN 'RECEPTION'
                END                                                     as statut_ctrmq,
                CASE 
                    WHEN slor_natcm = 'C' THEN slor_numcf
                    WHEN slor_natcm = 'L' THEN (
                        select max(fllf_numcde)
                        from {$this->dbIps}.frn_llf 
                        WHERE fllf_numliv = slor_numcf
                            and fllf_ligne = slor_noligncm
                            and fllf_refp = slor_refp
                    )
                END                                                     as num_cmd,
                CASE 
                    WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) and slor_qterel > 0 
                        THEN trim('A LIVRER')
                    WHEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) = slor_qteres and slor_qterel = 0 and slor_qterea = 0
                        THEN trim('DISPO STOCK')
                    WHEN slor_qterea =  (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) 
                        THEN trim('LIVRE')
                    WHEN slor_natcm = 'C' AND slor_qterea + slor_qteres <= 0 THEN
                                ( SELECT libelle_type
                                  FROM  {$this->dbIrium}.gcot_acknow_cat
                                  WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10))
                                  AND Parts_Number = slor_refp  
                                  AND Parts_CST = slor_constp 
                                  AND Line_Number = slor_noligncm
                                  AND id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat)
                                                             FROM {$this->dbIrium}.gcot_acknow_cat
                                                             WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10))
                                                             AND Parts_Number = slor_refp
                                                             AND Parts_CST = slor_constp
                                                             AND Line_Number = slor_noligncm 
                                                             AND quantity > 0) 
                                )
                END                                                 as statut,
                CASE 
                    WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                        and slor_qterel >0 
                    THEN
                            TO_CHAR((
                                select spic_datepic
                                from (
                                    select spic_datepic,
                                        ROW_NUMBER() OVER (ORDER BY spic_datepic ASC) AS rn
                                    from {$this->dbIpsRegix}.sav_pic
                                    where spic_numor = slor_numor
                                        and spic_refp = slor_refp
                                        and spic_nolign = slor_nolign
                                    )                                                AS ranked_dates
                                where rn = 1
                            ), '%Y-%m-%d')
                    WHEN slor_qterea = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) 
                    THEN TO_CHAR(((
                            select min(sliv_date) 
                            from {$this->dbIps}.sav_liv 
                            where sliv_numor = slor_numor 
                                and sliv_nolign = slor_nolign)
                        ), '%Y-%m-%d')
                    WHEN slor_natcm = 'C' 
                    THEN TO_CHAR((select date_creation
                                    FROM {$this->dbIrium}.gcot_acknow_cat
                                    WHERE id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat)
                                                                 FROM {$this->dbIrium}.gcot_acknow_cat
                                                                 WHERE Numero_PO = slor_numcf
                                                                 AND Parts_Number = slor_refp
                                                                 AND Parts_CST = slor_constp
                                                                 AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign) 
                                                                 AND quantity > 0)
                                        
                                 ),
                                '%Y-%m-%d')
                    END                                                             as dateStatut,
                    CASE
                        WHEN slor_qterea <> (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN
	                        ( SELECT message FROM {$this->dbIrium}.gcot_acknow_cat
                                WHERE id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat)
                                                             FROM {$this->dbIrium}.gcot_acknow_cat
                                                             WHERE Numero_PO = slor_numcf
                                                             AND Parts_Number = slor_refp
                                                             AND Parts_CST = slor_constp
                                                             AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign) 
                                                             AND quantity > 0)
					        )
					    ELSE
					        ''
					END                                                             as Message
            FROM {$this->dbIps}.sav_lor
            JOIN {$this->dbIps}.sav_itv
                ON slor_numor = sitv_numor
                AND sitv_interv = TRUNC(slor_nogrp / 100)
            LEFT JOIN {$this->dbIps}.neg_lig
                ON slor_numcf = nlig_numcde
                AND slor_refp = nlig_refp
            WHERE cast(sitv_numor || '-' || sitv_interv as varchar(50)) = '$numOrItv'
                AND (
                    slor_refp not like '%-L'
                    and slor_refp not like '%-CTRL'
                )
                {$this->getTypeLignePieceCondition($searchDto)}
            GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }

    public function getMaterielPlanningList(array $numOrs, array $orSoumis, PlanningSearchDto $searchDto, string $codeSoc = 'HF'): array
    {
        $statement = "SELECT 
                trim(seor_succ)                                                                                                                 as code_suc, 
                trim(asuc_lib)                                                                                                                  as lib_suc, 
                trim(seor_servcrt)                                                                                                              as code_serv, 
                trim(ser.atab_lib)                                                                                                              as lib_serv, 
                trim(sitv_comment)                                                                                                              as commentaire,
                mmat_nummat                                                                                                                     as id_mat,
                trim(mmat_marqmat)                                                                                                              as mark_mat,
                trim(mmat_typmat)                                                                                                               as type_mat,
                trim(mmat_numserie)                                                                                                             as num_serie,
                trim(mmat_recalph)                                                                                                              as num_parc,
                trim(mmat_numparc)                                                                                                              as casier,
                {$this->getAnneeSelectByPlanning($searchDto)}                                                                                   as annee,
                {$this->getMonthSelectByPlanning($searchDto)}                                                                                   as mois,
                seor_numor ||'-'|| sitv_interv                                                                                                  as or_itv,
                (select
                    sum(
                        CASE WHEN slor_typlig = 'P' {$this->getTypeLigneCondition($searchDto)}  THEN
                            slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec
                        ELSE slor_qterea END
                    )
                from {$this->dbIps}.sav_lor as A, {$this->dbIps}.sav_itv  as B
                where  A.slor_numor = B.sitv_numor
                    and  B.sitv_interv = TRUNC(A.slor_nogrp/100)
                    and A.slor_numor = C.slor_numor
                    and B.sitv_interv  = D.sitv_interv {$this->getTypeLigneCondition($searchDto)}
                )                                                                                                                               as qte_cmd,
                (select sum(slor_qterea)
                from {$this->dbIps}.sav_lor as A, {$this->dbIps}.sav_itv as B
                where  A.slor_numor = B.sitv_numor
                    and  B.sitv_interv = TRUNC(A.slor_nogrp/100)
                    and A.slor_numor = C.slor_numor and
                    B.sitv_interv  = D.sitv_interv {$this->getTypeLigneCondition($searchDto)}
                )                                                                                                                               as qte_liv,
                (select sum(slor_qteres)
                from {$this->dbIps}.sav_lor as A, {$this->dbIps}.sav_itv  as B
                where A.slor_numor = B.sitv_numor
                    and B.sitv_interv = TRUNC(A.slor_nogrp/100)
                    and A.slor_numor = C.slor_numor
                    and B.sitv_interv  = D.sitv_interv   {$this->getTypeLigneCondition($searchDto)}
                )                                                                                                                               as qte_all,
                sitv_interv                                                                                                                     as itv,
                seor_numor                                                                                                                      as num_or,
                --------- Statut B Start
                CASE 
                    WHEN (
                        SELECT SUM(
                            CASE  
                                WHEN A.slor_typlig = 'P' 
                                THEN A.slor_qterel + A.slor_qterea + A.slor_qteres + A.slor_qtewait - A.slor_qrec 
                                ELSE A.slor_qterea  
                            END
                        )
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) = (
                        SELECT SUM(A.slor_qterea)
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) 
                    THEN TRIM('TOUT LIVRE')
                    WHEN (
                        SELECT SUM(A.slor_qterea)
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) > 0 
                    AND (
                        SELECT SUM(A.slor_qterea)
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) != (
                        SELECT SUM(
                            CASE  
                                WHEN A.slor_typlig = 'P' 
                                THEN A.slor_qterel + A.slor_qterea + A.slor_qteres + A.slor_qtewait - A.slor_qrec 
                                ELSE A.slor_qterea 
                            END
                        )
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) 
                    THEN TRIM('PARTIELLEMENT LIVRE')
                    WHEN 
                    (
                        SELECT SUM(A.slor_qteres)
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) > 0
                    AND (
                        SELECT SUM(A.slor_qteres)
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) != (
                        SELECT SUM(
                            CASE  
                                WHEN A.slor_typlig = 'P' 
                                THEN A.slor_qterel + A.slor_qterea + A.slor_qteres + A.slor_qtewait - A.slor_qrec 
                                ELSE A.slor_qterea 
                            END
                        )
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor
                    ) 
                    THEN TRIM('PARTIELLEMENT DISPO')
                    WHEN (
                        SELECT SUM(
                            CASE  
                                WHEN A.slor_typlig = 'P' 
                                THEN A.slor_qterel + A.slor_qterea + A.slor_qteres + A.slor_qtewait - A.slor_qrec 
                                ELSE A.slor_qterea 
                            END
                        )
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) = (
                        SELECT SUM(A.slor_qteres)
                        FROM {$this->dbIps}.sav_lor AS A
                        INNER JOIN {$this->dbIps}.sav_itv AS B
                            ON A.slor_numor = B.sitv_numor 
                            AND B.sitv_interv = TRUNC(A.slor_nogrp / 100) 
                        WHERE A.slor_numor = C.slor_numor AND B.sitv_interv = D.sitv_interv
                    ) 
                    THEN TRIM('COMPLET NON LIVRE')
                    ELSE ''
                    END                                                                                                                         as statut_b,
                --------- Statut B End
                sitv_datepla                                                                                                                    as date_planning,
                trim(slor_constp)                                                                                                               as cst,
                trim(slor_refp)                                                                                                                 as ref,
                trim(slor_desi)                                                                                                                 as desi,
                ROUND(slor_qterel)                                                                                                              as qte_reliquat,
                --------- Qte Res Or Start
                CASE WHEN slor_typlig = 'P' THEN
                        ROUND( (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) )
                    ELSE 
                        ROUND( slor_qterea )
                    END                                                                                                                         as qte_res_or,
                --------- Qte Res Or End
                ROUND(slor_qterea)                                                                                                              as qte_liv,
                ROUND(slor_qteres)                                                                                                              as qte_all,
                --------- Statut Ctrmq Start
                CASE  
                    WHEN slor_natcm = 'C' THEN 'COMMANDE'
                    WHEN slor_natcm = 'L' THEN 'RECEPTION'
                    END                                                                                                                         as statut_ctrmq,
                --------- Statut Ctrmq End
                --------- Num Cmd Start
                CASE 
                    WHEN slor_natcm = 'C' THEN slor_numcf
                    WHEN slor_natcm = 'L' THEN 
                        (SELECT MAX(fllf_numcde)
                        FROM {$this->dbIps}.frn_llf
                        WHERE fllf_numliv = slor_numcf
                            AND fllf_ligne = slor_noligncm
                            AND fllf_refp = slor_refp)
                END                                                                                                                             as num_cmd,
                --------- Num Cmd End
                --------- Statut Start
                CASE 
                    WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) AND slor_qterel >0 THEN
                        trim('A LIVRER')
                    WHEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) = slor_qteres AND slor_qterel = 0 AND slor_qterea = 0 THEN
                        trim('DISPO STOCK')
                    WHEN slor_qterea =  (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN
                        trim('LIVRE')
                    END                                                                                                                         as statut,
                --------- Statut End
                --------- Date Statut Start
                CASE
                    WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) AND slor_qterel >0 THEN
                        TO_CHAR((
                            SELECT spic_datepic
                            FROM (
                                SELECT
                                    spic_datepic,
                                    ROW_NUMBER() OVER (ORDER BY spic_datepic ASC) AS rn
                                FROM {$this->dbIpsRegix}.sav_pic
                                WHERE spic_numor = slor_numor
                                    AND spic_refp = slor_refp
                                    AND spic_nolign = slor_nolign
                                ) AS ranked_dates
                                WHERE rn = 1
                        ), '%Y-%m-%d')
                    WHEN slor_qterea = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN
                        TO_CHAR((
                            (SELECT min(sliv_date) 
                            FROM {$this->dbIps}.sav_liv 
                            WHERE sliv_numor = slor_numor 
                                AND sliv_nolign = slor_nolign
                            )
                        ), '%Y-%m-%d')
                    END                                                                                                                         as date_statut
            FROM  {$this->dbIps}.sav_eor, {$this->dbIps}.sav_lor as C , {$this->dbIps}.sav_itv as D, {$this->dbIps}.agr_succ, {$this->dbIps}.agr_tab ser, {$this->dbIps}.mat_mat, {$this->dbIps}.agr_tab ope, outer {$this->dbIps}.agr_tab sec, outer {$this->dbIps}.neg_lig
            WHERE seor_numor = slor_numor
                AND seor_soc = '$codeSoc'
                AND seor_serv <> 'DEV'
                AND seor_soc = '$codeSoc'
                AND sitv_numor = slor_numor 
                AND sitv_interv = TRUNC(slor_nogrp/100) 
                AND (seor_succ = asuc_num)
                AND (seor_servcrt = ser.atab_code AND ser.atab_nom = 'SER')
                AND (sitv_typitv = sec.atab_code AND sec.atab_nom = 'TYI')
                AND (seor_ope = ope.atab_code AND ope.atab_nom = 'OPE')     
                {$this->getFactureCondition($searchDto)}
                AND mmat_marqmat NOT like 'z%' AND mmat_marqmat NOT like 'Z%'
                --AND sitv_servcrt IN ('ATE','FOR','GAR','MAN','CSP','MAS', 'LR6', 'LST')
                AND (seor_nummat = mmat_nummat)
                AND slor_numcf = nlig_numcde AND slor_refp = nlig_refp
                {$this->getOrValidBackOrderCondition($searchDto,$numOrs,$orSoumis)}
                {$this->getTypeLigneCondition($searchDto)}
                {$this->getPlanningSansDateCondition($searchDto)}
                {$this->getAgenceCondition($searchDto)}
                {$this->getInterneExterneCondition($searchDto)}
                {$this->getAgenceDebiteCondition($searchDto)}
                {$this->getServiceDebiteCondition($searchDto)}
                {$this->getDateDebutCondition($searchDto,$codeSoc)}
                {$this->getDateFinCondition($searchDto,$codeSoc)}
                {$this->getNumParcCondition($searchDto)}
                {$this->getIdMaterielCondition($searchDto)}
                {$this->getNumOrCondition($searchDto)}
                {$this->getNumSerieCondition($searchDto)}
                {$this->getCasierCondition($searchDto)}
                {$this->getSectionCondition($searchDto)}
            group by 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,32
            order by 10,14 
        ";
        // dd($statement);
        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }
}
