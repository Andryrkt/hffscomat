<?php


namespace App\Model\Atelier\Dit;

use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Mapper\Atelier\Dit\DitMapper;
use App\Model\Informix\InsertQueryBuilder;
use App\Model\Informix\UpdateQueryBuilder;
use App\Model\Model;

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

      FROM MAT_MAT
      LEFT JOIN mat_bil on mbil_nummat = mmat_nummat and mbil_dateclot <= '01/01/1900' and mbil_dateclot = '12/31/1899'
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

    public function getAllClients()
    {
        $statement = "SELECT DISTINCT nent_numcli as num_client, nent_nomcli as nom_client
                        from neg_ent
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function recupAllClientExterne()
    {
        $statement = " SELECT cbse_nomcli, cbse_numcli FROM cli_bse , cli_soc WHERE cbse_numcli = csoc_numcli and csoc_soc ='HF'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    /**
     * Methode pour enregistrer les données du formulaire Dit
     *  dans la base de donnée
     *
     * @param OrSoumissionDto $dto
     * @return void
     */
    public function enregistrerDit(OrSoumissionDto $dto, array $ors): void
    {
        // Convertir le DTO en tableau associatif pour l'insertion
        $donnees = DitMapper::toArrayDit($dto, $ors);

        // Construire la requête d'insertion et l'exécuter
        $builder = new InsertQueryBuilder("{$this->dbIrium}:Informix.bc_client_soumis_neg");
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

        // // Définir les données à mettre à jour
        $updateBuilder->setData($donnees);

        // // Ajouter les conditions WHERE
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
    public function recupInformationsDit($numDit, $codeSociete)
    {

        $statement = " SELECT FIRST 1 *
        FROM {$this->dbIrium}:Informix.demande_intervention
        WHERE numero_demande_dit = '$numDit'
        AND code_societe = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);
        $data = $this->convertirEnUtf8($data);
        $info_materiel = $data[0] ?? [];

        return $info_materiel;
    }

    public function recupOrSoumisValidation($numOr, $codeSociete)
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
                  WHEN slor_typlig = 'P' 
                  THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                  WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_qterea
              END 
              * 
              CASE
                  WHEN slor_typlig = 'P' THEN slor_pxnreel
                  WHEN slor_typlig IN ('F', 'M', 'U', 'C') THEN slor_pxnreel
              END
          ) as MONTANT_ITV,

          Sum(
              CASE
                  WHEN slor_typlig = 'P'
                  AND slor_constp NOT like 'Z%'
                  AND slor_constp <> 'LUB' THEN (nvl (slor_qterel, 0) + nvl (slor_qterea, 0) + nvl (slor_qteres, 0) + nvl (slor_qtewait, 0) - nvl (slor_qrec, 0))
              END 
              * 
              CASE
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
                  WHEN 
                    slor_typlig = 'P'
                    AND slor_constp NOT like 'Z%'
                    AND slor_constp = 'LUB' 
                  THEN (nvl (slor_qterel, 0) + nvl (slor_qterea, 0) + nvl (slor_qteres, 0) + nvl (slor_qtewait, 0) - nvl (slor_qrec, 0))
              END 
              * 
              CASE
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
              AND seor_soc = '$codeSociete'
              AND slor_soc=seor_soc
              AND sitv_soc=seor_soc
          --AND sitv_pos NOT IN('FC', 'FE', 'CP', 'ST')
          --AND sitv_servcrt IN ('ATE','FOR','GAR','MAN','CSP','MAS','LR6','LST')
          AND seor_numor = '$numOr'
          --AND SEOR_SUCC = '01'
          group by 1, 2, 3, 4, 5
          order by slor_numor, sitv_interv
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
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
    public function recupereCommandeOr($numero_or)
    {
        $statement = "SELECT
        slor_numcf,
        fcde_date,
        slor_typcf,
        fcde_posc,
        fcde_posl

      from sav_lor
      inner join frn_cde on frn_cde.fcde_numcde = slor_numcf
      where
      slor_soc = 'HF'
      --and slor_succ = '01'
      and slor_constp not like '%Z'
      and slor_numor in (select seor_numor from sav_eor where seor_serv = 'SAV')
      and slor_numor = '" . $numero_or . "'
      group by 1,2,3,4,5";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }
}
