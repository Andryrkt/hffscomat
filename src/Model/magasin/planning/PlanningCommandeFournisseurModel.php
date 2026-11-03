<?php

namespace App\Model\magasin\planning;

use App\Model\Model;
use App\Model\Informix\SelectWhereCondition;
use App\Dto\Magasin\Planning\PlanningCommandeFournisseurSearchDto;


class PlanningCommandeFournisseurModel extends Model
{
    /**
     * @param array $monthsKey mois affichés au format 'Y-m' ; limite les commandes à cette fenêtre
     */
    public function getPlanningMagasin(string $statut, string $codeSociete, PlanningCommandeFournisseurSearchDto $dto, array $monthsKey = [])
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
                        ON atab_code = fcde_serv AND atab_nom  = 'SER'
                    LEFT JOIN {$this->dbIps}.frn_llf fllf
                        ON fllf.fllf_soc = fcdl.fcdl_soc AND fllf.fllf_numcde = fcdl.fcdl_numcde AND fllf.fllf_ligne = fcdl.fcdl_ligne
                    WHERE 1=1 {$this->conditionsCommande($dto,$monthsKey)}
                        AND (fcde_cdeext NOT LIKE '%CIS%' OR fcde_cdeext IS NULL)
                        AND fcde_soc = '$codeSociete'
                    GROUP BY fcdl.fcdl_constp, fcdl.fcdl_numcde, fcdl.fcdl_ligne, fcdl.fcdl_qte, fbse_numfou, fbse_nomfou, asuc_lib, atab_lib, asuc_num, atab_code, fcde_datec
                ) l
                GROUP BY l.fcdl_constp, l.fcdl_numcde, l.fbse_numfou, l.fbse_nomfou, l.asuc_lib, l.atab_lib, l.asuc_num, l.atab_code, l.fcde_datec
            ) o
            WHERE o.fcdl_constp IN (select distinct abse_constp from {$this->dbIps}.art_bse where abse_codg = 'ST') AND o.total_qte > 0
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
        // Activer la jointure sur la table {$this->dbIrium}.sip_lnk pour la société SCOMAT
        $sipLnkActive = $codeSociete === "CO";

        $asCdlFiltre      = 'cdl_filtre';
        $asCde            = 'cde';
        $asSipLnkLigne    = 'sl_ligne';
        $asSipLnkCommande = 'sl_cde';

        $statement = "--sql
        WITH
            $asCdlFiltre AS ({$this->getQueryCdlFiltre($numCde,$codeSociete)}),
            $asCde AS ({$this->getQueryCdeAgrege($asCdlFiltre)}),
            res AS (
                SELECT DISTINCT type_doc, constp, refp, numero, numcli, nomcli, SUM(NVL(qtedem, 0)) AS qtedem
                FROM (
                    {$this->getQueryResOrViaLivraison($numCde,$codeSociete,$asCdlFiltre)}
                    UNION ALL
                    {$this->getQueryResOrNonLivre($codeSociete,$asCdlFiltre)}
                    UNION ALL
                    {$this->getQueryResVenteDirecte($numCde,$codeSociete,$asCdlFiltre)}
                ) u
                GROUP BY type_doc, constp, refp, numero, numcli, nomcli
            )
        SELECT
            g.constp, 
            g.refp, 
            g.desi,
            g.qte_dem, 
            g.qte_rest, 
            g.qte_recept, 
            g.qte_dispo, 
            g.qte_fact,
            CASE
                WHEN g.qte_recept = 0 THEN 'en_attente'
                WHEN g.qte_dem > 0 AND g.qte_fact = g.qte_dem THEN 'complet_facture'
                WHEN g.qte_dem > 0 AND g.qte_fact > 0 AND g.qte_fact < g.qte_dem THEN 'partiel_facture'
                WHEN g.qte_dem > 0 AND g.qte_recept > 0 AND g.qte_rest > 0 THEN 'partiel_dispo'
                WHEN g.qte_dem > 0 AND g.qte_rest = 0 AND g.qte_fact = 0 THEN 'complet_non_facture'
                when g.qte_dem = 0 and g.qte_rest > 0 then 'en_attente'
                when g.qte_dem = 0 and g.qte_rest = 0 and g.qte_recept = g.qte_fact then 'complet_facture'
                WHEN g.qte_dem = 0 and g.qte_rest = 0 and g.qte_recept > 0 and g.qte_recept > g.qte_fact THEN 'complet_non_facture'
                ELSE ''
            END AS statut,
            g.numero, 
            g.qte_dem_ligne, 
            g.type_doc, 
            g.numcli, 
            g.nomcli, 
            g.back_order, 
            g.eta_magasin,
            g.eta_maurice
        FROM (
            SELECT
                x.constp, x.refp, x.desi,
                SUM(x.qte_dem) AS qte_dem,
                SUM(x.qte_rest) AS qte_rest,
                SUM(x.qte_recept) AS qte_recept,
                SUM(x.qte_dispo) AS qte_dispo,
                SUM(x.qte_fact) AS qte_fact,
                x.numero, x.qte_dem_ligne, x.type_doc, x.numcli, x.nomcli,
                x.eta_magasin, x.eta_maurice,
                MAX(x.back_order) AS back_order
            FROM (
                SELECT
                    $asCde.constp,
                    TRIM($asCde.refp) AS refp,
                    TRIM($asCde.desi) AS desi,
                    $asCde.qte_dem,
                    $asCde.qte_rest,
                    $asCde.qte_recept,
                    $asCde.qte_dispo,
                    $asCde.qte_fact,
                    res.numero,
                    NVL(res.qtedem, 0) AS qte_dem_ligne,
                    res.type_doc,
                    res.numcli,
                    res.nomcli,
                    CASE
                        WHEN bo.numero_po IS NOT NULL THEN 'O'
                        ELSE 'N'
                    END AS back_order,
                    {$this->getColonnesEta($sipLnkActive,$asSipLnkLigne,$asSipLnkCommande)}
                FROM $asCde
                LEFT JOIN res
                    ON  res.refp   = $asCde.refp
                    AND res.constp = $asCde.constp
                LEFT JOIN (
                    SELECT DISTINCT
                        CAST(b.numero_po AS VARCHAR(50)) AS numero_po,
                        b.parts_cst,
                        b.parts_number,
                        b.line_number
                    FROM {$this->dbIrium}.gcot_acknow_cat AS b
                    WHERE CAST(b.libelle_type AS VARCHAR(10)) = 'Back Order'
                        AND b.id_gcot_acknow_cat = (
                            SELECT MAX(sub.id_gcot_acknow_cat)
                            FROM {$this->dbIrium}.gcot_acknow_cat AS sub
                            WHERE sub.parts_number = b.parts_number
                                AND sub.numero_po    = b.numero_po
                                AND sub.line_number  = b.line_number
                                AND sub.quantity > 0
                        )
                ) bo
                    ON  bo.numero_po    = CAST($asCde.numcde AS VARCHAR(50))
                    AND bo.parts_cst    = $asCde.constp
                    AND bo.parts_number = TRIM($asCde.refp)
                    AND bo.line_number  = $asCde.ligne
                {$this->getJoinSipLnk($sipLnkActive,$numCde,$asCde,$asSipLnkLigne,$asSipLnkCommande)}
            ) x
            GROUP BY x.constp, x.refp, x.desi, x.numero, x.qte_dem_ligne, x.type_doc, x.numcli, x.nomcli, x.eta_magasin, x.eta_maurice
        ) g
        ORDER BY g.refp, g.numero";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    private function getQueryCdlFiltre(string $numCde, string $codeSociete): string
    {
        return "SELECT 
                    c.fcdl_constp   AS constp,
                    c.fcdl_refp     AS refp,
                    c.fcdl_desi     AS desi,
                    c.fcdl_qte      AS qte_dem,
                    c.fcdl_solde    AS qte_rest,
                    c.fcdl_qteli    AS qte_recept,
                    c.fcdl_qtefa    AS qte_fact,
                    c.fcdl_soc      AS soc,
                    c.fcdl_succ     AS succ,
                    c.fcdl_numcde   AS numcde,
                    c.fcdl_ligne    AS ligne
                FROM {$this->dbIps}.frn_cdl c
                WHERE c.fcdl_numcde = '$numCde'
                    AND c.fcdl_soc = '$codeSociete'";
    }

    private function getQueryCdeAgrege(string $asCdlFiltre): string
    {
        return "SELECT 
                    cf.numcde,
                    cf.ligne,
                    cf.constp,
                    cf.refp,
                    cf.desi,
                    SUM(cf.qte_dem) AS qte_dem,
                    SUM(cf.qte_rest) as qte_rest,
                    SUM(cf.qte_recept) as qte_recept,
                    SUM(
                        CASE
                            WHEN l.fllf_majstk = 'O' THEN NVL(l.fllf_qteliv, 0)
                            ELSE 0
                        END
                    ) AS qte_dispo,
                    SUM(cf.qte_fact) as qte_fact
                FROM $asCdlFiltre cf
                LEFT JOIN {$this->dbIps}.frn_llf l
                    ON cf.soc     = l.fllf_soc
                    AND cf.succ   = l.fllf_succ
                    AND cf.numcde = l.fllf_numcde
                    AND cf.ligne  = l.fllf_ligne
                GROUP BY 1,2,3,4,5";
    }

    /** OR déjà livrés : lien commande -> livraison fournisseur (frn_llf.numliv) -> sav_lor.slor_numcf */
    private function getQueryResOrViaLivraison(string $numCde, string $codeSociete, string $asCdlFiltre): string
    {
        return "SELECT DISTINCT
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
                        AND l.fllf_refp IN (SELECT refp FROM $asCdlFiltre)
                ) liv
                INNER JOIN {$this->dbIps}.sav_lor o
                    ON  o.slor_numcf = liv.numliv
                    AND o.slor_refp  = liv.refp
                INNER JOIN {$this->dbIps}.cli_bse cb ON cb.cbse_numcli = o.slor_numcli
                INNER JOIN {$this->dbIps}.cli_soc cs ON cs.csoc_soc = o.slor_soc AND cs.csoc_numcli = o.slor_numcli
                WHERE o.slor_soc = '$codeSociete'";
    }

    /** OR non livrés : pièces en commande (slor_natcm = 'C'), liées à la ligne de commande par slor_noligncm */
    private function getQueryResOrNonLivre(string $codeSociete, string $asCdlFiltre): string
    {
        return "SELECT DISTINCT
                    TRIM('OR')    AS type_doc,
                    o.slor_constp AS constp,
                    c.refp        AS refp,
                    o.slor_numor  AS numero,
                    CASE
                        WHEN o.slor_typlig = 'P' THEN (o.slor_qterel + o.slor_qterea + o.slor_qteres + o.slor_qtewait - o.slor_qrec)
                        WHEN o.slor_typlig IN ('F','M','U','C') THEN o.slor_qterea
                    END AS qtedem,
                    o.slor_numcli AS numcli,
                    cb.cbse_nomcli AS nomcli
                FROM $asCdlFiltre c
                INNER JOIN {$this->dbIps}.sav_lor o
                    ON  o.slor_constp   = c.constp
                    AND o.slor_refp     = c.refp
                    AND TRIM(o.slor_desi) = TRIM(c.desi)
                    AND o.slor_noligncm = c.ligne
                    AND o.slor_natcm    = 'C'
                INNER JOIN {$this->dbIps}.cli_bse cb ON cb.cbse_numcli = o.slor_numcli
                INNER JOIN {$this->dbIps}.cli_soc cs ON cs.csoc_soc = o.slor_soc AND cs.csoc_numcli = o.slor_numcli
                WHERE o.slor_soc = '$codeSociete'";
    }

    private function getQueryResVenteDirecte(string $numCde, string $codeSociete, string $asCdlFiltre): string
    {
        return "SELECT DISTINCT
                    TRIM('VTEDIR') AS type_doc,
                    n.nlig_constp AS constp,
                    n.nlig_refp   AS refp,
                    n.nlig_numcde AS numero,
                    n.nlig_qtecde AS qtedem,
                    e.nent_numcli AS numcli,
                    TRIM(e.nent_nomcli) AS nomcli
                FROM {$this->dbIps}.neg_lig n
                INNER JOIN {$this->dbIps}.neg_ent e
                    ON  e.nent_soc    = n.nlig_soc
                    AND e.nent_succ   = n.nlig_succ
                    AND e.nent_numcde = n.nlig_numcde
                WHERE n.nlig_numcf = '$numCde'
                    AND n.nlig_soc = '$codeSociete'
                    AND n.nlig_refp IN (SELECT refp FROM $asCdlFiltre)";
    }

    /**
     * Colonnes eta_magasin/eta_maurice, dépendantes de la jointure sip_lnk.
     * Si sip_lnk est désactivée pour l'environnement courant, ces colonnes
     * retombent sur cat.esd_date / NULL au lieu d'échouer sur des alias absents.
     */
    private function getColonnesEta(bool $sipLnkActive, string $asSipLnkLigne, string $asSipLnkCommande): string
    {
        if (!$sipLnkActive) {
            return "--sql
            CAST(NULL AS CHAR(1)) AS eta_magasin,
            CAST(NULL AS CHAR(1)) AS eta_maurice";
        }

        return "--sql
            COALESCE($asSipLnkLigne.eta_magasin, $asSipLnkCommande.eta_magasin) AS eta_magasin,
            COALESCE($asSipLnkLigne.eta_maurice, $asSipLnkCommande.eta_maurice) AS eta_maurice";
    }

    private function getJoinSipLnk(bool $sipLnkActive, string $numCde, string $asCde, string $asSipLnkLigne, string $asSipLnkCommande): string
    {
        if (!$sipLnkActive) return "";

        return "--sql
            LEFT JOIN (
                SELECT
                    slnk_pk1    AS num_cde,
                    slnk_pk2    AS no_lign,
                    slnk_date1  AS eta_magasin,
                    slnk_alpha1 AS eta_maurice
                FROM {$this->dbIpsRegix}.sip_lnk s
                WHERE slnk_tabname IN ('frn_cdl', 'frn_cde')
                    AND slnk_pk1 = '$numCde'
                    AND slnk_pk2 IS NOT NULL
                    AND slnk_id = (
                        SELECT MAX(m.slnk_id)
                        FROM {$this->dbIpsRegix}.sip_lnk m
                        WHERE m.slnk_tabname IN ('frn_cdl', 'frn_cde')
                            AND m.slnk_pk1 = s.slnk_pk1
                            AND m.slnk_pk2 = s.slnk_pk2
                    )
            ) $asSipLnkLigne ON $asSipLnkLigne.num_cde = $asCde.numcde 
                AND $asSipLnkLigne.no_lign = $asCde.ligne
            LEFT JOIN (
                SELECT
                    slnk_pk1    AS num_cde,
                    slnk_date1  AS eta_magasin,
                    slnk_alpha1 AS eta_maurice
                FROM {$this->dbIpsRegix}.sip_lnk s
                WHERE slnk_tabname IN ('frn_cdl', 'frn_cde')
                    AND slnk_pk1 = '$numCde'
                    AND slnk_pk2 IS NULL
                    AND slnk_id = (
                        SELECT MAX(m.slnk_id)
                        FROM {$this->dbIpsRegix}.sip_lnk m
                        WHERE m.slnk_tabname IN ('frn_cdl', 'frn_cde')
                            AND m.slnk_pk1 = '$numCde'
                            AND m.slnk_pk2 IS NULL
                    )
            ) $asSipLnkCommande ON $asSipLnkCommande.num_cde = $asCde.numcde";
    }

    /** Filtres du formulaire + fenêtre de mois, appliqués avant les agrégations. */
    private function conditionsCommande(PlanningCommandeFournisseurSearchDto $dto, array $monthsKey): string
    {
        $where = new SelectWhereCondition;
        $sql = '';

        if ($monthsKey) {
            $anneesMois = array_map(fn($k) => (int) str_replace('-', '', $k), $monthsKey); // Ex: [202611,202612,202701,202702,...]
            $sql .= $where->in('(YEAR(fcde_datec) * 100 + MONTH(fcde_datec))', $anneesMois);
        }

        $sql .= $where->between('fcde_datec', $dto->dateCommande['debut'] ?? null, $dto->dateCommande['fin'] ?? null);

        // Fournisseur : recherche partielle sur le code ou le nom
        $sql .= $where->like("fbse_numfou || ' ' || fbse_nomfou", $dto->fournisseur);

        $agence = $dto->agenceService['agence'] ?? null;
        $service = $dto->agenceService['service'] ?? null;

        $sql .= $where->eq('fcde_numcde', $dto->numeroCommande);
        $sql .= $where->eq('asuc_num', $agence ? $agence->getCodeAgence() : null);
        $sql .= $where->eq('atab_code', $service ? $service->getCodeService() : null);

        return $sql;
    }

    private function conditionStatut(string $statut, bool $isEmptyQuery): string
    {
        $statutParCondition = [
            'en_cours'            => "en_cours",
            'partiel_facture'     => "Partiellement facturé",
            'partiel_dispo'       => "Partiellement dispo",
            'complet_non_facture' => "Complet non facturé",
            'complet_facture'     => "Complet facturé",
            'back_order'          => "Back Order", // TODO: Pas encore de moyen pour les récupérer
        ];

        if ($statut === "tous") return "";
        $selectWhereCondition = new SelectWhereCondition;

        if ($statut === "back_order") return $selectWhereCondition->eq('t.back_order', "O");

        $statutCondition = $statutParCondition[$statut];

        return ($statutCondition === "en_cours")
            ? $selectWhereCondition->ne('statut', $statutParCondition['complet_facture']) //*** Par défaut ne pas afficher les commandes complet facturés
            : $selectWhereCondition->eq('statut', $statutCondition);
    }
}
