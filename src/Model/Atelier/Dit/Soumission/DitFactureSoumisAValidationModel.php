<?php

namespace App\Model\Atelier\Dit\Soumission;

use App\Model\Informix\InsertQueryBuilder;
use App\Model\Informix\UpdateQueryBuilder;
use App\Model\Model;
use App\Model\Traits\ConversionModel;
use App\Service\GlobalVariablesService;

class DitFactureSoumisAValidationModel extends Model
{
    use ConversionModel;

    /**
     * Récupération du numero OR 
     * -------------------------------
     * selon la numéro DIT donnée et la code société, 
     * on récupère le numéro OR dans la base de donnée sav_eor
     *
     * @param string $numDit
     * @param string $codeSociete
     * @return string|null
     */
    public function recupNumeroOr(string $numDit, string $codeSociete): ?string
    {
        $statement = " SELECT FIRST 1 
                seor_numor as numOr
                from {$this->dbIps}:Informix.sav_eor
                where seor_refdem like '%$numDit%'
                AND seor_serv = 'SAV'
                AND seor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['numor'] ?? null;
    }

    /**
     * Récupération du numero soumission et incrementation de cette numéro obtenu
     * ---------------------------------------------------------------------------
     * selon le numero OR et code société donnée on recupère le numéro de soumission 
     * dans la table ri_soumis_a_validation puis on l'increment
     *
     * @param string $numOr
     * @param string $codeSociete
     * @return integer
     */
    public function recupNumeroSoumission(string $numOr, string $codeSociete): int
    {
        $statement = "SELECT COALESCE(MAX(numero_soumission)+1, 1) AS numSoumissionEncours
                FROM {$this->dbIrium}:Informix.facture_soumis_a_validation
                WHERE numero_or = '$numOr' and code_societe = '$codeSociete'";

        $result = $this->connect->executeQuery($statement);

        $data = array_column($this->convertirEnUtf8($this->connect->fetchResults($result)), 'numSoumissionEncours');

        return $data[0] ?? 1;
    }

    /**
     * Recupère l'agence et service debiteur du DIT (80-INF)
     *
     * @param string $numDit
     * @param string $codeSociete
     * @return array
     */
    public function recupAgServDebAndIntExtDit(string $numDit, string $codeSociete): array
    {
        $statement = " SELECT agence_service_debiteur as agServDeb,
                            internet_externe as int_ext,
                            migration as migration
                    from {$this->dbIrium}:Informix.demande_intervention 
                    Where numero_demande_dit = '$numDit'
                    and code_societe = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0] ?? [];
    }

    /**
     * Recupération des information sur le facture
     *
     * @param string $numeroOr
     * @param string $numFac
     * @param string $codeSociete
     * @return array
     */
    public function recupInfoFact(string $numeroOr, string $numFac, string $codeSociete): array
    {
        $statement = "SELECT
                    numeroFac,
                    numeroOr,
                    typeOr,
                    numeroItv,
                    SUM(pxnreel * qterea) AS montantFactureItv,
                    agenceDebiteur,
                    serviceDebiteur,
                    libelleItv,
                    montant,
                    SUM(
                        CASE
                            WHEN typlig = 'P' THEN (qterel + qterea + qteres + qtewait - qrec)
                            WHEN typlig IN ('F', 'M', 'U', 'C') THEN qterea
                        END * pxnreel
                    ) AS montantItv,
                    TRUNC(SUM(
                        CASE 
                            WHEN typlig = 'P' THEN (qterel + qterea + qteres + qtewait - qrec)
                            WHEN typlig IN ('F', 'M', 'U', 'C') THEN qterea 
                        END
                    )) AS quantiteDemander,
                    TRUNC(SUM(qteres)) AS quantiteReserver,
                    TRUNC(SUM(
                        CASE 
                            WHEN typlig IN ('F', 'M', 'U', 'C') THEN qterea 
                            ELSE qteliv 
                        END
                    )) AS quantiteLivree,
                    TRUNC(SUM(qterel)) AS quantiteReliquat,
                    CASE 
                        WHEN TRUNC(SUM(
                            CASE WHEN typlig IN ('F','M','U','C') THEN qterea ELSE qteliv END
                        )) = 0
                        THEN 'A livrer'
                        WHEN TRUNC(SUM(
                            CASE WHEN typlig IN ('F','M','U','C') THEN qterea ELSE qteliv END
                        )) = TRUNC(SUM(
                            CASE WHEN typlig = 'P' THEN (qterel + qterea + qteres + qtewait - qrec)
                                WHEN typlig IN ('F','M','U','C') THEN qterea END
                        ))
                        THEN 'Livré'
                        WHEN TRUNC(SUM(
                            CASE WHEN typlig IN ('F','M','U','C') THEN qterea ELSE qteliv END
                        )) < TRUNC(SUM(
                            CASE WHEN typlig = 'P' THEN (qterel + qterea + qteres + qtewait - qrec)
                                WHEN typlig IN ('F','M','U','C') THEN qterea END
                        ))
                        THEN 'Livré partiellement'
                        ELSE ''
                    END AS statut_itv
                FROM (
                    SELECT
                        slor_numfac       AS numeroFac,
                        slor_numor        AS numeroOr,
                        slor_typeor       AS typeOr,
                        ROUND(slor_nogrp / 100) AS numeroItv,
                        slor_pxnreel      AS pxnreel,
                        slor_qterea       AS qterea,
                        slor_succdeb      AS agenceDebiteur,
                        slor_servdeb      AS serviceDebiteur,
                        TRIM(sitv_comment) AS libelleItv,
                        slor_typlig       AS typlig,
                        slor_qterel       AS qterel,
                        slor_qteres       AS qteres,
                        slor_qtewait      AS qtewait,
                        slor_qrec         AS qrec,
                        sliv_qteliv       AS qteliv,
                        osv_or.montantitv  as montant
                    FROM ips_test:Informix.sav_lor
                    JOIN ips_test:Informix.sav_itv
                        ON sitv_numor  = slor_numor
                        AND sitv_interv = slor_nogrp / 100
                    LEFT JOIN ips_test:Informix.sav_liv
                        ON sliv_soc   = slor_soc
                        AND sliv_succ  = slor_succ
                        AND sliv_numor = slor_numor
                        AND slor_nolign = sliv_nolign
                    LEFT JOIN ir_prod108_test:informix.ors_soumis_a_validation osv_or
                        ON osv_or.numeroor     = slor_numor
                        AND osv_or.numeroitv    = slor_nogrp / 100
                        AND osv_or.numeroversion = (
                            SELECT MAX(osv2.numeroversion)
                            FROM ir_prod108_test:informix.ors_soumis_a_validation osv2
                            WHERE osv2.id = osv_or.id
                        )
                        AND osv_or.statut LIKE 'Valid%'
                    WHERE slor_soc    = '$codeSociete'
                    AND slor_numor    = '$numeroOr'
                    AND slor_numfac   = '$numFac'
                ) sub
                GROUP BY
                    numeroFac,
                    numeroOr,
                    typeOr,
                    numeroItv,
                    agenceDebiteur,
                    serviceDebiteur,
                    libelleItv,
                    montant
                ORDER BY
                    numeroItv;
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }


    public function recupNumeroItvDejaSoumi(string $numOr, string $codeSociete): array
    {
        $statement = " SELECT distinct numeroitv  
                        from {$this->dbIrium}:Informix.ri_soumis_a_validation 
                        where numero_or ='$numOr' 
                        and code_societe ='$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'numeroitv') ?? [];
    }

    public function recupOrSoumisValidation(string $numOr, string $numFact, string $codeSociete): array
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

        from {$this->dbIps}:Informix.sav_eor, {$this->dbIps}:Informix.sav_lor, {$this->dbIps}:Informix.sav_itv
        WHERE
            seor_numor = slor_numor
            AND slor_soc = '$codeSociete'
            AND seor_serv <> 'DEV'
            AND sitv_numor = slor_numor
            AND sitv_interv = slor_nogrp / 100


        AND seor_numor = '$numOr'
        AND slor_numfac = '$numFact'

        group by 1, 2, 3, 4, 5
        order by slor_numor, sitv_interv
    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupererNumdevis(string $numOr, string $codeSociete): string
    {
        $statement = "SELECT seor_numdev 
                from {$this->dbIps}:Informix.sav_eor
                where seor_numor = '$numOr' and seor_soc = '$codeSociete'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data)[0]['seor_numdev'] ?? 0;
    }

    /**
     * Recupération de nombre d'intervention dans la table ors_soumis_a_validation
     *
     * @param string $numOr
     * @param string $codeSociete
     * @return integer
     */
    public function recupNbrItvDansOR(string $numOr, string $codeSociete): int
    {
        $statement = "SELECT count(numeroitv) as nbr_itv
                    from {$this->dbIrium}:Informix.ors_soumis_a_validation 
                    where numeroor ='$numOr'
                    AND code_societe ='$codeSociete'
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'nbr_itv')[0] ?? 0;
    }

    public function recupStatutOr(string $numOr, int $numItv, string $codeSociete): ?string
    {
        $statement = " SELECT first 1  statut  as statut
                    from {$this->dbIrium}:Informix.ors_soumis_a_validation 
                    where numeroor = '$numOr' 
                    and code_societe='$codeSociete' 
                    and numeroitv =$numItv 
                    order by numeroversion desc
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'statut')[0] ?? null;
    }


    public function recupInfoOrSelonNumeroOr(string $numOr, string $codeSociete): array
    {
        $statement = " SELECT * 
                from {$this->dbIrium}:Informix.ors_soumis_a_validation 
                where numeroor = '$numOr' 
                    and code_societe='$codeSociete' 
                ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupEtatOr($numOr, string $codeSociete)
    {
        $statement = " SELECT 
                CASE 
                    WHEN COUNT(*) > 0 THEN 'PF'
                    ELSE 'CF'
                END AS etat_facturation_or
            FROM sav_lor
            WHERE slor_numor = '$numOr' 
            AND slor_soc = '$codeSociete'
            AND NVL(slor_numfac, 0) = 0 ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'etat_facturation_or');
    }

    public function updateEtatFacture(string $numDit, string $codeSociete, array $data)
    {

        $updateBuilder = new UpdateQueryBuilder("{$this->dbIrium}:Informix.demande_intervention");

        /// Définir les données à mettre à jour
        $updateBuilder->setData($data);

        // Ajouter les conditions WHERE
        $updateBuilder->where('numero_demande_dit', $numDit);
        $updateBuilder->where('code_societe',  $codeSociete);

        // Changer l'opérateur des conditions (optionnel)
        // $updateBuilder->setConditionOperator('AND');
        // Construire et exécuter la requête
        try {
            $result = $updateBuilder->build();
            $this->connect->connect();
            try {
                $this->connect->executeQuery($result['sql'], $result['params']);
            } finally {
                $this->connect->close();
            }
        } catch (\Exception $e) {
            // Vous pouvez logger l'erreur ici
            throw $e;
        }
    }

    public function enregistrerFacture(array $data): void
    {
        // Exécuter la requête d'insertion
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            foreach ($data as $donnees) {
                // Construire la requête d'insertion et l'exécuter
                $builder = new InsertQueryBuilder("{$this->dbIrium}:Informix.facture_soumis_a_validation");
                $builder->setData($donnees);
                $result = $builder->build();

                $this->connect->executeQuery($result['sql'], $result['params']);
            }
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }

    // ===========================================================

    // public function recupNombreFacture(string $numOr, string $numFact, string $codeSociete)
    // {
    //     $statement = "SELECT count(slor_numfac) as nbFact 
    //                 FROM sav_lor where slor_numor = '$numOr'
    //                 AND slor_numfac = '$numFact'
    //                 AND slor_soc = '$codeSociete'
    //                 ";

    //     $result = $this->connect->executeQuery($statement);

    //     $data = $this->connect->fetchResults($result);

    //     return $this->convertirEnUtf8($data);
    // }

    // public function recupNumeroItv(string $numOr, string $numFact)
    // {
    //     $statement = "SELECT
    //                 slor_nogrp / 100 AS numeroItv
    //             FROM
    //                 sav_lor
    //             JOIN
    //                 sav_itv ON sitv_numor = slor_numor
    //                         AND sitv_interv = slor_nogrp / 100
    //             WHERE
    //                 --sitv_servcrt IN ('ATE', 'FOR', 'GAR', 'MAN', 'CSP', 'MAS', 'LR6', 'LST')
    //                  slor_numor = '" . $numOr . "'
    //                 AND slor_numfac = '" . $numFact . "'
    //             GROUP BY
    //             numeroOr, numeroItv
    //             ORDER BY
    //                 numeroItv
    //     ";
    //     $result = $this->connect->executeQuery($statement);

    //     $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

    //     return array_column($data, 'numeroItv');
    // }



    // public function recuperationStatutItv(string $numOr, string $numItv, string $codeSociete)
    // {
    //     $statement = " SELECT 
    //                 TRIM(seor_refdem) AS referenceDIT,
    //                 seor_numor AS numeroOr,
    //                 TRUNC(SUM(
    //                     CASE 
    //                         WHEN slor_typlig = 'P' 
    //                         THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) 
    //                         WHEN slor_typlig IN ('F', 'M', 'U', 'C') 
    //                         THEN slor_qterea 
    //                     END
    //                 )) AS quantiteDemander,
    //                 TRUNC(SUM(slor_qteres)) AS quantiteReserver,
    //                 TRUNC(SUM(
    //                     CASE 
    //                         WHEN slor_typlig IN ('F', 'M', 'U', 'C') 
    //                         THEN slor_qterea 
    //                         ELSE sliv_qteliv 
    //                     END
    //                 )) AS quantiteLivree,
    //                 TRUNC(SUM(slor_qterel)) AS quantiteReliquat
    //             FROM sav_lor 
    //             INNER JOIN sav_eor 
    //                 ON seor_soc = slor_soc 
    //                 AND seor_succ = slor_succ 
    //                 AND seor_numor = slor_numor
    //             LEFT JOIN sav_liv 
    //                 ON sliv_soc = slor_soc 
    //                 AND sliv_succ = slor_succ 
    //                 AND sliv_numor = seor_numor 
    //                 AND slor_nolign = sliv_nolign
    //             WHERE slor_soc = '$codeSociete'
    //                 AND seor_serv = 'SAV'
    //                 --AND slor_constp IN (" . GlobalVariablesService::get('tous') . ")
    //                 AND slor_numor = '" . $numOr . "'
    //                 AND TRUNC(slor_nogrp / 100) IN (" . $numItv . ")
    //             GROUP BY 
    //                 1,2
    //     ";

    //     $result = $this->connect->executeQuery($statement);

    //     $data = $this->connect->fetchResults($result);

    //     return $this->convertirEnUtf8($data);
    // }

    // public function orStatutEstValide(string $numOr, string $numItv)
    // {
    //     $sql = " SELECT 
    //             case when statut = 'Validé' then 'Validé'else 'Non validé' end as Statut
    //             from ors_soumis_a_validation
    //             where numeroOR = '$numOr' 
    //             and numeroItv = '$numItv' 
    //             and numeroVersion = (select max(numeroversion) from ors_soumis_a_validation where numeroOR = '$numOr' and numeroItv = '$numItv')
    //     ";

    //     $exec = $this->connexion->query($sql);
    //     $tab = [];
    //     while ($result = odbc_fetch_array($exec)) {
    //         $tab[] = $result;
    //     }
    //     return array_column($tab, 'Statut');
    // }

    // public function findNbrFact(string $numFac)
    // {
    //     $statement = "SELECT
    // 		cout(numeroFact)
    // 		from 
    // 		where numeroFact = '$numFac'
    // 	";

    //     $result = $this->connect->executeQuery($statement);

    //     $nbrFact = $this->connect->fetchScalarResults($result);

    //     return $nbrFact ? $nbrFact : 0;
    // }

    // public function findNumItvFacStatut(string $numOr)
    // {
    //     $statement = "SELECT
    // 		numeroItv,
    // 		numeroFact,
    // 		statut
    // 		from 
    // 		where numeroOR = '$numOr'
    // 	";

    //     $result = $this->connect->executeQuery($statement);

    //     $data = $this->connect->fetchScalarResults($result);

    //     return $this->convertirEnUtf8($data);
    // }
}
