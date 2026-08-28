<?php

namespace App\Model\magasin\planning;

use App\Model\Model;
use App\Model\Informix\SelectWhereCondition;


class PlanningMagasinModel extends Model
{
    public function getPlanningMagasin(string $statut, bool $isEmptyQuery)
    {
        $statement = "SELECT *
        FROM(
            SELECT
                o.fcdl_numcde AS numero_commande,
                o.fbse_numfou as numero_fournisseur,
                o.fbse_nomfou as nom_fournisseur,
                TRIM(o.asuc_lib) || ' - ' || TRIM(o.atab_lib) AS agence_service,
                o.asuc_num as code_agence,
                TRIM(o.atab_code) as code_service,
                o.fcde_datec as date_commande,
                TRIM(
                    CASE
                        WHEN o.total_facture = o.total_qte THEN 'Complet facturé'
                        WHEN o.total_facture > 0 AND o.total_facture < o.total_qte THEN 'Partiellement facturé'
                        WHEN o.total_dispo > 0 AND o.total_dispo < o.total_qte THEN 'Partiellement dispo'
                        WHEN o.total_dispo = o.total_qte AND o.total_facture = 0 THEN 'Complet non facturé'
                        WHEN o.total_recu = 0 THEN 'Aucune reception'
                        ELSE 'Autre'
                    END
                ) AS statut
            FROM (
                SELECT
                    l.fcdl_constp,
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
                        fcdl.fcdl_constp,
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
                    GROUP BY fcdl.fcdl_constp, fcdl.fcdl_numcde, fcdl.fcdl_ligne, fcdl.fcdl_qte, fbse_numfou, fbse_nomfou, asuc_lib, atab_lib, asuc_num, atab_code, fcde_datec
                ) l
                GROUP BY l.fcdl_constp, l.fcdl_numcde, l.fbse_numfou, l.fbse_nomfou, l.asuc_lib, l.atab_lib, l.asuc_num, l.atab_code, l.fcde_datec
            ) o
            WHERE o.fcdl_constp IN (select distinct abse_constp from {$this->dbIps}.art_bse where abse_codg = 'ST')            
        ) t
        WHERE 1=1  {$this->conditionStatut($statut,$isEmptyQuery)} 
        ORDER BY t.numero_commande
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

    public function recupLigneCommande(string $numCde, string $codeSociete)
    {
        $statement = "--sql
        WITH cdl_filtre AS (
            SELECT
                c.fcdl_constp   AS constp,
                c.fcdl_refp     AS refp,
                c.fcdl_desi     AS desi,
                c.fcdl_qte      AS qte,
                c.fcdl_soc      AS soc,
                c.fcdl_succ     AS succ,
                c.fcdl_numcde   AS numcde,
                c.fcdl_ligne    AS ligne
            FROM {$this->dbIps}.frn_cdl c
            WHERE c.fcdl_numcde = '$numCde'
                AND c.fcdl_soc = '$codeSociete'
        )
        SELECT
            cde.constp,
            TRIM(cde.refp) AS refp,
            TRIM(cde.desi) AS desi,
            cde.qte_dem,
            cde.qte_dispo,
            CASE
                WHEN cde.qte_dispo > 0 AND cde.qte_dispo < cde.qte_dem THEN 'partiel'
                WHEN cde.qte_dispo > 0 AND cde.qte_dispo = cde.qte_dem THEN 'livre'
                ELSE ''
            END AS statut,
            res.numero,
            NVL(res.qtedem, 0) AS qte_dem_ligne,
            res.type_doc,
            res.numcli,
            res.nomcli,
            CASE 
                WHEN cde.constp = 'CAT' THEN COALESCE(cat.esd_date, sl1.eta_magasin, sl2.eta_magasin)
                ELSE COALESCE(sl1.eta_magasin, sl2.eta_magasin)
            END AS eta_magasin,
            CASE 
                WHEN cde.constp <> 'CAT' THEN COALESCE(sl1.eta_maurice, sl2.eta_maurice)
            END AS eta_maurice
        FROM
        (
            SELECT
                cf.numcde,
                cf.ligne,
                cf.constp,
                cf.refp,
                cf.desi,
                SUM(cf.qte) AS qte_dem,
                SUM(
                    CASE
                        WHEN l.fllf_majstk = 'O' THEN NVL(l.fllf_qteaff, 0)
                        ELSE 0
                    END
                ) AS qte_dispo
            FROM cdl_filtre cf
            LEFT JOIN {$this->dbIps}.frn_llf l
                ON cf.soc     = l.fllf_soc
                AND cf.succ   = l.fllf_succ
                AND cf.numcde = l.fllf_numcde
                AND cf.ligne  = l.fllf_ligne
            GROUP BY 1,2,3,4,5
        ) cde
        LEFT JOIN
        (
            SELECT DISTINCT
                TRIM('OR')    AS type_doc,
                o.slor_constp AS constp,
                liv.refp      AS refp,
                o.slor_numor  AS numero,
                CASE
                    WHEN o.slor_typlig = 'P' THEN (o.slor_qterel + o.slor_qterea + o.slor_qteres + o.slor_qtewait - o.slor_qrec)
                    WHEN o.slor_typlig IN ('F','M','U','C') THEN o.slor_qterea
                END AS qtedem,
                o.slor_numcli AS numcli,
                cb.cbse_nomcli AS nomcli
            FROM
            (
                SELECT DISTINCT
                    l.fllf_numcde AS numcde,
                    l.fllf_refp   AS refp,
                    l.fllf_numliv AS numliv
                FROM {$this->dbIps}.frn_llf l
                WHERE l.fllf_numcde = '$numCde'
                    AND l.fllf_refp IN (SELECT refp FROM cdl_filtre)
            ) liv
            INNER JOIN {$this->dbIps}.sav_lor o
                ON  o.slor_numcf = liv.numliv 
                AND o.slor_refp  = liv.refp
            INNER JOIN {$this->dbIps}.cli_bse cb ON cb.cbse_numcli = o.slor_numcli
            INNER JOIN {$this->dbIps}.cli_soc cs ON cs.csoc_soc = o.slor_soc AND cs.csoc_numcli = o.slor_numcli
            WHERE o.slor_soc = '$codeSociete'
        UNION ALL
            SELECT DISTINCT
                TRIM('VTEDIR') AS type_doc,
                n.nlig_constp AS constp,
                n.nlig_refp   AS refp,
                n.nlig_numcde AS numero,
                n.nlig_qtecde AS qtedem,
                n.nlig_numcli AS numcli,
                cb.cbse_nomcli AS nomcli
            FROM {$this->dbIps}.neg_lig n
            INNER JOIN {$this->dbIps}.cli_bse cb ON cb.cbse_numcli = n.nlig_numcli
            INNER JOIN {$this->dbIps}.cli_soc cs ON cs.csoc_soc = n.nlig_soc AND cs.csoc_numcli = n.nlig_numcli
            WHERE n.nlig_numcde = '$numCde'
                AND n.nlig_soc = '$codeSociete'
                AND n.nlig_refp IN (SELECT refp FROM cdl_filtre)
        ) res 
            ON  res.refp   = cde.refp 
            AND cde.constp = res.constp
        LEFT JOIN {$this->dbIrium}.gcot_acknow_cat cat 
            ON  cat.numero_po    = cde.numcde
            AND cat.parts_number = cde.refp 
            AND cat.parts_cst    = cde.constp
            AND cat.line_number  = cde.ligne
        LEFT JOIN (
            SELECT
                slnk_pk1    AS num_cde,
                slnk_pk2    AS no_lign,
                slnk_date1  AS eta_magasin,
                slnk_alpha1 AS eta_maurice
            FROM {$this->dbIpsRegix}.sip_lnk
            WHERE slnk_tabname IN ('frn_cdl', 'frn_cde')
                AND slnk_pk1 = '$numCde'
                AND slnk_pk2 IS NOT NULL
            ORDER BY slnk_id
        ) sl1 ON sl1.num_cde = cde.numcde AND sl1.no_lign = cde.ligne
        LEFT JOIN (
            SELECT
                slnk_pk1    AS num_cde,
                slnk_date1  AS eta_magasin,
                slnk_alpha1 AS eta_maurice
            FROM {$this->dbIpsRegix}.sip_lnk
            WHERE slnk_tabname IN ('frn_cdl', 'frn_cde')
                AND slnk_pk1 = '$numCde'
                AND slnk_pk2 IS NULL
            ORDER BY slnk_id
        ) sl2 ON sl2.num_cde = cde.numcde
        ORDER BY cde.refp, res.numero;";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    private function conditionStatut(string $statut, bool $isEmptyQuery): string
    {
        $statutParCondition = [
            'default'             => "default",
            'partiel_facture'     => "Partiellement facturé",
            'partiel_dispo'       => "Partiellement dispo",
            'complet_non_facture' => "Complet non facturé",
            'complet_facture'     => "Complet facturé",
            'back_order'          => "Back Order", // TODO: Pas encore de moyen pour les récupérer
        ];

        if ($statut === "tous" || (!$isEmptyQuery && $statut === "default")) return "";

        $selectWhereCondition = new SelectWhereCondition;

        $statutCondition = $statutParCondition[$statut];

        return ($statutCondition === "default")
            ? $selectWhereCondition->ne('statut', $statutParCondition['complet_facture']) //*** Par défaut ne pas afficher les commandes complet facturés
            : $selectWhereCondition->eq('statut', $statutCondition);
    }
}
