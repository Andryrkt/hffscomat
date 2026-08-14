<?php

namespace App\Model\magasin\planning;

use App\Model\Model;


class PlanningMagasinModel extends Model
{
    public function getPlanningMagasin()
    {
        $statement = "SELECT
                o.fcdl_numcde AS numero_commande,
                o.fbse_numfou as numero_fournisseur,
                o.fbse_nomfou as nom_fournisseur,
                o.asuc_lib || ' - ' || o.atab_lib AS agence_service,
                o.asuc_num as code_agence,
                o.atab_code as code_service,
                o.fcde_datec as date_commande,
                CASE
                    WHEN o.total_facture > 0 AND o.total_facture < o.total_qte THEN 'Partiellement facturé'
                    WHEN o.total_dispo > 0 AND o.total_dispo < o.total_qte THEN 'Partiellement dispo'
                    WHEN o.total_dispo = o.total_qte AND o.total_facture = 0 THEN 'Complet non facturé'
                    WHEN o.total_recu = 0 THEN 'Aucune reception'
                    WHEN o.total_facture = o.total_qte THEN 'Complet facturé'
                    ELSE 'Autre'
                END AS statut
            FROM (
                SELECT
                    l.fcdl_numcde,
                    l.fbse_numfou,
                    l.fbse_nomfou,
                    l.asuc_lib,
                    l.atab_lib,
                    l.asuc_num,
                    l.atab_code,
                    l.fcde_datec,
                    SUM(l.fcdl_qte)     AS total_qte,
                    SUM(l.qte_recue)    AS total_recu,
                    SUM(l.qte_facturee) AS total_facture,
                    SUM(l.qte_dispo)    AS total_dispo
                FROM (
                    SELECT
                        fcdl.fcdl_numcde,
                        fcdl.fcdl_ligne,
                        fcdl.fcdl_qte,
                        fbse_numfou,
                        fbse_nomfou,
                        asuc_lib,
                        atab_lib,
                        asuc_num,
                        atab_code,
                        fcde_datec,
                        NVL(SUM(fllf.fllf_qteliv), 0) AS qte_recue,
                        NVL(SUM(fllf.fllf_qtefac), 0) AS qte_facturee,
                        NVL(SUM(CASE WHEN fllf.fllf_majstk = 'O' THEN fllf.fllf_qteliv ELSE 0 END), 0) AS qte_dispo
                    FROM {$this->dbIps}.frn_cdl fcdl
                    INNER JOIN {$this->dbIps}.frn_cde
                        ON fcdl_soc    = fcde_soc
                    AND fcdl_succ   = fcde_succ
                    AND fcdl_numcde = fcde_numcde
                    INNER JOIN {$this->dbIps}.frn_bse
                        ON fbse_numfou = fcde_numfou
                    INNER JOIN {$this->dbIps}.agr_succ
                        ON asuc_num = fcde_succ
                    INNER JOIN {$this->dbIps}.agr_tab
                        ON atab_code = fcde_serv
                    AND atab_nom  = 'SER'
                    LEFT JOIN {$this->dbIps}.frn_llf fllf
                        ON fllf.fllf_soc = fcdl.fcdl_soc AND fllf.fllf_numcde = fcdl.fcdl_numcde AND fllf.fllf_ligne = fcdl.fcdl_ligne
                    WHERE year(fcde_date) = 2026
                    AND (fcde_cdeext NOT LIKE '%CIS%' OR fcde_cdeext IS NULL)
                    AND fcdl_constp IN (select distinct abse_constp from {$this->dbIps}.art_bse where abse_codg = 'ST')
                    GROUP BY fcdl.fcdl_numcde, fcdl.fcdl_ligne, fcdl.fcdl_qte, fbse_numfou, fbse_nomfou, asuc_lib, atab_lib, asuc_num, atab_code, fcde_datec
                ) l
                GROUP BY l.fcdl_numcde, l.fbse_numfou, l.fbse_nomfou, l.asuc_lib, l.atab_lib, l.asuc_num, l.atab_code, l.fcde_datec
            ) o
            ORDER BY o.fcdl_numcde
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupListeFournissseur(string $codeSociete)
    {
        $statement = "SELECT DISTINCT
                        fbse_numfou AS num_fournisseur,
                        TRIM(fbse_nomfou) AS nom_fournisseur
                    FROM {$this->dbIps}.frn_bse
                        JOIN {$this->dbIps}.frn_fou ON fbse_numfou=ffou_numfou
                        JOIN {$this->dbIps}.frn_cde on fcde_numfou=fbse_numfou
                    WHERE ffou_soc='$codeSociete'
                    ORDER by nom_fournisseur";

        $result = $this->connect->executeQuery($statement);


        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    function recupLigneCommande(string $numCde, string $codeSociete)
    {
        $statement = "SELECT 
                cde.constp,
                cde.refp,
                cde.desi,
                cde.qte_dem,
                cde.qte_dem - cde.qte_dispo AS qte_rest,
                case
                    when cde.qte_dispo > 0 and cde.qte_dispo < cde.qte_dem then 'partiel'
                    when cde.qte_dispo > 0 and cde.qte_dispo = cde.qte_dem then 'livre'
                    else ''
                end as statut,
                res.numero,
                res.qtedem as qte_dem_ligne,
                res.numcli ,
                res.nomcli
        FROM
        (
            SELECT
                c.fcdl_constp   AS constp,
                c.fcdl_refp     AS refp,
                c.fcdl_desi     AS desi,
                SUM(c.fcdl_qte) AS qte_dem,
                SUM(
                    CASE
                        WHEN l.fllf_majstk = 'O' THEN NVL(l.fllf_qteaff, 0)
                        ELSE 0
                    END
                ) AS qte_dispo
            FROM {$this->dbIps}.frn_cdl c
            LEFT JOIN {$this->dbIps}.frn_llf l
                ON c.fcdl_soc    = l.fllf_soc
                AND c.fcdl_succ   = l.fllf_succ
                AND c.fcdl_numcde = l.fllf_numcde
                AND c.fcdl_ligne  = l.fllf_ligne
            WHERE c.fcdl_numcde = '$numCde'
                AND c.fcdl_soc = '$codeSociete'
            GROUP BY 1,2,3
        ) cde
        LEFT JOIN
        (
            SELECT DISTINCT
                liv.refp      AS refp,
                o.slor_numor  AS numero,
                CASE
                    WHEN o.slor_typlig = 'P' THEN (o.slor_qterel + o.slor_qterea + o.slor_qteres + o.slor_qtewait - o.slor_qrec)
                    WHEN o.slor_typlig IN ('F','M','U','C') THEN o.slor_qterea
                end as qtedem,
                o.slor_numcli as numcli,
                cb.cbse_nomcli as nomcli
            FROM
            (
                SELECT DISTINCT
                    l.fllf_refp   AS refp,
                    l.fllf_numliv AS numliv
                FROM {$this->dbIps}.frn_llf l
                WHERE l.fllf_numcde = '$numCde'
                AND l.fllf_refp IN (
                    SELECT fcdl_refp FROM {$this->dbIps}.frn_cdl
                    WHERE fcdl_numcde = '$numCde'
                )
            ) liv
            INNER JOIN {$this->dbIps}.sav_lor o
                ON o.slor_numcf = liv.numliv
            AND o.slor_refp  = liv.refp
            INNER JOIN {$this->dbIps}.cli_bse cb ON cb.cbse_numcli = o.slor_numcli
            INNER JOIN {$this->dbIps}.cli_soc cs ON cs.csoc_soc = o.slor_soc AND cs.csoc_numcli = o.slor_numcli
            WHERE o.slor_soc = '$codeSociete'
            UNION ALL
            SELECT DISTINCT
                n.nlig_refp   AS refp,
                n.nlig_numcde AS numero,
                n.nlig_qtecde as qtedem,
                n.nlig_numcli as numcli,
                cb.cbse_nomcli as nomcli
            FROM {$this->dbIps}.neg_lig n
            INNER JOIN {$this->dbIps}.cli_bse cb ON cb.cbse_numcli = n.nlig_numcli
            INNER JOIN {$this->dbIps}.cli_soc cs ON cs.csoc_soc = n.nlig_soc AND cs.csoc_numcli = n.nlig_numcli
            WHERE n.nlig_numcde = '$numCde'
            AND n.nlig_soc = '$codeSociete'
            AND n.nlig_refp IN (
                SELECT fcdl_refp FROM {$this->dbIps}.frn_cdl
                WHERE fcdl_numcde = '$numCde'
            )
        ) res
        ON res.refp = cde.refp
        ORDER BY cde.refp, res.numero;";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }
}
