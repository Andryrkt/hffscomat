<?php

namespace App\Model\magasin\Ors\Traiter;

use App\Model\Model;
use App\Model\Traits\ConditionModelTrait;

class OrTraiterModel extends Model
{
    use ConditionModelTrait;

    public function recupereListeMaterielValider($criteria = [], $lesOrSelonCondition = [])
    {

        $designation = $this->conditionLike('slor_desi', 'designation', $criteria);
        $referencePiece = $this->conditionLike('slor_refp', 'referencePiece', $criteria);
        $constructeur = $this->conditionLike('slor_constp', 'constructeur', $criteria);
        $dateDebut = $this->conditionDateSigne('slor_datec', 'dateDebut', $criteria, '>=');
        $dateFin = $this->conditionDateSigne('slor_datec', 'dateFin', $criteria, '<=');
        $numDit = $this->conditionLike('seor_refdem', 'numDit', $criteria);
        $numOr = $this->conditionSigne('slor_numor', 'numOr', '=', $criteria);
        $piece = $this->conditionPiece('pieces', $criteria, 'slor_constp');
        $agence = $this->conditionAgenceService("slor_succdeb", 'agence', $criteria);
        $service = $this->conditionAgenceService("slor_servdeb", 'service', $criteria);
        $agenceUser = $this->conditionAgenceUser('agenceUser', $criteria);

        $statement = "SELECT 
            trim(seor_refdem) as numero_dit
            ,seor_numor as numero_or
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

            from sav_lor 
            inner join sav_eor on seor_soc = slor_soc and seor_succ = slor_succ and seor_numor = slor_numor and seor_soc = 'HF'
            inner join mat_mat on mmat_nummat =  seor_nummat
            inner join agr_usr on ausr_num = seor_usr
            inner join agr_tab on atab_nom = 'OPE' and atab_code = ausr_ope
            inner join sav_itv 
                on sitv_soc = slor_soc 
                and sitv_succ = slor_succ 
                and sitv_numor = slor_numor 
                and sitv_interv = slor_nogrp / 100 
                and sitv_soc = 'HF'
                and seor_succ = slor_succ 
                and seor_numor = slor_numor
            left join ir_prod108_test:Informix.demande_intervention di on di.numero_or = seor_numor
            LEFT JOIN ir_prod108_test:informix.wor_niveau_urgence w ON di.id_niveau_urgence = w.id
            where 
            slor_soc = 'HF'
            and seor_typeor not in('950', '501')
            -- $agenceUser
            -- $designation
            -- $referencePiece 
            -- $constructeur 
            -- $dateDebut
            -- $dateFin
            -- $numOr
            -- $numDit
            -- $piece
            -- $agence
            -- $service
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
}
