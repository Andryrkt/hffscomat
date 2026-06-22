<?php

namespace App\Model\magasin\Ors\Traiter;

use App\Dto\Magasin\Ors\Traiter\OrATraiterSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;
use App\Model\Traits\ConditionModelTrait;

class OrTraiterModel extends Model
{
    use ConditionModelTrait;

    public function recupereListeMaterielValider(OrATraiterSearchDto $dtoSearch, $lesOrSelonCondition = [])
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
        "; // 10-ANTALAHA => 10


        // $agenceUser = $this->conditionAgenceUser('agenceUser', $criteria);

        $statement = "SELECT 
            trim(seor_refdem) as numero_dit
            ,seor_numor as numero_or
            ,seor_nummat as numero_mat
            ,trim(mmat_numserie) as numSerie
            ,trim(mmat_recalph) as numParc
            ,trim(mmat_marqmat) as marque
            ,trim(mmat_desi) as designationMaterie
            ,trim(mmat_typmat) as modele
            ,trim(mmat_numparc) as casier

            
            ,CASE 
                    WHEN 
                        (SELECT DATE(Min(ska_d_start)) FROM informix.ska, informix.skw WHERE ofh_id = sitv_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id )  is Null THEN DATE(sitv_datepla)  
                    ELSE
                        (SELECT DATE(Min(ska_d_start)) FROM informix.ska, informix.skw WHERE ofh_id = sitv_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) 
            END as datePlanning
            ,w.description as niveauUrgence
            ,slor_datec as dateCreation
            ,slor_succ as agenceCrediteur
            ,slor_servcrt as serviceCrediteur
            ,slor_succdeb as agence
            ,slor_servdeb as service
            ,slor_nogrp/100 as numInterv
            ,slor_nolign as numeroLigne
            ,trim(slor_constp) as constructeur
            ,trim(slor_refp) as referencePiece
            ,trim(slor_desi) as designation
            ,CASE WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea END AS quantiteDemander
            , trim(atab_lib) as nomPrenom

            from {$this->dbIps}:Informix.sav_lor 
            inner join {$this->dbIps}:Informix.sav_eor on seor_soc = slor_soc and seor_succ = slor_succ and seor_numor = slor_numor and seor_soc = 'HF'
            inner join {$this->dbIps}:Informix.mat_mat on mmat_nummat =  seor_nummat
            inner join {$this->dbIps}:Informix.agr_usr on ausr_num = seor_usr
            inner join {$this->dbIps}:Informix.agr_tab on atab_nom = 'OPE' and atab_code = ausr_ope
            inner join {$this->dbIps}:Informix.sav_itv 
                on sitv_soc = slor_soc 
                and sitv_succ = slor_succ 
                and sitv_numor = slor_numor 
                and sitv_interv = slor_nogrp / 100 
                and sitv_soc = '{$dtoSearch->codeSociete}'
                and seor_succ = slor_succ 
                and seor_numor = slor_numor
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
            where slor_soc = '{$dtoSearch->codeSociete}'
            and seor_typeor not in('950', '501')
            $conditions
            and slor_typlig = 'P'
            and slor_pos = 'EC'
            and seor_serv ='SAV'
            and slor_qteres = 0 and slor_qterel = 0 and slor_qterea = 0
            order by numInterv ASC, seor_dateor DESC, slor_numor DESC, numeroLigne ASC
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupMarqueCasierMateriel(string $matricule): array
    {
        if ($matricule === null) return [];
        $statement = "SELECT
          mmat_nummat as num_matricule,
          trim(mmat_numserie) as num_serie,
          trim(mmat_recalph) as num_parc ,
          trim(mmat_marqmat) as marque,
          trim(mmat_desi) as designation,
          trim(mmat_typmat) as modele,
          trim(mmat_numparc) as casier

          from {$this->dbIps}:Informix.mat_mat
          where mmat_nummat ='$matricule'
          and MMAT_ETSTOCK in ('ST','AT', '--')
          and trim(MMAT_AFFECT) in ('IMM','LCD', 'SDO', 'VTE')
      ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function getIdMaterielDitSelonNumOr(string $numeroOr): ?string
    {
        $statement = " SELECT id_materiel  
        from {$this->dbIrium}:Informix.demande_intervention 
        where numero_or ='$numeroOr' ";

        $result = $this->connect->executeQuery($statement);

        $data = array_column($this->convertirEnUtf8($this->connect->fetchResults($result)), 'id_materiel');

        return $data[0] ?? null;
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
