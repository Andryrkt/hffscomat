<?php

namespace App\Model\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Model\connexionDote4;
use App\Model\connexionDote4Gcot;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;
use PHPUnit\Util\Exception;

class PlanningModel extends Model
{

    private SelectWhereCondition $selectCond;

    public function __construct()
    {
        parent::__construct();
        $this->selectCond = new SelectWhereCondition();
    }

    public function getAgences(string $codeSociete): array
    {
        $statement = "SELECT
                trim(asuc_num)              as asuc_num,
                trim(asuc_lib)              as asuc_lib
            from agr_succ
            where asuc_codsoc = '$codeSociete'
            and (
                asuc_num like '01'
                or asuc_num like '20'
                or asuc_num like '30'
                or asuc_num like '40'
                or asuc_num like '50'
            )
        ";

        $results = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($results);

        return array_map(function ($item) {
            return [$item['asuc_num'] . '-' . $item['asuc_lib'] => $item['asuc_num']];
        }, $data);
    }

    public function getAgenceDebite(string $codeSociete)
    {
        $statement = "SELECT
                    trim(asuc_lib) as asuc_lib,
                    trim(asuc_num) as asuc_num
            FROM  agr_succ , sav_itv 
            WHERE asuc_num = sitv_succdeb 
            AND asuc_codsoc = '$codeSociete'
            AND asuc_lib <> 'ANTALAHA'
            AND asuc_num <> '10'
            group by 1,2
            order by 1
        ";

        $results = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($results);

        return array_combine(
            array_column($data, 'asuc_lib'),
            array_map(function ($item) { return $item['asuc_num']; }, $data)
        );
    }

    public function getSections()
    {
        $statement = "SELECT distinct
                trim(sitv_typitv)           as sec_num,
                trim(atab_lib2)             as sec_Lib
            from sav_itv
            inner join agr_tab
                on atab_nom = 'TYI'
                and atab_code = sitv_typitv
            ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);
        $data = $this->convertirEnUtf8($data);

        return array_combine(
            array_column($data, 'sec_lib'),
            array_map(function ($item) { return $item['sec_num']; }, $data)
        );
    }

    public function getServiceDebiteByAgence(string $agence): array
    {
        $statement = " SELECT distinct 
                trim(atab_code) as atab_code ,
                trim(atab_lib)  as atab_lib  
            from agr_succ , agr_tab a 
            where a.atab_nom = 'SER' 
            and a.atab_code not in (
                select b.atab_code
                from agr_tab b
                where substr(b.atab_nom,10,2) = asuc_num
                  and b.atab_nom like 'SERBLOSUC%'
            ) 
            {$this->selectCond->eq('asuc_num', $agence)}
        ";
        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);
        return array_map(function ($item) {
            return [
                "value" => $item['atab_code'],
                "text"  => $item['atab_lib']
            ];
        }, $data);
    }

    public function getAnneePlannification()
    {
        $query = "SELECT
                year(ska_d_start) as        annee
            from ska
            inner join skw on skw.skw_id = ska.skw_id
            group by 1
            order by year(ska_d_start) desc           
       ";

        $result = $this->connect->executeQuery($query);
        $data = $this->connect->fetchResults($result);
        return array_combine(
            array_column($data, 'annee'),
            array_column($data, 'annee')
        );
    }

    public function getBackOrderPlanning(array $orsValides, array $orsSoumis, PlanningSearchDto $searchDto): array
    {
        if ($searchDto->orNonValiderDw)
            $vOrValideDw = $this->selectCond->ni('cast(slor_numor as varchar(10))', $orsSoumis);
        else
            $vOrValideDw = $this->selectCond->in('cast(slor_numor as varchar(10))', $orsValides);

        $statement = "SELECT distinct
                cast(sav.slor_numor as varchar(50))                                         as num_or,
                trunc(sav.slor_nogrp/100)                                                   as num_itv,
                cast(sav.slor_numor || '-' || trunc(sav.slor_nogrp/100) as varchar(50))     as num_or_itvs
            from sav_lor as sav
            inner join gcot_acknow_cat as cat
            on cast(sav.slor_numcf  as varchar(50))= cast(cat.numero_po as varchar(50))
            and (sav.slor_nolign = cat.line_number or  sav.slor_noligncm = cat.line_number)
            and sav.slor_refp = cat.parts_number
            where (
                cast(cat.libelle_type as varchar(10)) = 'Error'
                or cast(cat.libelle_type as varchar(10))= 'Back Order'
            ) 
            and cat.id_gcot_acknow_cat = (
                select max(sub.id_gcot_acknow_cat )
                from gcot_acknow_cat as sub
                where sub.parts_number = cat.parts_number
                    and sub.numero_po = cat.numero_po
                    and sub.line_number = cat.line_number
            )
            $vOrValideDw
        ";

        $results = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($results);

        return [
            "num_ors" => array_column($data, 'num_or'),
            "num_itvs" => array_column($data, 'num_itv'),
            "num_or_itvs" => array_column($data, 'num_or_itvs')
        ];
    }

    public function getBackOrderPlanningNoItv(array $orsValides, array $orsSoumis, PlanningSearchDto $searchDto)
    {
        if (!empty($orsValides))
        {
            if ($searchDto->orNonValiderDw)
                $vOrValideDw = $this->selectCond->ni('slor_numor', $orsSoumis);
            else
                $vOrValideDw = $this->selectCond->in('slor_numor', $orsValides);
        } else {
            $vOrValideDw = "and slor_numor in ('')";
        }

        $statement = "SELECT distinct 
                sav.slor_numor  AS intervention
            FROM sav_lor AS sav
            INNER JOIN gcot_acknow_cat AS cat
                ON sav.slor_numcf = cat.numero_po
                AND (sav.slor_nolign = cat.line_number OR  sav.slor_noligncm = cat.line_number)
                AND sav.slor_refp = cat.parts_number
            WHERE cat.libelle_type = 'Back Order'
                AND cat.id_gcot_acknow_cat  = (
                    SELECT MAX(sub.id_gcot_acknow_cat)
                    FROM gcot_acknow_cat AS sub
                    WHERE sub.parts_number = cat.parts_number
                        AND sub.numero_po = cat.numero_po
                        AND sub.line_number = cat.line_number
                )
            $vOrValideDw
        ";

        $results = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($results);
        $data = $this->convertirEnUtf8($data);
        return array_map(function ($item) { return $item['intervention']; }, $data);
    }

    /**
     * Recuperation numero `OR` valide dans `DW` (demande intervention)
     * @param PlanningSearchDto $searchDto
     * @return array
     * @throws \Exception
     */
    public function getNumeroOrValider(PlanningSearchDto $searchDto): array
    {
        $statement = "SELECT distinct
                osv.numeroOR                                as num_or,
                osv.numeroItv                               as num_itv,
                osv.numeroOR || '-' || osv.numeroItv        as num_or_itv
            from {$this->dbIrium}:Informix.ors_soumis_a_validation    osv
            inner join (
                select
                    numeroOR,
                    max(numeroversion)  as max_version
                from {$this->dbIrium}:Informix.ors_soumis_a_validation
                group by numeroOR
            ) latest
                on latest.numeroOR = osv.numeroOR
                and latest.max_version = osv.numeroversion
            where osv.statut like 'Valid%'
                and exists (
                    select 1
                    from {$this->dbIrium}:Informix.demande_intervention di
                    where di.numero_or = osv.numeroOR
                        {$this->selectCond->eq('type_document', $searchDto->typeDocument)}
                        {$this->selectCond->eq('reparation_realise', $searchDto->reparationRealise)}
                        {$this->selectCond->eq('numero_or', $searchDto->numOr)}
                        {$this->selectCond->eq('id_niveau_urgence', $searchDto->niveauUrgence)}
                )
            order by osv.numeroOR asc
        ";
        $results = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($results);
        return [
            "num_ors" => array_column($data, 'num_or'),
            "num_itvs" => array_column($data, 'num_itv'),
            "num_or_itvs" => array_column($data, 'num_or_itv')
        ];
    }

    public function getEtaMagasin(string $numeroCmd, string $refP, string $cst): array
    {
        if ($cst == 'CAT')
            $cst = 'K230';

        $statement = "SELECT
                Eta_ivato,
                Eta_magasin,
                Est_ship_date
            from Ces_magasin
            where Cust_ref = '$numeroCmd'
                {$this->selectCond->eq('Part_no', $refP)}
                {$this->selectCond->eq('custCode', $cst)}
        ";

        $sql = $this->connexion->query($statement);
        $data = array();
        while ($tabType = odbc_fetch_array($sql)) {
            $data[] = $tabType;
        }
        return $data;
    }

    public function getEtatPiecePartiel(string $numCmd, string $refP): array
    {
        $statement = " SELECT fcdl_solde as solde,
                          fcdl_qte as qte
                  FROM FRN_CDL 
                  WHERE 1=1
                  {$this->selectCond->eq('fcdl_numcde', $numCmd)}
                  {$this->selectCond->eq('fcdl_refp', $refP)}
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }

    public function getOrsSoumis(): array
    {
        $statement = "SELECT distinct
            numeroor                        as num_or,
            numeroitv                       as num_itv,
            numeroor || '-' || numeroitv    as num_or_itv
            from {$this->dbIrium}:Informix.ors_soumis_a_validation
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        return [
            'num_ors' => array_column($data, 'num_or'),
            'num_itvs' => array_column($data, 'num_itv'),
            'num_or_itvs' => array_column($data, 'num_or_itv')
        ];
    }

    public function getTechnicientIntervenantSkw(string $numOr, string $numItv)
    {
        $statement = " SELECT distinct 
                ssal_numsal             AS matricule, 
                ssal_nom                AS matriculeNomPrenom
            from skw
            inner join ska on ska.skw_id = skw.skw_id
            inner join sav_sal
                on sav_sal.ssal_numsal = ska.skr_id
                {$this->selectCond->eq('ofs_id', $numItv)}
            where ssal_numsal <> 9999
            {$this->selectCond->eq('skw.ofh_id', $numOr)}
        ";

        $results = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($results);
    }

    public function getTechnicientIntervenantItv(string $numOr, string $numItv)
    {
        $statement = " SELECT distinct 
                ssal_numsal             AS matricule, 
                ssal_nom                AS matriculeNomPrenom
            from sav_itv
            inner join sav_sal on sav_sal.ssal_numsal = sitv_techn
            where ssal_numsal <> 9999
            {$this->selectCond->eq('sitv_interv', $numItv)}
            {$this->selectCond->eq('sitv_numor', $numOr)}
        ";

        $results = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($results);
    }

    public function getQteLigneCIS(string $numOr, string $numItv, string $refP): array
    {
        $statement = "SELECT 
                trunc(nvl(nlig_qtecde,0)) as qteorlig,
                trunc(nvl(nlig_qtealiv,0) )as qtealllig,
                trunc(nvl((nlig_qtecde - nlig_qtealiv - nlig_qteliv) ,0))as qtereliquatlig,
                trunc(nvl(nlig_qteliv,0)) as qtelivlig
            from sav_lor
            inner join neg_lig
                on nlig_soc = slor_soc
                and nlig_succd = slor_succ
                and nlig_numcde = slor_numcf
                and nlig_constp = slor_constp
                and nlig_refp = slor_refp
            where nlig_natop = 'CIS'
                {$this->selectCond->eq('slor_numor', $numOr)}
                {$this->selectCond->eq('trunc(slor_nogrp/100)', $numItv)}
                {$this->selectCond->eq('slor_refp', $refP)}
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }

    public function getDateLivraisonCIS(string $numCIS, string $refP, string $cst): array
    {
        $statement = "SELECT
                max(nliv_dateexp)   as date_livraison
            from neg_liv, neg_llf
            where nliv_soc = nllf_soc
                {$this->selectCond->eq('nllf_numcde', $numCIS)}
                and nliv_numliv = nllf_numliv
                {$this->selectCond->eq('nllf_constp', $cst)}
                {$this->selectCond->eq('nllf_refp', $refP)}
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }

    public function getOrCIS(string $numOrItv): array
    {
        $statement = "SELECT
                decode(seor_succ,'01','','60','','80','','CIS') as succ
            from sav_lor, sav_eor
            where slor_succ = seor_succ
                and slor_numor = seor_numor
                {$this->selectCond->eq('slor_numor  || '-' || trunc(slor_nogrp/100)', $numOrItv)}
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }

    public function getItvCount(string $numOr, array $restItv): array
    {
        $statement = "SELECT 
                count(sitv_interv) as nb_itv
            from sav_itv
            where sitv_numor = '$numOr'
                {$this->selectCond->ni($restItv)}
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }

    public function getDateAllocationCIS(string $numCIS, string $refP, string $cst): array
    {
        $statement = "SELECT
                max(npic_date)   as     date_allocation
            from neg_pic, neg_pil
            where npic_soc = npil_soc
                and npic_numcde = npil_numcde
                {$this->selectCond->eq('npic_numcde', $numCIS)}
                {$this->selectCond->eq('npil_constp', $cst)}
                {$this->selectCond->eq('npil_refp', $refP)}
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($result);
    }

}