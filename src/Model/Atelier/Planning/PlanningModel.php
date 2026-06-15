<?php

namespace App\Model\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningSearchDto;
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

    public function getAgenceIrium(?string $codeSociete = 'HF'): array
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
        $data = $this->convertirEnUtf8($data);

        return array_map(function ($item) {
            return [$item['asuc_num'] . '-' . $item['asuc_lib'] => $item['asuc_num']];
        }, $data);
    }

    public function getAgenceDebite(?string $codeSociete = 'HF')
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
        $data = $this->convertirEnUtf8($data);

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

    public function getServiceDebiteByAgence(string $agence)
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
        $data = $this->convertirEnUtf8($data);
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
        $data = $this->convertirEnUtf8($data);
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
        $data = $this->convertirEnUtf8($data);

        $numOrs = array_column($data, 'num_or');
        $numItvs = array_column($data, 'num_itv');
        $numOrItvs = array_column($data, 'num_or_itvs');

        return ["num_ors" => $numOrs, "num_itvs" => $numItvs, "num_or_itvs" => $numOrItvs];
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

    public function getNumeroOrValider(PlanningSearchDto $searchDto): array
    {
        $statement = "SELECT distinct
                osv.numeroOR                                as num_or,
                osv.numeroItv                               as num_itv,
                osv.numeroOR || '-' || osv.numeroItv        as num_or_itv
            from {$this->dbIrium}:Informix.ors_soumis_a_validation    osv
            inner join {$this->dbIrium}:Informix.demande_intervention di on di.numero_or = osv.numeroor
            where numeroversion = (select
                max(numeroversion) 
                from {$this->dbIrium}:Informix.ors_soumis_a_validation oo
                where oo.numeroor = osv.numeroor)
            and osv.statut like 'Valid%'
            {$this->selectCond->eq('type_document', $searchDto->typeDocument)}
            {$this->selectCond->eq('reparation_realise', $searchDto->reparationRealise)}
            {$this->selectCond->eq('numero_or', $searchDto->numOr)}
            {$this->selectCond->eq('id_niveau_urgence', $searchDto->niveauUrgence ? $searchDto->niveauUrgence : null)}
            order by numeroOR asc
        ";

        $results = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($results));
        $numOrs = array_column($data, 'num_or');
        $numItvs = array_column($data, 'num_itv');
        $numOrItvs = array_column($data, 'num_or_itv');
        return ["num_ors" => $numOrs, "num_itvs" => $numItvs, "num_or_itvs" => $numOrItvs];
    }

    public function getEtaMagasin(string $numeroCmd, string $refP, string $cst)
    {
        // if ($cst == 'CAT')
        //     $cst = 'K230';

        // $statement = "SELECT
        //         Eta_ivato,
        //         Eta_magasin,
        //         Est_ship_date
        //     from Ces_magasin
        //     where Cust_ref = '$numeroCmd'
        //     {$this->selectCond->eq('Part_no', $refP)}
        //     {$this->selectCond->eq('custCode', $cst)}
        // ";

        // $sql = $this->connexion04->query($statement);
        // $data = array();
        // while ($tabType = odbc_fetch_array($sql)) {
        //     $data[] = $tabType;
        // }
        // return $data;
    }


    public function getEtatPiecePart(string $numCmd, string $refP)
    {
        $statement = " SELECT fcdl_solde as solde,
                          fcdl_qte as qte
                  FROM FRN_CDL 
                  WHERE 1
                  {$this->selectCond->eq('fcdl_numcde', $numCmd)}
                  {$this->selectCond->eq('fcdl_refp', $refP)}
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);
        return $this->convertirEnUtf8($data);
    }

    public function getOrsSoumis()
    {
        $statement = "SELECT distinct
            numeroor                        as num_or,
            numeroitv                       as num_itv,
            numeroor || '-' || numeroitv    as num_or_itv
            from {$this->dbIrium}:Informix.ors_soumis_a_validation
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        $numOrs = array_column($data, 'num_or');
        $numItvs = array_column($data, 'num_itv');
        $numOrItvs = array_column($data, 'num_or_itv');

        return ["num_ors" => $numOrs, "num_itvs" => $numItvs, "num_or_itvs" => $numOrItvs];
    }

    public function getOrsSoumisValider()
    {
        $statement = "SELECT distinct
            osv.numeroor || '-' || osv.numeroitv    as numero_or_numero_itv
            from {$this->dbIrium}:Informix.ors_soumis_a_validation    osv
        ";

        $result = $this->connect->executeQuery($statement);
        return $this->convertirEnUtf8($this->connect->fetchResults($result));
    }

}