<?php

namespace App\Model\magasin\Ors\Livrer;

use App\Dto\Magasin\Ors\Livrer\OrLivrerSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;

class OrLivrerModel extends Model
{
    public function recupereListeMaterielValider(OrLivrerSearchDto $dtoSearch): array
    {
        $selectWhereCondition = new SelectWhereCondition();

        $conditions = "
            {$selectWhereCondition->like('slor_desi',$dtoSearch->designation)}
            {$selectWhereCondition->like('seor_refdem',$dtoSearch->numDit)}
            {$selectWhereCondition->eq('slor_numor',$dtoSearch->numOr)}
            {$selectWhereCondition->like('slor_refp',$dtoSearch->referencePiece)}
            {$selectWhereCondition->between('slor_datec',$dtoSearch->dateDebut,$dtoSearch->dateFin)}
            {$selectWhereCondition->eq('w.description',$dtoSearch->niveauUrgence)}
            {$selectWhereCondition->eq('slor_succdeb', trim(explode('-',$dtoSearch->agence)[0]))}
            {$selectWhereCondition->eq('slor_servdeb', trim(explode('-',$dtoSearch->service)[0]))}
            {$selectWhereCondition->eq('slor_succdeb', trim(explode('-',$dtoSearch->agenceUser)[0]))}
            {$selectWhereCondition->eq('T.situation',$dtoSearch->orCompletNon)}
        ";

        $statement = " SELECT
            TRIM(seor_refdem) as referencedit
            , seor_numor as numeroOr
            , CASE WHEN  (SELECT DATE(Min(ska_d_start)) FROM informix.ska, informix.skw WHERE ofh_id = sitv_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id )  is Null THEN DATE(sitv_datepla) 
                    ELSE(SELECT DATE(Min(ska_d_start)) FROM informix.ska, informix.skw WHERE ofh_id = sitv_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) 
              END as datePlanning
            , w.description as niveauUrgence
            , seor_dateor as dateCreation
            , seor_succ as agenceCrediteur
            , seor_servcrt as serviceCrediteur
            , sitv_succdeb as agenceDebiteur
            , sitv_servdeb as serviceDebiteur
            , sitv_interv as numInterv
            , slor_nolign as numeroLigne
            , slor_constp as constructeur
            , TRIM(slor_refp) as referencePiece
            , TRIM(slor_desi) as designation
            ,sum(CASE
                    WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                    WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea
                END)  AS quantiteDemander
            , sum(slor_qteres) as qteALivrer
            , sum(slor_qterea) as quantiteLivree
            , trim(atab_lib) as nomPrenom
            -- mety tsy ilaina
            , (
	            SELECT F.situation FROM (select
	            CASE
	           	 WHEN
	            	sum(slor_qteres) > 0 AND
	            	sum(CASE
	                WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
	                    WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea
	                    END) = sum(slor_qteres + slor_qterea)
	                THEN 'ORs COMPLET'
	                WHEN sum(slor_qteres) > 0 AND
	                    sum(CASE
	                        WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
	                        WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea
	                            END) > sum(slor_qteres + slor_qterea)
	                THEN 'ORs INCOMPLETS'
            	END as situation
            	, situ.slor_numor as numero_or
            
	            FROM sav_lor situ
	            WHERE
	            situ.slor_numor = OR.slor_numor
	            and situ.slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST')
	            group by 2 ) as F
            ) as situationtest
            , seor_usr as idUser
            , trim(ausr_nom) as nomUtilisateur
            
            , mmat_nummat as idMateriel
            , trim(mmat_numserie) as num_serie
            , trim(mmat_recalph) as num_parc 
            , trim(mmat_marqmat) as marque
            , trim(mmat_numparc) as casie
            
            
            
            FROM {$this->dbIps}:Informix.sav_lor as OR
            inner join {$this->dbIps}:Informix.sav_eor as U on U.seor_numor = slor_numor and U.seor_soc = slor_soc and U.seor_succ = slor_succ
            inner join {$this->dbIps}:Informix.mat_mat on mmat_nummat =  seor_nummat
            inner join {$this->dbIps}:Informix.agr_usr on ausr_num = seor_usr
            inner join {$this->dbIps}:Informix.agr_tab on atab_nom = 'OPE' and atab_code = ausr_ope
            inner join {$this->dbIps}:Informix.sav_itv as I
	            on I.sitv_soc = slor_soc
	            and I.sitv_succ = slor_succ
	            and I.sitv_numor = slor_numor
	            and I.sitv_interv = slor_nogrp /100
	            and sitv_numor || '-' || sitv_interv in ('16417354-1')
            inner join(
			            SELECT F.* FROM (select
			            CASE
			            WHEN
			            sum(slor_qteres) > 0 AND
			            sum(CASE
			                WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
			                    WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea
			                    END) = sum(slor_qteres + slor_qterea)
			            THEN 'ORs COMPLET'
			            WHEN
			            sum(slor_qteres) > 0 AND
			            sum(CASE
			                WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
			                    WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea
			                    END) > sum(slor_qteres + slor_qterea)
			            THEN 'ORs INCOMPLETS'
			            END as situation
			            , situ.slor_numor as numero_or
			            FROM sav_lor situ
			            WHERE
			            situ.slor_numor in ('16417354')
			             AND situ.slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
			            group by 2 ) as F
            		) as T ON T.numero_or = OR.slor_numor
            left join {$this->dbIrium}:Informix.demande_intervention di on di.numero_or = seor_numor
            LEFT JOIN {$this->dbIrium}:informix.wor_niveau_urgence w ON di.id_niveau_urgence = w.id
            inner JOIN {$this->dbIrium}:informix.ors_soumis_a_validation osv_or 
                ON osv_or.numeroor = seor_numor
                AND osv_or.numeroversion = (
                    SELECT MAX(osv2.numeroversion)
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv2
                    WHERE osv2.id = osv_or.id
                )
                AND osv_or.statut LIKE 'Valid%'
            where seor_numor in
            (
	            select slor_numor from sav_lor l
	            where l.slor_numor  in ('16417354')
	             AND l.slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
	            group by l.slor_numor
	            having sum(l.slor_qteres) > 0
            )
             AND slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
            and seor_typeor not in('950', '501') -- a voir avec Atish ==> hoby
            $conditions

            group by 1,2,3,4, 5, 6, 7, 8, 9, 10, 11, 12, 13,14,18, 19, 20, 21,22,23,24,25,26
            order by seor_numor asc, sitv_interv asc, slor_nolign asc;
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function agence(string $codeSociete)
    {
        $statement = "  SELECT DISTINCT
                            slor_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) as agence
                        FROM {$this->dbIps}:Informix.sav_lor
                        WHERE slor_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) <> ''
                        AND slor_soc = '$codeSociete'
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'agence');
    }

    public function service(?string $agence)
    {
        if ($agence === null) {
            return []; // Si aucune agence, retourner un tableau vide
        }

        // Reverted to string concatenation as executeQuery might not support parameters
        $statement = " SELECT DISTINCT
                            slor_servdeb||'-'||(select trim(atab_lib) from agr_tab where atab_nom = 'SER' and atab_code = slor_servdeb) as service
                        FROM sav_lor
                        WHERE slor_servdeb||'-'||(select trim(atab_lib) from agr_tab where atab_nom = 'SER' and atab_code = slor_servdeb) <> ''
                        AND slor_soc = 'HF'
                        AND slor_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) = '$agence'
            ";


        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        $dataUtf8 = $this->convertirEnUtf8($data);


        return array_map(function ($item) {
            return [
                "value" => $item['service'],
                "text"  => $item['service']
            ];
        }, $dataUtf8);
    }

    public function agenceUser(string $codeAgence, string $codeSociete)
    {
        $statement = "  SELECT DISTINCT
                            slor_succdeb||'-'||(select trim(asuc_lib) from informix.agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) as agence
                        FROM {$this->dbIps}:Informix.sav_lor
                        WHERE slor_succdeb||'-'||(select trim(asuc_lib) from informix.agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) <> ''
                        AND slor_soc = '$codeSociete'
                    ";

        if ($codeAgence <> "''") {
            $statement .= " AND slor_succdeb IN ($codeAgence) ";
        }

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'agence');
    }
}
