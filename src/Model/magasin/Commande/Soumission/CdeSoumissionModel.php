<?php

namespace App\Model\magasin\Commande\Soumission;

use App\Model\Model;
use App\Model\Informix\InsertQueryBuilder;
use App\Model\Informix\SelectWhereCondition;
use App\Dto\Magasin\Commande\Soumission\BcSoumisMagasinDTO;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Factory\magasin\Commande\Soumission\CommandeSoumissionFactory;

class CdeSoumissionModel extends Model
{
    private SelectWhereCondition $selectCond;

    public function __construct()
    {
        parent::__construct();
        $this->selectCond = new SelectWhereCondition();
    }

    /** 
     * Méthode pour retourner les infos sur la commande avec $numCde
     * 
     * @param string $numCde       numéro de la commande
     * @param string $userMail     email de l'utilisateur
     * @param string $succursale   succursale
     * @param string $codeSociete  code société
     * 
     * @return ?CommandeSoumissionDTO
     */
    public function findInfoCommande(string $numCde, string $userMail, string $succursale = '1', string $codeSociete = 'CO'): ?CommandeSoumissionDTO
    {
        $startDate = (new \DateTime('first day of -6 months'))->format("Ym");
        $endDate   = (new \DateTime('last day of last month'))->format("Ym");

        $ipsArtSta = ($codeSociete === 'CO') ? "{$this->dbIpsRegix}.art_sta" : "{$this->dbIps}.art_sta";
        $ipsAgrDev = ($codeSociete === 'CO') ? "{$this->dbIpsRegix}.agr_dev" : "{$this->dbIps}.agr_dev";

        $statement = "SELECT 
            fcde_numcde as num_cde,
            fcde_date AS date_cde,
            (
                SELECT TRIM(atab_lib)
                FROM {$this->dbIps}.agr_tab
                WHERE atab_code = fcde_typcde AND atab_nom  = 'TOP'
            ) AS type_cde,
            fcde_numfou AS num_frn,
            TRIM(fbse_nomfou) AS nom_frn,
            fbse_devise AS devise_code,
            (
                SELECT TRIM(adev_lib)
                FROM $ipsAgrDev
                WHERE adev_code = fbse_devise
            ) AS devise_libelle,
            (
                SELECT TRIM(asuc_lib)
                FROM {$this->dbIps}.agr_succ
                WHERE asuc_num = fcde_succ
            ) AS agence_lib,
            (
                SELECT TRIM(atab_lib)
                FROM {$this->dbIps}.agr_tab
                WHERE atab_nom  = 'SER' AND atab_code = fcde_serv
            ) AS service_lib,
            TRIM(fcdl_constp) AS cst,
            CASE TRIM(abse_libre1)
                WHEN 'A' THEN '(A)'
                ELSE '(B)'
            END AS av_bt,
            TRIM(fcdl_refp) AS refp,
            (
                SELECT afrn_cond
                FROM {$this->dbIps}.art_frn
                WHERE afrn_numf    = fcde_numfou
                AND   afrn_constp  = fcdl_constp
                AND   afrn_refp    = fcdl_refp
                AND   afrn_dated   = (
                    SELECT MAX(afrn_dated)
                    FROM {$this->dbIps}.art_frn
                    WHERE afrn_numf   = fcde_numfou
                    AND   afrn_constp = fcdl_constp
                    AND   afrn_refp   = fcdl_refp
                )
            ) AS package_qty,
            TRIM(fcdl_desi) AS desi,
            CASE NVL(
                (
                    SELECT SUM(astp_stock - astp_reserv)
                    FROM {$this->dbIps}.art_stp
                    WHERE astp_constp = fcdl_constp
                    AND astp_refp IN (
                        SELECT armp_ref
                        FROM {$this->dbIps}.art_rmp
                        WHERE armp_nivr   = 2
                        AND armp_constp = fcdl_constp
                        AND armp_refp   = fcdl_refp
                    )
                ), 0
            )   WHEN 0 THEN ''
                ELSE '(*)'
            END AS npr,
            TRIM(abse_libre2) AS fms,
            fcdl_qte AS qte_cde,
            (
                SELECT NVL(astp_stock - astp_reserv, 0)
                FROM {$this->dbIps}.art_stp
                WHERE astp_constp = fcdl_constp
                AND astp_refp   = fcdl_refp
                AND astp_succ   = '$succursale'
            ) AS stock_dispo,
            (
                SELECT astp_min1
                FROM {$this->dbIps}.art_stp
                WHERE astp_constp = fcdl_constp
                AND astp_refp   = fcdl_refp
                AND astp_succ   = '$succursale'
            ) AS stock_min,
            (
                SELECT astp_max1
                FROM {$this->dbIps}.art_stp
                WHERE astp_constp = fcdl_constp
                AND astp_refp   = fcdl_refp
                AND astp_succ   = '$succursale'
            ) AS stock_max,
            (
                SELECT NVL(SUM(asta_qtesor), 0)
                FROM $ipsArtSta
                WHERE asta_constp = fcdl_constp
                AND asta_refp   = fcdl_refp
                AND asta_per >= '$startDate'
                AND asta_per <= '$endDate'
            ) AS vte_der_mois,
            (
                SELECT NVL(SUM(asta_nblign), 0)
                FROM $ipsArtSta
                WHERE asta_constp = fcdl_constp
                AND asta_refp   = fcdl_refp
                AND asta_per >= '$startDate'
                AND asta_per <= '$endDate'
            ) AS nbr_vente,
            fcdl_pxach * (1 - (fcdl_txrem / 100)) AS prix_unit,
            fcdl_qte * fcdl_pxach * (1 - (fcdl_txrem / 100)) AS montant,
            fcdl_qte * abse_poids AS poids_total
        FROM {$this->dbIps}.frn_cde, {$this->dbIps}.frn_cdl, {$this->dbIps}.art_bse, {$this->dbIps}.frn_bse, {$this->dbIps}.frn_fou
        WHERE   fcde_numcde = '$numCde'
            AND fcde_soc    = '$codeSociete'
            AND fcde_numcde = fcdl_numcde
            AND fcde_soc    = fcdl_soc
            AND fcde_succ   = fcdl_succ
            AND fcdl_constp = abse_constp
            AND fcdl_refp   = abse_refp
            AND fcde_numfou = fbse_numfou
            AND fbse_numfou = ffou_numfou
            AND fcde_soc    = ffou_soc
        ORDER BY fcdl_ref";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        if (empty($data)) return null;

        // --- Extraction des couples (cst, refp) distincts ---
        $pairs = [];
        foreach ($data as $row) {
            $cst  = trim($row['cst']);
            $refp = trim($row['refp']);
            $pairs["$cst|$refp"] = ['cst' => $cst, 'refp' => $refp];
        }

        $detailsParPiece = $this->findLignesSavEtVenteNegoceParPieces($numCde, $pairs, $codeSociete);
        $allValidatedPO  = $this->findAllValidatedPO($numCde, $codeSociete);

        return (new CommandeSoumissionFactory)->hydrate($data, $detailsParPiece["lignesParPiece"], $detailsParPiece["ordresReparationValides"], $allValidatedPO, $userMail);
    }

    /**
     * Retourne, en une seule requête, toutes les lignes liées à une commande fournisseur
     * (ordres de réparation SAV + ventes négoce) pour un ensemble de pièces.
     *
     * Une pièce est identifiée par le couple (constructeur, référence).
     *
     * @param string $numCde            Numéro de la commande fournisseur
     * @param array<string,array{cst:string,refp:string}> $piecesRecherchees Couples (cst, refp) issus de `findInfoCommande`
     * @param string $codeSociete       Code société
     *
     * @return array{ordresReparationValides:list<string>,lignesParPiece:array<string,list<array>>}
     */
    private function findLignesSavEtVenteNegoceParPieces(string $numCde, array $piecesRecherchees, string $codeSociete): array
    {
        if (empty($piecesRecherchees)) return ['ordresReparationValides' => [], 'lignesParPiece' => []];

        $refsParConstructeur = [];
        foreach ($piecesRecherchees as ['cst' => $cst, 'refp' => $refp]) {
            $refsParConstructeur[$cst][] = $refp;
        }

        $conditionsSav = [];
        foreach ($refsParConstructeur as $cst => $refps) {
            $conditionsSav[] = "(slor_constp = '$cst' {$this->selectCond->in('slor_refp',$refps)})";
        }
        $whereSav = implode(' OR ', $conditionsSav);

        $conditionsNegoce = [];
        foreach ($refsParConstructeur as $cst => $refps) {
            $conditionsNegoce[] = "(nlig_constp = '$cst' {$this->selectCond->in('nlig_refp',$refps)})";
        }
        $whereNegoce = implode(' OR ', $conditionsNegoce);

        $statement = "SELECT 
            TRIM(slor_constp) AS cst, 
            TRIM(slor_refp)   AS refp,
            TRIM(seor_lib)    AS lib, 
            seor_numor        AS num_doc, 
            seor_numcli       AS num_cli, 
            TRIM(seor_nomcli) AS nom_cli,
            TRIM('OR')        AS rmq,
            CASE
                WHEN plan.min_start IS NULL 
                THEN TO_CHAR(sitv_datepla, '%Y-%m-%d')
                ELSE TO_CHAR(plan.min_start, '%Y-%m-%d')
            END AS datepla
        FROM {$this->dbIps}.sav_eor
        JOIN {$this->dbIps}.sav_lor
            ON seor_numor = slor_numor 
            AND slor_soc = seor_soc 
            AND slor_succ = seor_succ
        JOIN {$this->dbIps}.sav_itv
            ON sitv_numor = slor_numor
            AND sitv_interv = TRUNC(slor_nogrp / 100)
            AND sitv_soc = seor_soc 
            AND sitv_succ = seor_succ
        LEFT JOIN (
            SELECT ofh_id, ofs_id, MIN(ska_d_start) AS min_start
            FROM {$this->dbIps}.ska
                JOIN {$this->dbIps}.skw 
                ON skw.skw_id = ska.skw_id
            GROUP BY ofh_id, ofs_id
        ) plan ON plan.ofh_id = seor_numor AND plan.ofs_id = sitv_interv
        WHERE   slor_numcf  = '$numCde'
            AND slor_natcm  = 'C'
            AND seor_serv   = 'SAV'
            AND ({$whereSav})

        UNION

        SELECT
            TRIM(nlig_constp) AS cst, 
            TRIM(nlig_refp)   AS refp,
            TRIM(nent_refcde) AS lib,
            nent_numcde       AS num_doc, 
            nent_numcli       AS num_cli, 
            TRIM(nent_nomcli) AS nom_cli,
            TRIM('VTE NEG')   AS rmq, 
            CASE 
                WHEN nent_delai IS NOT NULL 
                THEN TO_CHAR(nent_delai, '%Y-%m-%d') 
                ELSE NULL 
            END AS datepla
        FROM {$this->dbIps}.neg_ent
        JOIN {$this->dbIps}.neg_lig 
            ON nent_numcde = nlig_numcde
        WHERE nlig_numcf  = '$numCde'
            AND ({$whereNegoce})";

        $result = $this->connect->executeQuery($statement);
        $rows   = $this->connect->fetchResults($result);

        $lignesParPiece = [];
        $numerosOR      = [];

        foreach ($rows as $row) {
            $cst  = trim($row['cst']);
            $refp = trim($row['refp']);
            $lignesParPiece["$cst|$refp"][] = $row;

            if (trim($row['rmq']) === 'OR') $numerosOR[] = $row['num_doc'];
        }

        return [
            'ordresReparationValides' => $this->filterOrdresReparationValides($numerosOR, $codeSociete),
            'lignesParPiece'          => $lignesParPiece,
        ];
    }

    /**
     * Filtre une liste de numéros d'OR pour ne conserver que ceux dont la
     * DERNIÈRE version soumise à validation a le statut « Validé ».
     *
     * @param list<string> $numerosOR   Numéros d'ordres de réparation à contrôler
     * @param string       $codeSociete Code société
     *
     * @return list<string> ORs validés (liste vide si aucun)
     */
    private function filterOrdresReparationValides(array $numerosOR, string $codeSociete): array
    {
        $data = [];
        if (empty($numerosOR)) return $data;

        $statement = "SELECT FIRST 50 DISTINCT osav.numeroor
            FROM {$this->dbIrium}.ors_soumis_a_validation osav
            WHERE osav.code_societe = '$codeSociete'
            AND osav.statut LIKE 'Valid%'
            AND osav.numeroversion = (
                SELECT MAX(o1.numeroversion)
                FROM {$this->dbIrium}.ors_soumis_a_validation o1
                WHERE o1.numeroor = osav.numeroor
                AND o1.code_societe = '$codeSociete'
            )
            {$this->selectCond->in('osav.numeroor',$numerosOR)}
            ORDER BY osav.numeroor DESC";

        $result = $this->connect->executeQuery($statement);
        $rows   = $this->connect->fetchResults($result);

        foreach ($rows as $row) {
            $data[] = $row["numeroor"];
        }

        return $data;
    }

    /**
     * Retourne les numéros de PO ou BC client validés associés à un numéro de commande fournisseur.
     *
     * Chaîne de recherche :
     *  1. PO (nlig_numcf) -> commandes directes liées (neg_lig / neg_ent)
     *  2. Commandes -> devis en position 'TR' dont le libellé (nent_libcde)
     *     référence le numéro de commande
     *  3. Devis -> dernière version soumise (bc_client_soumis_neg)
     *  4. Filtre sur les BC dont le statut est 'Validé'
     *
     * @param string $numCde      Numéro de PO recherché (comparé à nlig_numcf)
     * @param string $codeSociete Code société
     *
     * @return string[] Liste des numero_bc validés (vide si aucun résultat)
     */
    private function findAllValidatedPO(string $numCde, string $codeSociete): array
    {
        $statement = "WITH commandes_liees AS (
            SELECT DISTINCT l.nlig_numcde
            FROM {$this->dbIps}.neg_lig l
            INNER JOIN {$this->dbIps}.neg_ent e
                ON  l.nlig_soc    = e.nent_soc
                AND l.nlig_succ   = e.nent_succ
                AND l.nlig_numcde = e.nent_numcde
            WHERE   l.nlig_numcf = '$numCde'
                AND l.nlig_soc   = '$codeSociete'
                AND l.nlig_natcm = 'C'
                AND l.nlig_natop = 'DIR'
        ),
        devis AS (
            SELECT DISTINCT e.nent_numcde
            FROM {$this->dbIps}.neg_ent e
            INNER JOIN commandes_liees c
                ON e.nent_libcde LIKE '%' || CAST(c.nlig_numcde AS VARCHAR(11)) || '%'
            WHERE   e.nent_posl  = 'TR'
                AND e.nent_natop = 'DEV'
        ),
        derniere_version AS (
            SELECT b.numero_devis, MAX(b.numero_version) AS max_version
            FROM {$this->dbIrium}.bc_client_soumis_neg b
            INNER JOIN devis d
                ON b.numero_devis = CAST(d.nent_numcde AS VARCHAR(11))
            GROUP BY b.numero_devis
        )
        SELECT FIRST 50 DISTINCT t.numero_bc
        FROM {$this->dbIrium}.bc_client_soumis_neg t
        INNER JOIN derniere_version v
            ON  t.numero_devis   = v.numero_devis
            AND t.numero_version = v.max_version
        WHERE t.statut_bc like 'Valid%'
        ORDER BY t.numero_bc DESC";

        $result = $this->connect->executeQuery($statement);
        $rows   = $this->connect->fetchResults($result);

        $data = [];
        foreach ($rows as $row) {
            $data[] = $row["numero_bc"];
        }

        return $data;
    }

    /** 
     * Enregistrer du Bc soumis Magasin dans BD
     * 
     * @param BcSoumisMagasinDTO $bcSoumisMagasinDto
     * 
     * @return void
     */
    public function enregistrerBcSoumisMagasin(BcSoumisMagasinDTO $bcSoumisMagasinDto): void
    {
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            // Construire la requête d'insertion et l'exécuter
            $builder = new InsertQueryBuilder("{$this->dbIrium}.bc_soumis_magasin");
            $builder->setData([
                'numero_cde'            => $bcSoumisMagasinDto->numeroCommande,
                'statut'                => $bcSoumisMagasinDto->statut,
                'operateur'             => $bcSoumisMagasinDto->operateur,
                'date_heure_soumission' => $bcSoumisMagasinDto->dateHeureSoumission,
                'deposer_dw'            => $bcSoumisMagasinDto->deposerDw,
            ]);

            $result = $builder->build();

            $this->connect->executeQuery($result['sql'], $result['params']);
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }
}
