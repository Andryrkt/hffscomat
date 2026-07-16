<?php

namespace App\Model\magasin\CommANDe\Soumission;

use App\Dto\Magasin\Commande\Soumission\BcSoumisMagasinDTO;
use App\Model\Informix\InsertQueryBuilder;
use App\Model\Model;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Factory\magasin\Commande\Soumission\CommandeSoumissionFactory;

class CdeSoumissionModel extends Model
{

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

        $statement = "SELECT 
            fcde_numcde as num_cde,
            fcde_date AS date_cde,
            (
                SELECT TRIM(atab_lib)
                FROM {$this->dbIps}.agr_tab
                WHERE atab_code = fcde_typcde AND atab_nom  = 'TOP'
            ) AS type_cde,
            fcde_numfou AS num_frn,
            (
                SELECT TRIM(fbse_nomfou)
                FROM {$this->dbIps}.frn_bse, {$this->dbIps}.frn_fou
                WHERE  fbse_numfou = fcde_numfou
                AND    fbse_numfou = ffou_numfou
                AND    ffou_soc    = fcde_soc
            ) AS nom_frn,
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
            fcdl_constp AS cst,
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
                FROM {$this->dbIps}.art_sta
                WHERE asta_constp = fcdl_constp
                AND asta_refp   = fcdl_refp
                AND asta_per >= '$startDate'
                AND asta_per <= '$endDate'
            ) AS vte_der_mois,
            (
                SELECT NVL(SUM(asta_nblign), 0)
                FROM {$this->dbIps}.art_sta
                WHERE asta_constp = fcdl_constp
                AND asta_refp   = fcdl_refp
                AND asta_per >= '$startDate'
                AND asta_per <= '$endDate'
            ) AS nbr_vente,
            fcdl_pxach * (1 - (fcdl_txrem / 100)) AS prix_unit,
            fcdl_qte * fcdl_pxach * (1 - (fcdl_txrem / 100)) AS montant,
            fcdl_qte * abse_poids AS poids_total
        FROM {$this->dbIps}.frn_cdl, {$this->dbIps}.frn_cde, {$this->dbIps}.art_bse
        WHERE fcdl_numcde = fcde_numcde
            AND fcde_numcde = '$numCde'
            AND fcdl_constp = abse_constp
            AND fcdl_refp   = abse_refp
            AND fcde_soc    = fcdl_soc
            AND fcde_succ   = fcdl_succ
            AND fcde_soc    = '$codeSociete'
        ORDER BY fcdl_ref";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        dd((new CommandeSoumissionFactory)->hydrate($data, $userMail));
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
                'numero_cde'                  => $bcSoumisMagasinDto->numeroCommande,
                'statut'                    => $bcSoumisMagasinDto->statut,
                'operateur'                 => $bcSoumisMagasinDto->operateur,
                'date_heure_soumission'       => $bcSoumisMagasinDto->dateHeureSoumission,
                'deposer_dw'                 => $bcSoumisMagasinDto->deposerDw,
            ]);

            $result = $builder->build();

            $this->connect->executeQuery($result['sql'], $result['params']);
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }
}
