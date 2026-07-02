<?php


namespace App\Model\Atelier\Dit;


use App\Dto\Atelier\Dit\DitDto;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Mapper\Atelier\Dit\DitMapper;
use App\Model\Informix\InsertQueryBuilder;
use App\Model\Informix\UpdateQueryBuilder;
use App\Model\Model;
use DateTime;

class DitModel extends Model
{
    public function findAll($matricule = '0',  $numParc = '0', $numSerie = '0')
    {
        if ($matricule === '' || $matricule === '0' || $matricule === null) {
            $conditionNummat = "";
        } else {
            $conditionNummat = "and mmat_nummat = '" . $matricule . "'";
        }


        if ($numParc === '' || $numParc === '0' || $numParc === null) {
            $conditionNumParc = "";
        } else {
            $conditionNumParc = "and mmat_recalph = '" . $numParc . "'";
        }

        if ($numSerie === '' || $numSerie === '0' || $numSerie === null) {
            $conditionNumSerie = "";
        } else {
            $conditionNumSerie = "and TRIM(mmat_numserie) = '" . $numSerie . "'";
        }




        $statement = "SELECT

        mmat_marqmat as constructeur,
        trim(mmat_desi) as designation,
        trim(mmat_typmat) as modele,
        trim(mmat_numparc) as casier_emetteur,
        mmat_nummat as num_matricule,
        trim(mmat_numserie) as num_serie,
        trim(mmat_recalph) as num_parc,

        (select mhir_compteur from mat_hir a where a.mhir_nummat = mmat_nummat and a.mhir_daterel = (select max(b.mhir_daterel) from mat_hir b where b.mhir_nummat = a.mhir_nummat)) as heure,
        (select mhir_cumcomp from mat_hir a where a.mhir_nummat = mmat_nummat and a.mhir_daterel = (select max(b.mhir_daterel) from mat_hir b where b.mhir_nummat = a.mhir_nummat)) as km,
        (select nvl(sum(mofi_mt),0) from mat_ofi where mofi_classe = 30 and mofi_ssclasse in (10,11,12,13,14,16,17,18,19) and mofi_numbil = mbil_numbil and mofi_typmt = 'R') as Prix_achat,
        (select nvl(sum(mofi_mt),0) from mat_ofi where mofi_classe = 30 and mofi_ssclasse = 15 and mofi_numbil = mbil_numbil and mofi_typmt = 'R') as Amortissement,

        (select nvl(sum(mofi_mt),0) from mat_ofi where mofi_classe = 10 and mofi_ssclasse in (100,21,22,23) and mofi_numbil = mbil_numbil and mofi_typmt = 'R') as ChiffreAffaires,
        (select nvl(sum(mofi_mt),0) from mat_ofi where mofi_classe = 40 and mofi_ssclasse in (100,110) and mofi_numbil = mbil_numbil and mofi_typmt = 'R') as ChargeLocative,
        (select nvl(sum(mofi_mt),0) from mat_ofi where mofi_classe = 40 and mofi_ssclasse in (21,22,23) and mofi_numbil = mbil_numbil and mofi_typmt = 'R') as ChargeEntretien

      FROM {$this->dbIps}:Informix.MAT_MAT
      LEFT JOIN {$this->dbIps}:Informix.mat_bil on mbil_nummat = mmat_nummat 
      --and mbil_dateclot <= '01/01/1900' 
      --and mbil_dateclot = '12/31/1899'
      WHERE MMAT_ETSTOCK in ('ST','AT', '--')
      AND MMAT_AFFECT <> 'CAS'
      " . $conditionNummat . "
      " . $conditionNumParc . "
      " . $conditionNumSerie . "
      ";


        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function getNumDitByNumOr($numOr, $codeSociete)
    {
        $statement = "SELECT DISTINCT
                numero_demande_dit
            FROM {$this->dbIrium}:Informix.demande_intervention
            WHERE numero_or = '$numOr' 
            AND code_societe = '$codeSociete'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        $data = $this->convertirEnUtf8($data);
        return $data[0]['numero_demande_dit'] ?? null;
    }

    /**
     * Recupe tous les numéro et nom des clients
     *
     * @return array
     */
    public function getAllClients(): array
    {
        $statement = "SELECT DISTINCT cbse_numcli as num_client, 
                            cbse_nomcli as nom_client
                        from {$this->dbIps}:Informix.cli_bse
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    

    /**
     * Methode pour enregistrer les données du formulaire Dit
     *  dans la table demande_intervention
     *
     * @param OrSoumissionDto $dto
     * @return void
     */
    public function enregistrerDit(OrSoumissionDto $dto, array $ors): void

    {


        // Convertir le DTO en tableau associatif pour l'insertion
        $donnees = DitMapper::toArrayDit($dto, $ors);

        // Construire la requête d'insertion et l'exécuter
        $builder = new InsertQueryBuilder("{$this->dbIrium}:Informix.demande_intervention");
        $builder->setData($donnees);
        $result = $builder->build();

        // Exécuter la requête d'insertion
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            $this->connect->executeQuery($result['sql'], $result['params']);
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }
    /**
     * MOdification du numeroOr dans dans la 
     * table demande_intervention
     *
     * @param OrSoummissionDto $dto
     * @param string statut
     * @return void
     */

    /**
     * MOdification du statut demande d'intervention dans la 
     * table demande_intervention
     *
     * @param string statut
     * @return void
     */
    public function updateStatut($numDit, $codeSociete, $statut)
    {
        $donnees = DitMapper::toArrayUpdateDit($statut);

        $updateBuilder = new UpdateQueryBuilder("{$this->dbIrium}:Informix.demande_intervention");

        // Définir les données à mettre à jour
        $updateBuilder->setData($donnees);

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
    /**
     * Modification pour l'annulation du demande d'intervention dans la 
     * table demande_intervention
     *
     * @return void
     */
    public function updateStatutDateAnnuler($numDit, $codeSociete)
    {
        $donnees = DitMapper::toArrayUpdateDitForAnnuler();

        $updateBuilder = new UpdateQueryBuilder("{$this->dbIrium}:Informix.demande_intervention");

        // // Définir les données à mettre à jour
        $updateBuilder->setData($donnees);

        // // Ajouter les conditions WHERE
        $updateBuilder->where('numero_demande_dit', $numDit);
        $updateBuilder->where('code_societe',   $codeSociete);

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

    public function updateNumeroOr(OrSoumissionDto $dto, string $statut)
    {
        $donnees = DitMapper::toArrayUpdateDitNumeroOr($statut, $dto->numeroOr);

        $updateBuilder = new UpdateQueryBuilder("{$this->dbIrium}:Informix.demande_intervention");

        // // Définir les données à mettre à jour
        $updateBuilder->setData($donnees);

        // // Ajouter les conditions WHERE
        $updateBuilder->where('numero_demande_dit', $dto->numeroDit);
        $updateBuilder->where('code_societe',   $dto->codeSociete);

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
    public function recupAgenceServiceDebiteur($numOr, string $codeSociete)
    {
        $statement = " SELECT 
          slor_succdeb || '-' || slor_servdeb AS agServDebiteur
          FROM sav_lor
          WHERE slor_numor = '$numOr' AND slor_soc = '$codeSociete'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'agservdebiteur');
    }
    public function recupInformationsDit(string $numDit, string $codeSociete)
    {

        $statement = "  SELECT FIRST 1
        d0_.*,
          TRIM(m.mmat_recalph) AS numero_parc,
        TRIM(m.mmat_numserie) AS numero_serie,
        TRIM(m.mmat_numparc) AS casier
    FROM {$this->dbIrium}:Informix.demande_intervention d0_
    LEFT JOIN {$this->dbIps}:informix.mat_mat m
        ON d0_.id_materiel = m.mmat_nummat
    WHERE d0_.numero_demande_dit = '$numDit'
      AND d0_.code_societe = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));


        return $data[0] ?? [];
    }


    public function recupererNumdevis($numOr, $codeSociete)
    {
        $statement = "SELECT seor_numdev 
                from sav_eor
                where seor_numor = '$numOr' and seor_soc = '$codeSociete'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    /**
     * Constraint soumission 
     */

    public function recupeConstraintSoumission(string $numDit, string $codeSociete)
    {
        $statement = "SELECT d0_.internet_externe as client , 
                            s2_.description as statut,
                            d0_.numero_or as numero_or
                from {$this->dbIrium}:Informix.demande_intervention d0_
                LEFT JOIN {$this->dbIrium}:informix.statut_demande s2_
                    ON d0_.id_statut_demande = s2_.ID_Statut_Demande
                AND s2_.code_application = 'DIT'
                where d0_.code_societe  = '$codeSociete'
                and d0_.numero_demande_dit  = '$numDit'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data;
    }



    /**
     * recupère l'ID du categorie de demande d'intervention
     *
     * @param string $numDit
     * @param string $codeSociete
     * @return string|null
     */
    public function findIdCategorieByNumeroDit(string $numDit, string $codeSociete): ?string
    {
        $statement = " SELECT FIRST 1 categorie_demande
            FROM {$this->dbIrium}:Informix.demande_intervention
            WHERE numero_demande_dit = '$numDit'
            AND code_societe = '$codeSociete'
    ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        return $data[0]['categorie_demande'] ?? null;
    }

    public function historiqueMateriel(int $idMateriel)
    {
        $statement = "SELECT
              TRIM(seor_succ) AS codeAgence,
              TRIM(seor_servcrt) AS codeService,
              sitv_datdeb AS dateDebut,
              sitv_numor AS numeroOr, 
              sitv_interv AS numeroIntervention, 
              TRIM(sitv_comment) AS commentaire,
              sitv_pos AS pos,
              SUM(
                slor_pxnreel * (
                CASE 
                  WHEN slor_typlig = 'P' 
                    THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) 
                  WHEN slor_typlig IN ('F','M','U','C') 
                    THEN slor_qterea 
                END)
              ) AS somme
            FROM {$this->dbIps}:Informix.sav_eor, 
                    {$this->dbIps}:Informix.sav_lor, 
                    {$this->dbIps}:Informix.sav_itv, 
                    {$this->dbIps}:Informix.agr_succ, 
                    {$this->dbIps}:Informix.agr_tab ser, 
                    {$this->dbIps}:Informix.mat_mat, 
                    {$this->dbIps}:Informix.agr_tab ope, 
                    OUTER {$this->dbIps}:Informix.agr_tab sec
            WHERE seor_numor = slor_numor
              AND seor_serv <> 'DEV'
              AND sitv_numor = slor_numor
              AND sitv_interv = slor_nogrp/100
              AND (seor_succ = asuc_num)
              AND (seor_servcrt = ser.atab_code AND ser.atab_nom = 'SER')
              AND (sitv_typitv = sec.atab_code AND sec.atab_nom = 'TYI')
              AND (seor_ope = ope.atab_code AND ope.atab_nom = 'OPE')
              AND sitv_pos IN ('FC','FE','CP','ST', 'EC')
              AND (seor_nummat = mmat_nummat)
              AND mmat_nummat ='$idMateriel'
            GROUP BY 1,2,3,4,5,6,7
            ORDER BY sitv_pos DESC, sitv_datdeb DESC, sitv_numor, sitv_interv
    ";

        $result = $this->connect->executeQuery($statement);


        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    /**
     * Récupération des id materiel
     * les Id materiel recupérer ne doit pas générer d'historique
     *
     * @return array
     */
    public function getNumeroMatriculePasMateriel(): array
    {
        $statement = "SELECT mmat_nummat as numero_matricule 
              from informix.mat_mat 
              where mmat_reffou in ('IMMODIV','PRESTDIV') OR (mmat_recalph = 'EQPABS')
              or mmat_nummat = '7711'
              order by mmat_nummat
              ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'numero_matricule');
    }


    public function enregistrementDit(array $data)
    {
        // Construire la requête d'insertion et l'exécuter
        $builder = new InsertQueryBuilder("{$this->dbIrium}:Informix.demande_intervention");
        $builder->setData($data);
        $result = $builder->build();

        // Exécuter la requête d'insertion
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            $this->connect->executeQuery($result['sql'], $result['params']);
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }

    public function updateDitDW(array $data, DitDto $dto)
    {
        $updateBuilder = new UpdateQueryBuilder("{$this->dbIrium}:Informix.demande_intervention");

        // Définir les données à mettre à jour
        $updateBuilder->setData($data);

        // Ajouter les conditions WHERE
        $updateBuilder->where('numero_demande_dit', $dto->numeroDemandeIntervention);
        $updateBuilder->where('code_societe',  $dto->codeSociete);

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

    public function recuperationSectionValidation()
    {

        $statement = "SELECT trim(Atab_Code) AS ATAB_CODE,
                  trim(Atab_lib)  AS ATAB_LIB
                  FROM AGR_TAB
                  WHERE Atab_nom = 'TYI'
      ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupereCommandeOr(?string $numero_or, string $codeSociete)
    {
        if ($numero_or === null) return [];

        $statement = "SELECT
        slor_numcf as slor_numcf,
        fcde_date as fcde_date, 
        slor_typcf as slor_typcf,
        fcde_posc as fcde_posc,
        fcde_posl as fcde_posl
      from sav_lor
      inner join frn_cde on frn_cde.fcde_numcde = slor_numcf
      where
      slor_soc = '$codeSociete'  
      --and slor_succ = '01'  
      and slor_constp not like '%Z'  
      and slor_numor in (select seor_numor from sav_eor where seor_serv = 'SAV')
      and slor_numor = '$numero_or'
      group by 1,2,3,4,5";


        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    /**
     * Récupération numero des DITs a annuler
     * 
     *
     * @return array
     */
    public function recupDitAAnnuler()
    {
        $now = (new DateTime())->format('Y-m-d H:i:s');
        $yesterday = (new DateTime('-1 day'))->format('Y-m-d H:i:s');

        $statement = " SELECT numero_demande_dit,a_annuler
                FROM {$this->dbIrium}:Informix.demande_intervention
                WHERE a_annuler = '1' 
                   AND date_annulation BETWEEN '$yesterday' AND '$now'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return  $data;
    }

    public function recupQuantiteQuatreStatutOr(?string $numOr, string $codeSociete): array
    {
        if ($numOr === null) return [];

        $statement = "SELECT 
            trim(seor_refdem) as referenceDIT,
            seor_numor as numeroOr,
            sum(CASE WHEN slor_typlig = 'P' THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea END) AS quantiteDemander,
            sum(slor_qteres) as quantiteReserver,
            sum(sliv_qteliv) as quantiteLivree,
            sum(slor_qterel) as quantiteReliquat,
            sum(slor_qterea) as qteLiv
            from {$this->dbIps}:informix.sav_lor 
            inner join {$this->dbIps}:informix.sav_eor on seor_soc = slor_soc and seor_succ = slor_succ 
            and seor_numor = slor_numor
            left join {$this->dbIps}:informix.sav_liv on sliv_soc = slor_soc and sliv_succ = slor_succ and sliv_numor = seor_numor and slor_nolign = sliv_nolign
            
            where 
            slor_soc = '$codeSociete'
                   --and slor_succ = '01'
            and slor_typlig = 'P'
            and seor_serv ='SAV'
            and slor_constp not like 'Z%'
            and slor_constp not like 'LUB'
           
            and seor_numor  = '$numOr'
            group by 1,2;
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return !empty($data[0])
            ? $this->convertirEnUtf8($data[0])
            : [];
    }
}
