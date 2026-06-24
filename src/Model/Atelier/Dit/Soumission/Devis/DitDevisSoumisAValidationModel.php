<?php

namespace App\Model\Atelier\Dit\Soumission\Devis;

use App\Model\Informix\InsertQueryBuilder;
use App\Model\Informix\UpdateQueryBuilder;
use App\Model\Model;

class DitDevisSoumisAValidationModel extends Model
{
    /**
     * Recupération du dernier statut du devis selon le numeroDIT, code societe et le dernier numero version
     *
     * @param string $numDit
     * @param string $codeSociete
     * @return array
     */
    public function findStatutDevis(string $numDit, string $codeSociete): array
    {
        $statement = "SELECT FIRST 1 d.statut AS statut
                        FROM {$this->dbIrium}:Informix.devis_soumis_a_validation d
                        WHERE d.numerodit = '$numDit'
                        AND d.code_societe = '$codeSociete'
                        ORDER BY d.numeroversion DESC
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data;
    }

    public function findStatutDevisSelonNumDevis(string $numDevis, string $codeSociete): ?string
    {
        $statement = "SELECT FIRST 1 d.statut AS statut
                        FROM {$this->dbIrium}:Informix.devis_soumis_a_validation d
                        WHERE d.numerodevis = '$numDevis'
                        AND d.code_societe = '$codeSociete'
                        ORDER BY d.numeroversion DESC
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['statut'] ?? null;
    }

    public function recupNumeroDevis(string $numDit, string $codeSociete): ?string
    {
        $statement = "SELECT  seor_numor  as numDevis
                    from {$this->dbIps}:Informix.sav_eor
                    where seor_serv = 'DEV'
                    AND seor_soc = '$codeSociete'
                    AND seor_refdem = '$numDit'
                ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['numdevis'] ?? null;
    }

    public function recupNbPieceMagasin(?string $numDevis, string $codeSociete): int
    {
        if ($numDevis === null) return 0;

        $statement = " SELECT
                    COUNT(slor.slor_constp) AS nbr_sortie_magasin
                FROM {$this->dbIps}:Informix.sav_lor slor
                INNER JOIN sav_eor seor ON slor.slor_numor = seor.seor_numor
                WHERE seor.seor_numor = '$numDevis'
                AND slor.slor_typlig = 'P'
                AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
                AND seor.seor_soc = '$codeSociete'
                AND slor.slor_constp IN (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST')
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return (int)$data[0]['nbr_sortie_magasin'] ?? 0;
    }

    public function recupNbPieceMagasinDejaSoumi(string $numDevis, string $codeSociete): int
    {
        $statement = " SELECT first 1 nombrelignepiece as nbr_ligne_piece 
                        from {$this->dbIrium}:Informix.devis_soumis_a_validation 
                        where numerodevis ='$numDevis' 
                        and code_societe ='$codeSociete'
                        order by numeroversion desc
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return !empty($data) ? (int)$data[0]['nbr_ligne_piece'] : 0;
    }

    public function recupDevisValide(string $numDevis, string $codeSociete): int
    {
        if ($numDevis === null) return 0;

        $statement = " SELECT COUNT(numerodevis) as nbr_devis_valide  
            from {$this->dbIrium}:Informix.devis_soumis_a_validation 
            where numerodevis ='$numDevis' 
            and code_societe ='$codeSociete'
            and statut like 'Valid%' 
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return (int)$data[0]['nbr_devis_valide'] ?? 0;
    }

    public function getMontantItv(string $numDevis, string $codeSociete): float
    {
        $statement = " SELECT 
                    Sum(
                        CASE
                            WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                            WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_qterea
                        END 
                        * 
                        CASE
                            WHEN slor_typlig = 'P' THEN slor_pxnreel
                            WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                        END
                    ) as montant_itv
                FROM {$this->dbIps}:Informix.sav_eor, {$this->dbIps}:Informix.sav_lor, {$this->dbIps}:Informix.sav_itv
                WHERE seor_numor = slor_numor
                AND seor_serv = 'DEV'
                AND sitv_numor = slor_numor
                AND sitv_interv = slor_nogrp / 100
                AND seor_soc = '$codeSociete'
                AND slor_soc = seor_soc
                AND sitv_soc = seor_soc
                AND sitv_pos NOT IN ('FC', 'FE', 'CP', 'ST')
                AND seor_numor = ({$numDevis})
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['montant_itv'] ?? 0.0;
    }

    public function recupMontantItvIrium(string $numDevis, string $codeSociete): float
    {
        $statement = " SELECT first 1 montantitv as montant_irium 
                        from {$this->dbIrium}:Informix.devis_soumis_a_validation 
                        where numerodevis ='$numDevis' 
                        and code_societe ='$codeSociete'
                        order by numeroversion desc
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['montant_irium'] ?? 0.0;
    }

    public function estPremierSoumission(string $numDevis, string $codeSociete): bool
    {
        $statement = " SELECT Count(numeroversion) as numero_version 
                        from {$this->dbIrium}:Informix.devis_soumis_a_validation 
                        where numerodevis ='$numDevis' 
                        and code_societe ='$codeSociete'
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['numero_version'] === 0 ? true :  false;
    }


    public function recupInfoDit(string $numDit, string $numDevis, string $codeSociete): ?array
    {
        $statement = " SELECT  *
                        from {$this->dbIrium}:Informix.demande_intervention 
                        where numero_demande_dit ='$numDit'
                        --and numero_devis_rattache = '$numDevis'
                        and code_societe ='$codeSociete'
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0] ?? null;
    }

    public function recupNumeroClientIps(string $numDevis, string $codeSociete): ?string
    {
        $statement = " SELECT seor_numcli as numero_client
                        FROM {$this->dbIps}:Informix.sav_eor
                        WHERE seor_serv = 'DEV'
                        AND seor_soc = '$codeSociete'
                        AND seor_numor = '$numDevis'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['numero_client'] ?? null;
    }

    public function recupNumDitIps(string $numDevis, string $codeSociete): ?string
    {
        $statement = " SELECT trim(seor_refdem) as num_dit
                    FROM {$this->dbIps}:Informix.sav_eor 
                    where seor_serv='DEV'
                    AND seor_soc = '$codeSociete'
                    AND seor_numor = '$numDevis' 
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['num_dit'] ?? null;
    }

    public function recupServDebiteur(string $numDevis, string $codeSociete): ?string
    {
        $statement = " SELECT sitv_succdeb as serv_debiteur
                        FROM {$this->dbIps}:Informix.sav_itv sitv 
                        inner join {$this->dbIps}:Informix.sav_eor seor on sitv.sitv_numor = seor.seor_numor and seor.seor_serv ='DEV'
                        WHERE seor.seor_numor = '$numDevis'
                        AND seor.seor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['serv_debiteur'] ?? null;
    }

    /**
     * Methode pour recupérer l'information du devis pour enregistrer dans le base de donnée
     *
     * @param string $numDevis
     * @param string $codeSociete
     * @return array
     */
    public function recupDevisSoumisValidation(string $numDevis, string $codeSociete): array
    {
        $statement = " SELECT 
            sitv_succdeb as num_agence, 
            slor_numor as numero_devis, 
            sitv_datdeb, 
            trim(seor_refdem) as numero_dit, 
            sitv_interv as numero_itv, 
            trim(sitv_comment) as libelle_itv, 
            trim(sitv_natop) as nature_operation, 
            trim(seor_devise) as devise, 
            count(slor_constp) as nombre_ligne,
            Sum(
                CASE
                    WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_qterea
                END 
                * 
                CASE
                    WHEN slor_typlig = 'P' THEN slor_pxnreel
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                END
            ) as montant_itv,  
            Sum(
                CASE
                    WHEN slor_typlig = 'P' AND slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') 
                    THEN (nvl(slor_qterel, 0) + nvl(slor_qterea, 0) + nvl(slor_qteres, 0) + nvl(slor_qtewait, 0) - nvl(slor_qrec, 0))
                END 
                * 
                CASE
                    WHEN slor_typlig = 'P' THEN slor_pxnreel
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                END
            ) AS montant_piece, 
            Sum(
                CASE
                        WHEN slor_typlig = 'M' THEN slor_qterea
                    END 
                    *
                    CASE
                    WHEN slor_typlig = 'P' THEN slor_pxnreel
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                END
                ) AS montant_mo,  
            Sum(
                CASE
                        WHEN slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') THEN (
                            slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec
                        )
                    END 
                    *
                    CASE
                    WHEN slor_typlig = 'P' THEN slor_pxnreel
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                END
                ) AS montant_achats_locaux,  
            Sum(
                    CASE
                        WHEN slor_constp <> 'ZST'
                        AND slor_constp like 'Z%' THEN slor_qterea
                    END 
                    *
                    CASE
                    WHEN slor_typlig = 'P' THEN slor_pxnreel
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                END
                ) AS montant_divers,  
            Sum(
                    CASE
                        WHEN 
                            slor_typlig = 'P'
                            AND slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST')
                        THEN (nvl (slor_qterel, 0) + nvl (slor_qterea, 0) + nvl (slor_qteres, 0) + nvl (slor_qtewait, 0) - nvl (slor_qrec, 0))
                    END 
                    * 
                    CASE
                    WHEN slor_typlig = 'P' THEN slor_pxnreel
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                END
                ) AS montant_lubrifiant,  
            sum(
                    CASE
                        WHEN slor_constp = 'ZDI' AND slor_refp = 'FORFAIT' AND sitv_natop = 'VTE'
                        THEN nvl((slor_pxnreel * slor_qtewait), 0)
                    END
                ) AS montant_forfait,
            Sum(
                CASE
                    WHEN slor_constp<> 'ZDI' AND slor_refp <> 'FORFAIT' AND sitv_natop = 'VTE' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_qterea
                END 
                * 
                CASE
                    WHEN slor_typlig = 'P' THEN slor_pxnreel
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
                END
            ) as montant_vente,
            Sum(
                CASE
                    WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_qterea
                END 
                * 
                CASE
                    WHEN slor_typlig = 'P' THEN slor_pmp
                    WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pmp
                END
            ) as montant_revient
            
                FROM {$this->dbIps}:informix.sav_eor, {$this->dbIps}:informix.sav_lor, {$this->dbIps}:informix.sav_itv
                WHERE seor_numor = slor_numor
                AND seor_serv = 'DEV'
                AND sitv_numor = slor_numor
                AND sitv_interv = slor_nogrp / 100
                AND seor_soc = '$codeSociete'
                AND slor_soc = seor_soc
                AND sitv_soc = seor_soc
                AND sitv_pos NOT IN ('FC', 'FE', 'CP', 'ST')
                AND seor_numor = ({$numDevis})
            
                GROUP BY 1, 2, 3, 4, 5, 6, 7, 8
                ORDER BY slor_numor, sitv_interv
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupConstRefPremDev(string $numDevis, string $codeSociete): array
    {
        $statement = " SELECT   TRIM(slor_constp||'-'|| slor_refp) as contructeur
                        FROM {$this->dbIps}:informix.sav_lor
                        WHERE  slor_numor = '{$numDevis}' 
                        AND slor_nogrp = 100 
                        AND slor_soc = '$codeSociete'
                        ORDER BY slor_nolign ASC
                        LIMIT 1
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupNbrItvDev(string $numDevis, string $codeSociete): array
    {
        $statement = " SELECT DISTINCT COUNT( slor_nogrp) as itv
                        FROM {$this->dbIps}:informix.sav_lor 
                        WHERE slor_numor= '{$numDevis}' 
                        AND slor_nogrp != 100 
                        AND slor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupNumeroVersion(string $numDevis, string $codeSociete): int
    {
        $statement = " SELECT COALESCE(MAX(numeroversion)+1, 1) as numero_version 
                from {$this->dbIrium}:Informix.devis_soumis_a_validation 
                where numerodevis ='$numDevis'
                AND code_societe = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = array_column($this->convertirEnUtf8($this->connect->fetchResults($result)), 'numero_version');

        return $data[0] ?? 1;
    }

    public function enregistrerDevis(array $data): void
    {
        // Exécuter la requête d'insertion
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            foreach ($data as $donnees) {
                // Construire la requête d'insertion et l'exécuter
                $builder = new InsertQueryBuilder("{$this->dbIrium}:Informix.devis_soumis_a_validation");
                $builder->setData($donnees);
                $result = $builder->build();

                $this->connect->executeQuery($result['sql'], $result['params']);
            }
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }

    public function updateNumeroEtStatuDevis(string $numDit, string $codeSociete, array $data)
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

    public function constructeurPieceMagasin(string $numDevis, string $codeSociete)
    {
        $statement = " SELECT
                    CASE
                        WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) > 0
                        AND COUNT(CASE WHEN slor_constp IN (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') THEN 1 END) > 0
                        THEN TRIM('CP')
                    
                        WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) > 0
                        AND COUNT(CASE WHEN slor_constp IN (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') THEN 1 END) = 0
                        THEN TRIM('C')

                        WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) = 0
                        AND COUNT(CASE WHEN slor_constp IN (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') THEN 1 END) = 0
                        THEN TRIM('N')

                        WHEN COUNT(CASE WHEN slor_constp = 'CAT' THEN 1 END) = 0
                        AND COUNT(CASE WHEN slor_constp IN (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST') THEN 1 END) > 0
                        THEN TRIM('P')
                    END AS retour
                FROM {$this->dbIps}:Informix.sav_lor
                WHERE slor_numor = '$numDevis'
                AND slor_soc = '$codeSociete'
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function findDevisSoumiAvantForfait(string $numDevis, string $codeSociete): array
    {
        $statement = " SELECT * from {$this->dbIrium}:Informix.devis_soumis_a_validation 
            where numerodevis ='$numDevis' 
            and code_societe = '$codeSociete'
            and montantforfait is not null
            and numeroversion = (
                    select MAX(dsv2.numeroversion ) 
                    from {$this->dbIrium}:Informix.devis_soumis_a_validation dsv2 
                    where dsv2.numerodevis ='$numDevis' 
                    and dsv2.code_societe ='$codeSociete'
                    and dsv2.montantforfait is not null 
            ) 
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function findDevisSoumiAvantMaxForfait(string $numDevis, string $codeSociete): array
    {
        $statement = " SELECT * from {$this->dbIrium}:Informix.devis_soumis_a_validation 
            where numerodevis ='$numDevis' 
            and code_societe = '$codeSociete'
            and montantforfait is not null
            and numeroversion = (
                    (select MAX(dsv2.numeroversion ) 
                    from {$this->dbIrium}:Informix.devis_soumis_a_validation dsv2 
                    where dsv2.numerodevis ='$numDevis' 
                    and dsv2.code_societe ='$codeSociete'
                    and dsv2.montantforfait is not null ) - 1
            ) 
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function findDevisSoumiAvant(string $numDevis, string $codeSociete): array
    {
        $statement = " SELECT * from {$this->dbIrium}:Informix.devis_soumis_a_validation 
            where numerodevis ='$numDevis' 
            and code_societe = '$codeSociete'
            and montantitv  <> 0.0
            and numeroversion = (
                    (select MAX(dsv2.numeroversion ) 
                    from {$this->dbIrium}:Informix.devis_soumis_a_validation dsv2 
                    where dsv2.numerodevis ='$numDevis' 
                    and dsv2.code_societe ='$codeSociete'
                    and montantitv  <> 0.0 )
            ) 
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function findDevisSoumiAvantMax(string $numDevis, string $codeSociete): array
    {
        $statement = " SELECT * from {$this->dbIrium}:Informix.devis_soumis_a_validation 
            where numerodevis ='$numDevis' 
            and code_societe = '$codeSociete'
            and montantitv  <> 0.0
            and numeroversion = (
                    (select MAX(dsv2.numeroversion ) 
                    from {$this->dbIrium}:Informix.devis_soumis_a_validation dsv2 
                    where dsv2.numerodevis ='$numDevis' 
                    and dsv2.code_societe ='$codeSociete'
                    and montantitv  <> 0.0 ) - 1
            ) 
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupNbAchatLocaux(string $numDevis, string $codeSociete)
    {
        $statement = " SELECT
            count(slor.slor_constp) as nbr_achat_locaux 
            from sav_lor slor
            INNER JOIN sav_eor seor ON slor.slor_numor = seor.seor_numor
            where seor.seor_numor = '$numDevis'
            and seor.seor_soc = '$codeSociete'
            and slor.slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST')
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupNbPieceMagasin2(string $numDevis, string $codeSociete)
    {
        $statement = " SELECT
                    COUNT(slor.slor_constp) AS nbr_sortie_magasin
                FROM sav_lor slor
                INNER JOIN sav_eor seor ON slor.slor_numor = seor.seor_numor
                WHERE seor.seor_numor = '$numDevis'
                AND slor.slor_typlig = 'P'
                AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
                AND seor.seor_soc = '$codeSociete'
                AND slor.slor_constp IN (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST')
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupInfoPieceClient(string $numDevis, string $codeSociete)
    {
        $statement = " SELECT 
                        trim(slor_refp) as ref_piece,
                        trim(slor_constp) as constructeur,
                        slor_numcli as num_client,
                        slor_numor as num_devis
                        FROM sav_lor
                        WHERE slor_numor = '$numDevis'
                        AND slor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    /**
     * Methode pour recupérer l'evolution de prix de chaque pièce
     *
     * @param array $infoPieceClient
     * @return void
     */
    public function recupInfoPourChaquePiece(array $infoPieceClient, string $codeSociete)
    {
        $statement = " SELECT FIRST 3 
                    trim(slor_constp) as CST, 
                    trim(slor_refp) as RefPiece, 
                    slor_datel as dateLigne,
                    slor_pxnreel as prixVente,
                    slor_typlig as type_ligne,
                    seor_serv 
                    FROM sav_lor
                    inner join sav_eor 
                    on seor_soc= slor_soc and seor_succ = slor_succ and seor_numor = slor_numor and slor_soc ='$codeSociete'
                    WHERE slor_refp = '{$infoPieceClient['ref_piece']}'
                    and slor_constp in (select distinct abse_constp from art_bse abse where abse.abse_codg = 'ST')
                    AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL')
                    and seor_serv = 'SAV'
                    and slor_pos in('CP','FC') 
                    and slor_numcli = '{$infoPieceClient['num_client']}'
                    ORDER BY slor_datel DESC
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }
}
