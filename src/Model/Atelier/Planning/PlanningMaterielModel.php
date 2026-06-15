<?php

namespace App\Model\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningAtelierSearchDto;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;

class PlanningMaterielModel extends Model
{
    private SelectWhereCondition $selectCond;
    private PlanningModel $planningModel;

    use PlanningModelTrait;

    public function __construct()
    {
        parent::__construct();
        $this->selectCond = new SelectWhereCondition();
        $this->planningModel = new PlanningModel();
    }

    public function getMaterielPlanifier(array $numOrs, array $orSoumis, array $orItvBack, PlanningSearchDto $searchDto, string $codeSoc = 'HF')
    {
        if ($searchDto->orBackOrder) {
            $vOrvalDw = $this->selectCond->in('cast(seor_numor ||'-'|| sitv_interv as varchar(10))', $orItvBack);
        } else {
            if (!empty($lesOrValides)) {
                if ($searchDto->orNonValiderDw) {
                    $vOrvalDw = $this->selectCond->ni('cast(seor_numor as varchar(10))', $orSoumis);
                } else {
                    $vOrvalDw = $this->selectCond->in('cast(seor_numor as varchar(10))', $numOrs);
                }
            } else {
                $vOrvalDw = " --AND seor_numor ||'-'||sitv_interv in ('')";
            }
        }

        $vligneType = $this->typeLigne($searchDto);
        $vYearsStatutPlan =  $this->planAnnee($searchDto);
        $vConditionNoPlanning = $this->nonplannfierSansDatePla($searchDto);
        $vMonthStatutPlan = $this->planMonth($searchDto);
        $vDateDMonthPlan = $this->dateDebutMonthPlan($searchDto);
        $vDateFMonthPlan = $this->dateFinMonthPlan($searchDto);
        $vStatutFacture = $this->facture($searchDto);
        $annee =  $this->criterAnnee($searchDto);
        $agence = $this->agence($searchDto);
        $vStatutInterneExterne = $this->interneExterne($searchDto);
        $agenceDebite = $this->agenceDebite($searchDto);
        $serviceDebite = $this->serviceDebite($searchDto);
        $vconditionNumParc = $this->numParc($searchDto);
        $vconditionIdMat = $this->idMat($searchDto);
        $vconditionNumOr = $this->numOr($searchDto);
        $vconditionNumSerie = $this->numSerie($searchDto);
        $vconditionCasier = $this->casier($searchDto);
        $vsection = $this->section($searchDto);
        $vplan = $searchDto->plan;

        $statement = "SELECT
                      trim(seor_succ) as codeSuc, 
                      trim(asuc_lib) as libSuc, 
                      trim(seor_servcrt) as codeServ, 
                      trim(ser.atab_lib) as libServ, 
                      trim(sitv_comment) as commentaire,
                      mmat_nummat as idMat,
                      trim(mmat_marqmat) as markMat,
                      trim(mmat_typmat) as typeMat ,
                      trim(mmat_numserie) as numSerie,
                      trim(mmat_recalph) as numParc,
                      trim(mmat_numparc) as casier,
                      $vYearsStatutPlan as annee,
                      $vMonthStatutPlan as mois,
                      seor_numor ||'-'||sitv_interv as orIntv,

                      (  SELECT SUM( CASE WHEN slor_typlig = 'P' $vligneType  THEN
                                                slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec
                                          ELSE slor_qterea END )
                        FROM sav_lor as A  , sav_itv  AS B WHERE  A.slor_numor = B.sitv_numor AND  B.sitv_interv = A.slor_nogrp/100 AND A.slor_numor = C.slor_numor and B.sitv_interv  = D.sitv_interv  $vligneType ) as QteCdm,
                    	(  SELECT SUM(slor_qterea ) FROM sav_lor as A  , sav_itv  AS B WHERE  A.slor_numor = B.sitv_numor AND  B.sitv_interv = A.slor_nogrp/100 AND A.slor_numor = C.slor_numor and B.sitv_interv  = D.sitv_interv  $vligneType ) as QtLiv,
                      (  SELECT SUM(slor_qteres )FROM sav_lor as A  , sav_itv  AS B WHERE  A.slor_numor = B.sitv_numor AND  B.sitv_interv = A.slor_nogrp/100 AND A.slor_numor = C.slor_numor and B.sitv_interv  = D.sitv_interv   $vligneType ) as QteALL
                      

                    FROM  sav_eor,sav_lor as C , sav_itv as D, agr_succ, agr_tab ser, mat_mat, agr_tab ope, outer agr_tab sec
                    WHERE seor_numor = slor_numor
                    AND seor_serv <> 'DEV'
                    AND seor_soc = '$codeSoc'
                    AND sitv_numor = slor_numor 
                    AND sitv_interv = slor_nogrp/100
                    AND (seor_succ = asuc_num) -- OR mmat_succ = asuc_parc)
                    AND (seor_servcrt = ser.atab_code AND ser.atab_nom = 'SER')
                    AND (sitv_typitv = sec.atab_code AND sec.atab_nom = 'TYI')
                    AND (seor_ope = ope.atab_code AND ope.atab_nom = 'OPE')
                    $vStatutFacture
                    AND (seor_nummat = mmat_nummat)
                    $vOrvalDw
                    $vligneType
                    $vConditionNoPlanning 
                    $agence
                    $vStatutInterneExterne
                    $agenceDebite
                    $serviceDebite
                    $vDateDMonthPlan
                    $vDateFMonthPlan
                    $vconditionNumParc
                    $vconditionIdMat
                    $vconditionNumOr
                    $vconditionNumSerie
                    $vconditionCasier
                    $vsection 
                    group by 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17
                    order by 10
        ";

        $results = $this->connect->executeQuery($statement);
        return $this->connect->fetchResults($results);
    }

    public function getDetailPieceInformix(string $numOrItv, PlanningSearchDto $searchDto)
    {
        $vTypeLigne = "";
        if (!empty($searchDto->typeLigne))
        {
            switch ($searchDto->typeLigne)
            {
                case "TOUTES":
                    $vTypeLigne = " ";
                    break;
                case "PIECES_MAGASIN":
                    $vTypeLigne = "and slor_constp <> 'LUB' and slor_constp not like 'Z%' and slor_typlig = 'P'";
                    break;
                case "ACHAT_LOCAUX":
                    $vTypeLigne = "and slor_constp = 'ZST'";
                    break;
                case "LUBRIFIANTS":
                    $vTypeLigne = "and slor_constp = 'LUB' and slor_typlig = 'P'";
                    break;
                default:
                    break;
            }
        }

        $statement = " SELECT '$searchDto->plan' as plan,
                            slor_numor as numOr,
                            slor_numcf as numCis,
                            sitv_interv as Intv,
                            trim(sitv_comment) as commentaire,
                            --slor_datel as datePlanning,
                            --sitv_datepla as datePlanning,
                            CASE WHEN 


                                   ( SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = slor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) is Null 

 
                                THEN


                                    DATE(sitv_datepla) 


                                ELSE


                                    (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = slor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) 

 
                                END   as datePlanning,
                            trim(slor_constp) as cst,
                            trim(slor_refp) as ref,
                            trim(slor_desi) as desi,
                            slor_qterel AS QteReliquat,
                            CASE 
                              WHEN slor_typlig = 'P' 
                                THEN
                                  (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) 
		                          ELSE 
                                slor_qterea 
	                          	END AS QteRes_Or,
                            slor_qterea AS Qteliv,
                            slor_qteres AS QteAll,
                            
                      CASE  
                        WHEN slor_natcm = 'C' THEN 'COMMANDE'
                        WHEN slor_natcm = 'L' THEN 'RECEPTION'
                      END AS Statut_ctrmq,
                      CASE 
                        WHEN slor_natcm = 'C' THEN 
                          slor_numcf
                        WHEN slor_natcm = 'L' THEN 
                          (SELECT MAX(fllf_numcde) FROM frn_llf WHERE fllf_numliv = slor_numcf
                          AND fllf_ligne = slor_noligncm
                          AND fllf_refp = slor_refp)
                      END  AS numeroCmd,

                      CASE WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) AND slor_qterel >0 THEN
                        trim('A LIVRER')
                      WHEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) = slor_qteres AND slor_qterel = 0 AND slor_qterea = 0 THEN
                        trim('DISPO STOCK')
                      WHEN slor_qterea =  (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN
                         trim('LIVRE')
                      WHEN slor_natcm = 'C' THEN
                                ( SELECT libelle_type 
                                  FROM  gcot_acknow_cat 
                                  WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10)) 
                                  AND Parts_Number = slor_refp  
                                  AND Parts_CST = slor_constp 
                                  AND Line_Number = slor_noligncm 
		   		                        AND id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat)
                                                             FROM gcot_acknow_cat 
                                                             WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10))  
                                                             AND Parts_Number = slor_refp  
                                                             AND Parts_CST = slor_constp 
                                                             AND Line_Number = slor_noligncm )
					                    	 )
                      WHEN slor_typcf = 'CIS' THEN
		                            ( SELECT libelle_type 
                                  FROM  gcot_acknow_cat 
                                  WHERE  CAST( Numero_PO as varchar(10)) = CAST(nlig_numcf  as varchar(10))
                                  AND Parts_Number = slor_refp  
                                  AND Parts_CST = slor_constp 
                                  AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm )
	                                AND id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat)
                                                             FROM gcot_acknow_cat 
                                                             WHERE  CAST( Numero_PO as varchar(10)) = CAST(nlig_numcf  as varchar(10))
                                                             AND Parts_Number = slor_refp  
                                                             AND Parts_CST = slor_constp 
                                                             AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm ) )
				                         )
	                    END as Statut,

                    CASE WHEN slor_qteres = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) AND slor_qterel >0 THEN
                    TO_CHAR((
		                                 SELECT spic_datepic
                                     FROM (
                                        SELECT spic_datepic,
                                         ROW_NUMBER() OVER (ORDER BY spic_datepic ASC) AS rn
                                         FROM sav_pic
                                         WHERE spic_numor = slor_numor
                                        AND spic_refp = slor_refp
                                        AND spic_nolign = slor_nolign
                                           ) AS ranked_dates
                                       WHERE rn = 1
                             ), '%Y-%m-%d')

	                  WHEN slor_qterea = (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN
                  	TO_CHAR((
		                        (SELECT sliv_date 
		                        FROM sav_liv 
                            WHERE sliv_numor = slor_numor 
		                        AND sliv_nolign = slor_nolign)), '%Y-%m-%d')
	                  WHEN slor_natcm = 'C' THEN
 		                    TO_CHAR((	
                                  ( SELECT date_creation
                                    FROM  gcot_acknow_cat 
                                    WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10)) 
                                    AND Parts_Number = slor_refp  
                                    AND Parts_CST = slor_constp 
                                    AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign)
                                    AND id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat) 
                                                               FROM gcot_acknow_cat 
                                                               WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10))  
                                                               AND Parts_Number = slor_refp  
                                                               AND Parts_CST = slor_constp 
                                                               AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign) )
	                        	       )
                                 ), 
                                 '%Y-%m-%d')
                    WHEN slor_typcf = 'CIS' THEN
		                       TO_CHAR((
                                  ( SELECT date_creation
                                    FROM  gcot_acknow_cat 
                                    WHERE  CAST( Numero_PO as varchar(10)) = CAST(nlig_numcf  as varchar(10))
                                    AND Parts_Number = slor_refp  
                                    AND Parts_CST = slor_constp 
                                    AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm )
                                    AND id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat) 
                                                               FROM gcot_acknow_cat 
                                                               WHERE  CAST( Numero_PO as varchar(10)) = CAST(nlig_numcf  as varchar(10))
                                                               AND Parts_Number = slor_refp  
                                                               AND Parts_CST = slor_constp 
                                                               AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm ))
                                    )
                                 ), '%Y-%m-%d')
	                  END AS dateStatut,

                      CASE  WHEN slor_qterea <> (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) THEN
	                     ( SELECT message FROM  gcot_acknow_cat 
                          WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10)) 
                          AND Parts_Number = slor_refp  
                          AND Parts_CST = slor_constp 
                          AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign)
		   		                AND id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat) 
                                                      FROM gcot_acknow_cat 
                                                      WHERE CAST( Numero_PO as varchar(10)) = CAST(slor_numcf  as varchar(10))  
                                                      AND Parts_Number = slor_refp  
                                                      AND Parts_CST = slor_constp 
                                                      AND (Line_Number = slor_noligncm OR Line_Number = slor_nolign))
					            	)
                        WHEN slor_typcf = 'CIS' THEN
                                  ( SELECT message FROM  gcot_acknow_cat 
                                            WHERE  CAST( Numero_PO as varchar(10)) = CAST(nlig_numcf  as varchar(10))
                                            AND Parts_Number = slor_refp  
                                            AND Parts_CST = slor_constp 
                                            AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm )
                                            AND id_gcot_acknow_cat = ( SELECT MAX(id_gcot_acknow_cat) 
                                                                         FROM gcot_acknow_cat 
                                                                         WHERE  CAST( Numero_PO as varchar(10)) = CAST(nlig_numcf  as varchar(10))
                                                                         AND Parts_Number = slor_refp  
                                                                         AND Parts_CST = slor_constp 
                                                                         AND (Line_Number = slor_nolign OR Line_Number = nlig_noligncm ) )
                                  )
	                    END as Message ,
                    CASE  
                      WHEN nlig_natcm = 'C' THEN 'COMMANDE'
                      WHEN nlig_natcm = 'L' THEN 'RECEPTION'
                    END AS Statut_ctrmq_cis,
                    
                    CASE
                    WHEN nlig_natcm = 'C' THEN 
                     nlig_numcf   
                    WHEN nlig_natcm = 'L'THEN
                     (SELECT MAX(fllf_numcde) FROM frn_llf WHERE fllf_numliv = nlig_numcf
                          AND fllf_ligne = nlig_noligncm
                          AND fllf_refp = nlig_refp)
                    END as numerocdecis   
                                      

                FROM sav_lor
	              JOIN sav_itv ON slor_numor = sitv_numor AND sitv_interv = slor_nogrp / 100
              LEFT JOIN neg_lig ON slor_numcf = nlig_numcde AND slor_refp = nlig_refp
                WHERE slor_numor || '-' || sitv_interv = '" . $numOrItv . "'
                AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
                
                --AND slor_typlig = 'P'
                $vTypeLigne
               -- AND slor_constp NOT LIKE '%ZDI%'
                GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20
               
        ";
        // dump($statement);
        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);
        return $this->convertirEnUtf8($data);
    }


}