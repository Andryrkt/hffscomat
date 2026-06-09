<?php

namespace App\Model\Atelier\Dit\Soumission;


use App\Model\Model;
use App\Model\Traits\ConversionModel;
use App\Service\GlobalVariablesService;
use App\Service\TableauEnStringService;
use Symfony\Component\Validator\Constraints\IsNull;

class DitOrSoumisAValidationModel extends Model
{
    use ConversionModel;
    public function recupOrSoumisValidation($numOr)
    {
        $statement = "SELECT
        slor_numor,
        sitv_datdeb,
        trim(seor_refdem) as NUMERo_DIT,
        sitv_interv as NUMERO_ITV,
        trim(sitv_comment) as LIBELLE_ITV,
        count(slor_constp) as NOMBRE_LIGNE,
        Sum(
            CASE
                WHEN slor_typlig = 'P' THEN (
                    slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec
                )
                WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_qterea
            END * CASE
                WHEN slor_typlig = 'P' THEN slor_pxnreel
                WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
            END
        ) as MONTANT_ITV,

        Sum(
            CASE
                WHEN slor_typlig = 'P'
                AND slor_constp NOT like 'Z%'
                AND slor_constp <> 'LUB' THEN (
                    nvl (slor_qterel, 0) + nvl (slor_qterea, 0) + nvl (slor_qteres, 0) + nvl (slor_qtewait, 0) - nvl (slor_qrec, 0)
                )
            END * CASE
                WHEN slor_typlig = 'P' THEN slor_pxnreel
                WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
            END
        ) AS MONTANT_PIECE,

        Sum(
            CASE
                WHEN slor_typlig = 'M' THEN slor_qterea
            END * CASE
                WHEN slor_typlig = 'P' THEN slor_pxnreel
                WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
            END
        ) AS MONTANT_MO,

        Sum(
            CASE
                WHEN slor_constp = 'ZST' THEN (
                    slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec
                )
            END * CASE
                WHEN slor_typlig = 'P' THEN slor_pxnreel
                WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
            END
        ) AS MONTANT_ACHATS_LOCAUX,

        Sum(
            CASE
                WHEN slor_constp <> 'ZST'
                AND slor_constp like 'Z%' THEN slor_qterea
            END * CASE
                WHEN slor_typlig = 'P' THEN slor_pxnreel
                WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
            END
        ) AS MONTANT_DIVERS,

        Sum(
            CASE
                WHEN slor_typlig = 'P'
                AND slor_constp NOT like 'Z%'
                AND slor_constp = 'LUB' THEN (
                    nvl (slor_qterel, 0) + nvl (slor_qterea, 0) + nvl (slor_qteres, 0) + nvl (slor_qtewait, 0) - nvl (slor_qrec, 0)
                )
            END * CASE
                WHEN slor_typlig = 'P' THEN slor_pxnreel
                WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
            END
        ) AS MONTANT_LUBRIFIANTS

        from sav_eor, sav_lor, sav_itv
        WHERE
            seor_numor = slor_numor
            AND seor_serv <> 'DEV'
            AND sitv_numor = slor_numor
            AND sitv_interv = slor_nogrp / 100

        --AND sitv_pos NOT IN('FC', 'FE', 'CP', 'ST')
        --AND sitv_servcrt IN ('ATE','FOR','GAR','MAN','CSP','MAS')
        AND seor_numor = '" . $numOr . "'
        --AND SEOR_SUCC = '01'
        group by
            1,
            2,
            3,
            4,
            5
        order by slor_numor, sitv_interv
    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }


    public function recupererNumdevis($numOr)
    {
        $statement = "SELECT  seor_numdev  
                from sav_eor
                where seor_numor = '" . $numOr . "'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupNumeroOr(string $numDit, string $codeSociete): ?string
    {
        $statement = " SELECT FIRST 1 
        seor_numor as numOr
        from {$this->dbIps}:Informix.sav_eor
        where seor_refdem = '$numDit'
        AND seor_serv = 'SAV'
        AND seor_soc = '$codeSociete'
    ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['numor'] ?? null;
    }

    public function recupNumeroMatricule(string $numDit, string $numOr, string $codeSociete)
    {
        $statement = " SELECT 
            seor_nummat as numMatricule
            from {$this->dbIps}:Informix.sav_eor
            where seor_refdem = '$numDit'
            AND seor_numor = '$numOr'
            AND seor_serv = 'SAV'
            AND seor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return (int) ($data[0]['nummatricule'] ?? 0);;
    }

    public function recupNbDatePlanningVide($numOr, string $codeSociete)
    {
        $statement = "SELECT count(*) as nbPlanning
        from sav_itv 
        where sitv_numor = '$numOr'
        AND sitv_soc = '$codeSociete'
        and sitv_datepla is null";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupPositonOr(string $numor, string $codeSociete): string
    {
        $statement = " SELECT seor_pos as position 
                    from {$this->dbIps}:Informix.sav_eor 
                    where seor_numor = '$numor' 
                    and seor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return  $data[0]['position'] ?? null;
    }

    public function recupTypeOr(?string $numor): int
    {
        if ($numor === null) return 0;

        $statement = " SELECT seor_typeor as type_or from informix.sav_eor where seor_numor = '$numor'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return  $data[0]['type_or'] ?? 0;
    }

    public function recupNbPieceMagasin($numOr, string $codeSociete)
    {
        $statement = " SELECT
            count(slor_constp) as nbr_sortie_magasin 
            from sav_lor 
            where (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
            and slor_typlig = 'P' 
            and slor_numor = '$numOr'
            and slor_soc = '$codeSociete'
            AND slor_constp in (" . GlobalVariablesService::get('pieces_magasin') . ")
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupNbAchatLocaux($numOr, string $codeSociete)
    {
        $statement = " SELECT
            count(slor_constp) as nbr_achat_locaux 
            from sav_lor 
            where slor_numor = '$numOr'
            and slor_soc = '$codeSociete'
            and slor_constp in (" . GlobalVariablesService::get('achat_locaux') . ")  
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupNbPol($numOr, string $codeSociete)
    {
        $statement = " SELECT
            count(slor_constp) as nbr_pol 
            from sav_lor 
            where slor_numor = '$numOr'
            and slor_soc = '$codeSociete'
            and slor_constp in (" . GlobalVariablesService::get('lub') . ")  
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupRefClient(string $numOr, string $codeSociete)
    {
        $statement = " SELECT seor_lib  
                    from {$this->dbIps}:Informix.sav_eor 
                    where seor_numor='$numOr' AND seor_soc='$codeSociete'
                    ";
        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }
    public function recupDatePlanning1($numOr, string $codeSociete)
    {
        $statement = " SELECT  
                            min(ska_d_start) as datePlanning1
                        from skw 
                        inner join ska on ska.skw_id = skw.skw_id 
                        where ofh_id ='$numOr'
                        AND skw_soc ='$codeSociete'
                        group by ofh_id 
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }
    // public function recupBlockageStatut($numOr)
    // {
    //     $sqlNumVersMax = " SELECT MAX(numeroVersion) as numversionMax
    //             FROM ors_soumis_a_validation
    //             WHERE numeroOR = '{$numOr}'";

    //     $numVersionMax = $this->retournerResult28($sqlNumVersMax);

    //     if ($numVersionMax[0]['numversionMax'] == 0 || is_null($numVersionMax[0]['numversionMax'])) {
    //         dump("pas de numéro or");
    //         return "ne pas bloquer";
    //     } else {
    //         dump("misy version");
    //         $sql1 = "SELECT
    //             CASE
    //                 WHEN COUNT(*) > 0 THEN 'ne pas bloquer'
    //                 ELSE 'bloquer'
    //             END AS retour
    //         FROM ors_soumis_a_validation
    //         WHERE numeroOR = '41326877'
    //         AND numeroVersion = {$numVersionMax[0]['numversionMax']}
    //         AND (
    //             statut = 'Validé' 

    //         )
    //         ";

    //         $sql2 = "SELECT
    //                 statut
    //             FROM
    //                 ors_soumis_a_validation
    //             WHERE
    //                 numeroOR = '51303448'
    //                 AND numeroVersion = :numVersionMax
    //                 AND REPLACE(REPLACE(statut, 'b\"', ''), '\"', '') LIKE 'Validé%'
    //             ";

    //         $sql3 = "SELECT statut_or from demande_intervention where numero_or = '51303448'";
    //     }




    //     $statement = $this->connexion->query($sql2);
    //     $data = [];
    //     while ($tabType = odbc_fetch_array($statement)) {
    //         $data[] = $tabType;
    //     }
    //     dd($data);

    //     dd("fin");

    //     $sql2 = " SELECT COUNT(*) as nb FROM ors_soumis_a_validation WHERE numeroOR= '{$numOr}' ";

    //     // // if ($this->retournerResult28($sql2) == 0) {
    //     //     // return 'ne pas bloquer';
    //     // // } else {
    //     //     $sql = " SELECT
    //     //         CASE
    //     //             WHEN COUNT(*) > 0 THEN 'ne pas bloquer'
    //     //             ELSE 'bloquer'
    //     //         END AS retour
    //     //     FROM ors_soumis_a_validation
    //     //     WHERE numeroOR = '{$numOr}'
    //     //     AND numeroVersion = (
    //     //         SELECT MAX(numeroVersion)
    //     //         FROM ors_soumis_a_validation
    //     //         WHERE numeroOR = '{$numOr}'
    //     //     )
    //     //     AND (
    //     //         statut LIKE '%Validé%' OR
    //     //         statut LIKE '%Refusé%' OR
    //     //         statut LIKE '%Livré partiellement%' OR
    //     //         statut LIKE '%Modification demandée par client%'
    //     //     )
    //     // ";

    //     //return $this->retournerResult28($sql);
    //     // }
    // }

    public function constructeurPieceMagasin(string $numOr)
    {
        $statement = " SELECT
            CASE
                WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) > 0
                AND COUNT(CASE WHEN slor_constp IN (" . GlobalVariablesService::get('pieceMagasinSansCat') . ") THEN 1 END) > 0
                THEN TRIM('CP')
            
                WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) > 0
                AND COUNT(CASE WHEN slor_constp IN (" . GlobalVariablesService::get('pieceMagasinSansCat') . ") THEN 1 END) = 0
                THEN TRIM('C')

                WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) = 0
                AND COUNT(CASE WHEN slor_constp IN (" . GlobalVariablesService::get('pieceMagasinSansCat') . ") THEN 1 END) = 0
                THEN TRIM('N')

                WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) = 0
                AND COUNT(CASE WHEN slor_constp IN (" . GlobalVariablesService::get('pieceMagasinSansCat') . ") THEN 1 END) > 0
                THEN TRIM('P')
            END AS retour
        FROM sav_lor
        WHERE slor_numor = '" . $numOr . "'
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function countAgServDebit($numOr, $codeSociete)
    {
        $statement = " SELECT count(distinct sitv_servdeb) as retour
                    from sav_itv 
                    where sitv_numor = '$numOr' AND sitv_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return (int) ($data[0]['retour'] ?? 0);
    }

    public function getNumcli(string $numOr, string $codeSociete): ?string
    {
        $statement = " SELECT seor_numcli as numcli
                    FROM {$this->dbIps}:Informix.sav_eor
                    WHERE seor_numor = '$numOr'
                    AND seor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'numcli')[0] ?? null;
    }

    public function numcliExiste(string $numcli, string $codeSociete): string
    {
        $statement = " SELECT  
        case
            when count(*) = 1 then 'existe_bdd' else ''
        end as numcli
        from cli_bse
        INNER JOIN cli_soc on csoc_numcli = cbse_numcli and csoc_soc = '$codeSociete' where cbse_numcli ='$numcli' and cbse_numcli > 0
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'numcli')[0] ?? '';
    }

    public function validationArticleZstDa($numOr)
    {
        $statement = " SELECT 
                    --TRIM(isl.slor_constp) as contructeur, 
                    ROUND(isl.slor_qterel) as quantite, 
                    TRIM(isl.slor_refp) as reference, 
                    isl.slor_pxnreel as montant,
                    TRIM(isl.slor_desi) as designation
                    from Informix.sav_lor isl 
                    where slor_constp ='ZST' 
                    and slor_soc ='HF' 
                    --and isl.slor_refp != 'ST'
                    and isl.slor_numor ='$numOr'
                    order by isl.slor_refp DESC
                    -- and isl.slor_numor ='" . $numOr . "'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function getTypeLigne($numOr)
    {
        $statement = "SELECT 
            TRIM(CASE 
                WHEN slor_constp IN (" . GlobalVariablesService::get('pieces_magasin') . GlobalVariablesService::get('achat_locaux') . ", 'ZST') 
                THEN 'bloquer' 
                ELSE 'pas bloquer' 
            END) AS est_bloquer
        FROM sav_lor 
        WHERE slor_numor = $numOr
    
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'est_bloquer');
    }

    public function getNumItv($numOr, string $codeSociete)
    {
        $statement = " SELECT sitv_interv  as num_itv
                    from sav_itv
                    where sitv_numor='$numOr' and sitv_soc='$codeSociete'
        ";
        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'num_itv');
    }

    public function getNumeroLigne(string $ref, string $designation, string $numOr)
    {
        $designation = str_replace("'", "''", mb_convert_encoding($designation, 'ISO-8859-1', 'UTF-8'));

        $statement = "  SELECT 
                MAX(slor_nolign) as numero_ligne
                from informix.sav_lor
                WHERE slor_constp = 'ZST' 
                and slor_typlig = 'P'
                and slor_refp not like ('PREST%')
                and REPLACE(slor_refp, '	','') = '$ref'
                and REPLACE(slor_desi, '	','') = '$designation'
                and slor_numor ='$numOr'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function getListeArticlesSavLorString(string $numOr, string $codeSociete): string
    {
        $statement = " SELECT TRIM(slor_refp) || REPLACE(TRIM(slor_desi), \"'\", \"''\") as refp_desi
            from sav_lor where slor_constp = 'ZST' and slor_numor = '$numOr' and slor_soc = '$codeSociete'
            ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return TableauEnStringService::TableauEnString(',', array_column($data, 'refp_desi'));
    }

    public function getNbrComparaisonArticleDaValiderEtSavLor(string $listeArticlesSavLorString, string $numOr): int
    {
        $sql = "SELECT count(*) 
            from da_valider dav  
            where dav.numero_or = '$numOr' 
            and numero_version = (select max(numero_version) from da_valider where numero_or = dav.numero_or)
            and concat(trim(art_refp),trim(art_desi)) in ($listeArticlesSavLorString)
        ";
        $data = $this->retournerResult28($sql);
        return (int) ($data[0]['count'] ?? 0);
    }

    public function getPieceFaibleActiviteAchat(string $constructeur, ?string $reference, string $numOr, string $codeSociete): array
    {

        $statement = "SELECT
                TRIM(case when 
                    A.nombre_jour >= 365 then 'a afficher'
                    else 'ne pas afficher'
                end) as retour
                , A.ffac_datef as date_derniere_cde
                , (select distinct slor_pmp from sav_lor where slor_numor = '$numOr' and slor_constp = '$constructeur' and slor_refp = '$reference') as pmp
                FROM
                (select first 1  
                ffac_datef
                , TODAY - ffac_datef as nombre_jour
                , fllf_numfac,*
                from {$this->dbIps}:informix.frn_llf 
                inner join {$this->dbIps}:informix.frn_fac on ffac_soc = fllf_soc and ffac_succ = fllf_succ and ffac_numfac = fllf_numfac
                inner join {$this->dbIps}:informix.frn_cde on fcde_soc = fllf_soc and fcde_succ = fllf_succ and fcde_numcde = fllf_numcde
                --inner join art_hpm on ahpm_soc = fllf_soc and ahpm_succfac = fllf_succ and ahpm_numfac = fllf_numfac and ahpm_constp = fllf_constp and ahpm_refp = fllf_refp
                where fllf_constp = '$constructeur'
                and fllf_refp = '$reference'
                and fllf_succ = '01'
                and ffac_serv = 'NEG'
                and fllf_soc = '$codeSociete'
                and fcde_numfou not in (select asuc_num from informix.agr_succ where asuc_numsoc = '$codeSociete')
                and fllf_qtefac > 0
                and fllf_constp in (" . GlobalVariablesService::get('pieces_magasin') . ")
                order by ffac_numfac desc) as A
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function getInformationOr(string $numOr, string $codeSociete): array
    {
        $statement = " SELECT
        slor_numor as numero_or,
        trim(seor_refdem) as numero_dit,
        sitv_interv as numero_itv,
        trim(sitv_comment) as libelle_itv,
        slor_constp as constructeur,
        trim(slor_refp) as reference,
        trim(slor_desi) as designation,
        slor_succ as code_agence, 
        slor_servcrt as code_service
        from {$this->dbIps}:Informix.sav_eor, {$this->dbIps}:Informix.sav_lor, {$this->dbIps}:Informix.sav_itv
        WHERE seor_numor = slor_numor
            AND seor_serv <> 'DEV'
            AND sitv_numor = slor_numor
            AND sitv_interv = slor_nogrp / 100
            AND seor_soc = '$codeSociete'
            AND seor_numor = '$numOr'
            AND slor_constp in (" . GlobalVariablesService::get('pieces_magasin') . ")
        order by slor_numor, sitv_interv
        ";
        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }



    public function findByNumeroDitAndSociete(string $numDit, string $codeSociete): array
    {
        $statement = "
        SELECT FIRST 1 *
        FROM {$this->dbIrium}:Informix.demande_intervention
        WHERE numero_demande_dit = '$numDit' AND code_societe = '$codeSociete'
    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $data[0] ?? [];
    }

    public function findNumeroVersionMax(string $numOr, string $codeSociete): int
    {
        $statement = "
        SELECT MAX(numeroVersion) AS numero_version_max
        FROM {$this->dbIrium}:Informix.ors_soumis_a_validation
        WHERE numeroor = '$numOr'
          AND code_societe = '$codeSociete'
    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return (int)($data[0]['numero_version_max'] ?? 0);
    }

    public function findByStatut(string $numOr, string $codeSociete, int $maxVersion)
    {
        $statement = "
        SELECT COUNT(o.id) AS total
        FROM {$this->dbIrium}:Informix.ors_soumis_a_validation o
        WHERE o.numeroor = '$numOr'
        AND o.numeroVersion = '$maxVersion'
        AND o.code_societe = '$codeSociete'
        AND (
            o.statut LIKE '%valide%'
            OR o.statut LIKE '%refuse%'
            OR o.statut LIKE '%livre_part%'
            OR o.statut LIKE '%modif_client%'
            OR o.statut LIKE '%modif_ca%'
            OR o.statut LIKE '%modif_dt%'
        )
    ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        $count = (int) ($data[0]['total'] ?? 0);

        return ($count > 0) ? 'bloquer' : 'ne pas bloquer';
    }

    public function getNbrOrSoumis(string $numOr, string $codeSociete): int
    {
        $statement = "
        SELECT COUNT(o.id) AS total
        FROM {$this->dbIrium}:Informix.ors_soumis_a_validation o
        WHERE o.numeroor = '$numOr'
        AND o.code_societe = '$codeSociete'
    ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        return (int) ($data[0]['total'] ?? 0);
    }

    public function findOrSoumiAvant(string $numOr, string $codeSociete)
    {
        $statement = "
        SELECT *
        FROM {$this->dbIrium}:Informix.ors_soumis_a_validation o
        WHERE o.numeroor = '$numOr'
        AND o.code_societe = '$codeSociete'
        AND o.numeroVersion = (
            SELECT MAX(o2.numeroVersion)
            FROM {$this->dbIrium}:Informix.ors_soumis_a_validation o2
            WHERE o2.numeroor = '$numOr'
            AND o2.code_societe = '$codeSociete'
        )
    ";

        $result = $this->connect->executeQuery($statement);

        return $this->connect->fetchResults($result);
    }

    public function findOrSoumiAvantMax(string $numOr, string $codeSociete)
    {
        // Étape 1 : récupérer MAX(numeroVersion)
        $statementMax = "
        SELECT MAX(o.numeroVersion) AS max_version
        FROM {$this->dbIrium}:Informix.ors_soumis_a_validation o
        WHERE o.numeroor = '$numOr'
        AND o.code_societe = '$codeSociete'
    ";

        $resultMax = $this->connect->executeQuery($statementMax);
        $dataMax = $this->connect->fetchResults($resultMax);

        $maxVersion = $dataMax[0]['max_version'] ?? null;

        if ($maxVersion === null || $maxVersion == 1) {
            // Même comportement que le dans repository
            return null;
        }

        // Étape 2 : récupérer la version juste avant la version max
        $statement = "
        SELECT *
        FROM {$this->dbIrium}:Informix.ors_soumis_a_validation o
        WHERE o.numeroor = '$numOr'
        AND o.numeroVersion = '" . ($maxVersion - 1) . "'
        AND o.code_societe = '$codeSociete'
    ";

        $result = $this->connect->executeQuery($statement);

        return $this->connect->fetchResults($result);
    }

    public function recupDatePlanningOR1($numOr, $numItv, $codeSociete)
    {
        $statement = " SELECT  
                            min(ska_d_start) as datePlanning1
                        from skw 
                        inner join ska on ska.skw_id = skw.skw_id 
                        where ofh_id ='$numOr'
                        and ofs_id = '$numItv'
                        and skw_soc ='$codeSociete'
                        group by ofh_id 
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupDatePlanningOR2($numOr, $numItv, $codeSociete)
    {
        $statement = " SELECT
                            min(sitv_datepla) as datePlanning2 
                        from sav_itv 
                        where sitv_numor = '$numOr'
                        and sitv_interv = '$numItv'
                        and sitv_soc = '$codeSociete'
                        group by sitv_numor
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }
}
