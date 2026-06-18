<?php

namespace App\Model\Atelier\Dit;

use App\Dto\Atelier\Dit\DitSearchDto;
use App\Model\Model;
use App\Service\atelier\dit\FiltreService;
use App\Service\security\SecurityService;

class DitListeModel extends Model
{
    private FiltreService $filtreService;


    public function __construct(
        SecurityService $securityService
    ) {
        parent::__construct();
        $this->filtreService = new FiltreService($securityService);
    }

    public function findPaginatedAndFiltered(string $codeSociete, DitSearchDto $ditSearchdto, int $page, int $perPage)
    {
        // Calculer le SKIP
        $skip = ($page - 1) * $perPage;

        $conditions = $this->filtreService->filtre($ditSearchdto);

        $conditionsMultisucursal = $this->filtreService->conditionAgenceService();

        $statement = " SELECT SKIP $skip FIRST $perPage
                    d0_.id AS id,
                    s3_.description AS statut,
                    d0_.id_statut_demande AS id_statut_demande,
                    d0_.numero_demande_dit AS numero_dit,
                    d0_.reparation_realise AS realise_par,
                    w1_.description AS type_document,
                    w2_.description AS niveau_urgence,
                    c4_.libelle_categorie_ate_app AS categorie,
                    m.mmat_numserie AS numero_serie,
                    m.mmat_recalph AS numero_parc,
                    d0_.date_demande AS date_demande,
                    d0_.internet_externe AS int_ext,
                    d0_.agence_service_emmeteur AS emetteur,
                    d0_.agence_service_debiteur AS debiteur,
                    d0_.objet_demande AS objet,
                    d0_.section_affectee AS section_affectee,
                    d0_.numero_devis_rattache AS numero_devis,
                    d0_.statut_devis AS statut_devis,
                    d0_.numero_or AS numero_or,
                    d0_.statut_or AS statut_or,
                    COALESCE(osv_or.montantitv, osv_dit.montantitv) AS montantitv,
                    COALESCE(osv_or.datesoumission, osv_dit.datesoumission) AS datesoumission,
                    d0_.etat_facturation AS statut_facture,
                    d0_.ri AS ri,
                    d0_.utilisateur_demandeur AS utilisateur ,
                    (
                        (CASE WHEN d0_.piece_joint1 IS NOT NULL AND d0_.piece_joint1 <> '' AND d0_.piece_joint1 <> ' ' THEN 1 ELSE 0 END) + 
                        (CASE WHEN d0_.piece_joint2 IS NOT NULL AND d0_.piece_joint2 <> '' AND d0_.piece_joint2 <> ' ' THEN 1 ELSE 0 END) + 
                        (CASE WHEN d0_.piece_joint IS NOT NULL AND d0_.piece_joint <> '' AND d0_.piece_joint <> ' ' THEN 1 ELSE 0 END)
                    ) AS nbrPj,
                    CASE
                        WHEN d0_.id_statut_demande = 50
                        OR (d0_.id_statut_demande = 51 AND d0_.utilisateur_demandeur = 'lanto')
                        OR (d0_.id_statut_demande = 53 AND (d0_.numero_or IS NULL OR d0_.numero_or = ''))
                        THEN 1 ELSE 0
                    END AS est_annulable,
                    CASE
                        WHEN (d0_.id_statut_demande = 51 AND COALESCE(osv_or.montantitv, osv_dit.montantitv) IS NULL)
                        OR (d0_.id_statut_demande = 53 AND d0_.internet_externe = 'EXTERNE')
                        OR (d0_.id_statut_demande = 53 AND COALESCE(osv_or.montantitv, osv_dit.montantitv) IS NOT NULL)
                        OR  d0_.id_statut_demande = 57
                        THEN 1 ELSE 0
                    END AS est_a_soumis

                FROM {$this->dbIrium}:informix.demande_intervention d0_

                LEFT JOIN {$this->dbIrium}:informix.wor_type_document w1_
                    ON d0_.type_document = w1_.id

                LEFT JOIN {$this->dbIrium}:informix.wor_niveau_urgence w2_
                    ON d0_.id_niveau_urgence = w2_.id

                LEFT JOIN {$this->dbIrium}:informix.categorie_ate_app c4_
                    ON d0_.categorie_demande = c4_.id

                LEFT JOIN {$this->dbIrium}:informix.statut_demande s3_
                    ON d0_.id_statut_demande = s3_.ID_Statut_Demande
                AND s3_.code_application = 'DIT'
                AND s3_.ID_Statut_Demande IN (50,51,52,53,54,57,78)

                LEFT JOIN {$this->dbIps}:informix.mat_mat m
                    ON d0_.id_materiel = m.mmat_nummat

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_or ON d0_.numero_or = osv_or.numeroor

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_dit
                    ON d0_.numero_demande_dit = osv_dit.numerodit
                AND osv_or.numeroor IS NULL

                WHERE d0_.code_societe = '$codeSociete'
                AND (d0_.statut_or NOT LIKE 'Refus%' OR d0_.statut_or IS NULL)
                $conditions
                $conditionsMultisucursal
                ORDER BY d0_.date_demande DESC, d0_.numero_demande_dit ASC              
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        // Compter le total d'items
        $totalItems = $this->compteNombreItem($codeSociete, $conditions, $conditionsMultisucursal);

        // Calculer le nombre de pages
        $lastPage = ceil($totalItems / $perPage);

        // Compter les statuts
        $statusCounts = $this->compteNombreStatut($codeSociete, $conditions, $conditionsMultisucursal);

        return [
            'data' => $data,
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'statusCounts' => $statusCounts,
        ];
    }

    public function DonnerAAjouterExcel(DitSearchDto $ditSearchdto, string $codeSociete)
    {
        $conditions = $this->filtreService->filtre($ditSearchdto);
        $conditionsMultisucursal = $this->filtreService->conditionAgenceService();

        $statement = " SELECT 
                    s3_.description AS statut,
                    d0_.numero_demande_dit AS numero_dit,
                    d0_.reparation_realise AS realise_par,
                    w1_.description AS type_document,
                    w2_.description AS niveau_urgence,
                    c4_.libelle_categorie_ate_app AS categorie,
                    m.mmat_numserie AS numero_serie,
                    m.mmat_recalph AS numero_parc,
                    d0_.date_demande AS date_demande,
                    d0_.internet_externe AS int_ext,
                    d0_.agence_service_emmeteur AS emetteur,
                    d0_.agence_service_debiteur AS debiteur,
                    d0_.objet_demande AS objet,
                    d0_.section_affectee AS section_affectee,
                    d0_.numero_devis_rattache AS numero_devis,
                    d0_.statut_devis AS statut_devis,
                    d0_.numero_or AS numero_or,
                    d0_.statut_or AS statut_or,
                    COALESCE(osv_or.montantitv, osv_dit.montantitv) AS montantitv,
                    COALESCE(osv_or.datesoumission, osv_dit.datesoumission) AS datesoumission,
                    d0_.etat_facturation AS statut_facture,
                    d0_.ri AS ri,
                    d0_.utilisateur_demandeur AS utilisateur,
                    
                    m.mmat_nummat AS num_matricule,
                TRIM(m.mmat_numserie) AS numero_serie,
                TRIM(m.mmat_recalph) AS numero_parc,
                TRIM(m.mmat_marqmat) AS marque,
                TRIM(m.mmat_desi) AS designation,
                TRIM(m.mmat_typmat) AS modele,
                TRIM(m.mmat_numparc) AS casier,
                    (
                (CASE WHEN d0_.piece_joint1 IS NOT NULL AND d0_.piece_joint1 <> '' AND d0_.piece_joint1 <> ' ' THEN 1 ELSE 0 END) + 
                (CASE WHEN d0_.piece_joint2 IS NOT NULL AND d0_.piece_joint2 <> '' AND d0_.piece_joint2 <> ' ' THEN 1 ELSE 0 END) + 
                (CASE WHEN d0_.piece_joint IS NOT NULL AND d0_.piece_joint <> '' AND d0_.piece_joint <> ' ' THEN 1 ELSE 0 END)
            ) AS nbrPj

                FROM {$this->dbIrium}:informix.demande_intervention d0_

                LEFT JOIN {$this->dbIrium}:informix.wor_type_document w1_
                    ON d0_.type_document = w1_.id

                LEFT JOIN {$this->dbIrium}:informix.wor_niveau_urgence w2_
                    ON d0_.id_niveau_urgence = w2_.id

                LEFT JOIN {$this->dbIrium}:informix.categorie_ate_app c4_
                    ON d0_.categorie_demande = c4_.id

                LEFT JOIN {$this->dbIrium}:informix.statut_demande s3_
                    ON d0_.id_statut_demande = s3_.ID_Statut_Demande
                AND s3_.code_application = 'DIT'
                AND s3_.ID_Statut_Demande IN (50,51,52,53,54,57,78)

                LEFT JOIN {$this->dbIps}:informix.mat_mat m
                    ON d0_.id_materiel = m.mmat_nummat

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_or ON d0_.numero_or = osv_or.numeroor

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_dit
                    ON d0_.numero_demande_dit = osv_dit.numerodit
                AND osv_or.numeroor IS NULL

                WHERE d0_.code_societe = '$codeSociete'
                AND (d0_.statut_or NOT LIKE 'Refus%' OR d0_.statut_or IS NULL)
                $conditions
                $conditionsMultisucursal
                ORDER BY d0_.date_demande DESC, d0_.numero_demande_dit ASC
        ";


        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        return  $this->convertirEnUtf8($data);
    }




    /**
     * compte le nombre de ligne recupérer
     *
     * @return integer
     */
    private function compteNombreItem(string $codeSociete, string $conditions, string $conditionsMultisucursal): int
    {
        $countStatement = "SELECT COUNT(*) as total
                FROM {$this->dbIrium}:informix.demande_intervention d0_
                LEFT JOIN {$this->dbIrium}:informix.wor_type_document w1_
                    ON d0_.type_document = w1_.id

                LEFT JOIN {$this->dbIrium}:informix.wor_niveau_urgence w2_
                    ON d0_.id_niveau_urgence = w2_.id

                LEFT JOIN {$this->dbIrium}:informix.categorie_ate_app c4_
                    ON d0_.categorie_demande = c4_.id

                LEFT JOIN {$this->dbIrium}:informix.statut_demande s3_
                    ON d0_.id_statut_demande = s3_.ID_Statut_Demande
                AND s3_.code_application = 'DIT'
                AND s3_.ID_Statut_Demande IN (50,51,52,53,54,57,78)

                LEFT JOIN {$this->dbIps}:informix.mat_mat m
                    ON d0_.id_materiel = m.mmat_nummat

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_or ON d0_.numero_or = osv_or.numeroor

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_dit
                    ON d0_.numero_demande_dit = osv_dit.numerodit
                AND osv_or.numeroor IS NULL
                WHERE d0_.code_societe = '$codeSociete'
                AND (d0_.statut_or NOT LIKE 'Refus%' OR d0_.statut_or IS NULL)
                $conditions
                $conditionsMultisucursal
        ";


        $countResult = $this->connect->executeQuery($countStatement);
        $countData = $this->connect->fetchResults($countResult);
        return  $countData[0]['total'] ?? 0;
    }

    /**
     * Compte le nombre de chaque statut
     *
     * @return array
     */
    private function compteNombreStatut(string $codeSociete, string $conditions, string $conditionsMultisucursal): array
    {

        $statusStatement = "SELECT s3_.description, COUNT(*) as count
                FROM {$this->dbIrium}:informix.demande_intervention d0_
                LEFT JOIN {$this->dbIrium}:informix.wor_type_document w1_
                    ON d0_.type_document = w1_.id

                LEFT JOIN {$this->dbIrium}:informix.wor_niveau_urgence w2_
                    ON d0_.id_niveau_urgence = w2_.id

                LEFT JOIN {$this->dbIrium}:informix.categorie_ate_app c4_
                    ON d0_.categorie_demande = c4_.id

                LEFT JOIN {$this->dbIrium}:informix.statut_demande s3_
                    ON d0_.id_statut_demande = s3_.ID_Statut_Demande
                AND s3_.code_application = 'DIT'
                AND s3_.ID_Statut_Demande IN (50,51,52,53,54,57,78)

                LEFT JOIN {$this->dbIps}:informix.mat_mat m
                    ON d0_.id_materiel = m.mmat_nummat

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_or ON d0_.numero_or = osv_or.numeroor

                LEFT JOIN (
                    SELECT osv.numeroor, osv.numerodit, osv.montantitv, osv.datesoumission
                    FROM {$this->dbIrium}:informix.ors_soumis_a_validation osv
                    INNER JOIN (
                        SELECT id, MAX(numeroversion) AS max_version
                        FROM {$this->dbIrium}:informix.ors_soumis_a_validation
                        GROUP BY id
                    ) mv ON osv.id = mv.id AND osv.numeroversion = mv.max_version
                ) osv_dit
                    ON d0_.numero_demande_dit = osv_dit.numerodit
                AND osv_or.numeroor IS NULL
                WHERE d0_.code_societe = '$codeSociete'
                AND (d0_.statut_or NOT LIKE 'Refus%' OR d0_.statut_or IS NULL)
                $conditions
                $conditionsMultisucursal
                GROUP BY s3_.description 
        ";

        $statusResult = $this->connect->executeQuery($statusStatement);
        $statusData = $this->connect->fetchResults($statusResult);
        $statusCounts = [];
        foreach ($statusData as $status) {
            $statusCounts[$status['description']] = $status['count'];
        }

        return $statusCounts;
    }



    /**===================================
     * SECTION AFFECTER ET SUPPORT
     *===================================*/
    public function findSectionAffectee()
    {
        $statement = " SELECT distinct section_affectee  as sectionAffectee
                    from {$this->dbIrium}:Informix.demande_intervention 
                    where section_affectee is not null 
                    and section_affectee <> ' ' 
                    and section_affectee <> 'Autres'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'sectionAffectee');
    }


    public function findSectionSupport($id)
    {
        $statement = "
        SELECT 
            section_affectee AS sectionAffectee,
            section_support_1 AS sectionSupport1,
            section_support_2 AS sectionSupport2,
            section_support_3 AS sectionSupport3
        FROM {$this->dbIrium}:Informix.demande_intervention
        WHERE id = $id
    ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));
        return $data ?? [];
    }

    public function findSectionSupport1()
    {
        $statement = " SELECT distinct section_support_1  as sectionSupport1
                    from {$this->dbIrium}:Informix.demande_intervention 
                    where section_support_1 is not null 
                    and section_support_1 <> ' ' 
                    and section_support_1 <> 'Autres'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'sectionSupport1');
    }

    public function findSectionSupport2()
    {
        $statement = " SELECT distinct section_support_2  as sectionSupport2
                    from {$this->dbIrium}:Informix.demande_intervention 
                    where section_support_2 is not null 
                    and section_support_2 <> ' ' 
                    and section_support_2 <> 'Autres'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'sectionSupport2');
    }

    public function findSectionSupport3()
    {
        $statement = " SELECT distinct section_support_3  as sectionSupport3
                    from {$this->dbIrium}:Informix.demande_intervention 
                    where section_support_3 is not null 
                    and section_support_3 <> ' ' 
                    and section_support_3 <> 'Autres'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'sectionSupport3');
    }

    /** =======================================
     * recuperation de nombre de pièce jointe
     * ======================================== */
    public function findNbrPj(string $numDit): int
    {
        $statement = "SELECT 
        SUM(
            (CASE WHEN piece_joint1 IS NOT NULL AND piece_joint1 <> '' AND piece_joint1 <> ' ' THEN 1 ELSE 0 END) + 
            (CASE WHEN piece_joint2 IS NOT NULL AND piece_joint2 <> '' AND piece_joint2 <> ' ' THEN 1 ELSE 0 END) + 
            (CASE WHEN piece_joint IS NOT NULL AND piece_joint <> '' AND piece_joint <> ' ' THEN 1 ELSE 0 END)
        ) AS nombrePiecesJointes
    FROM {$this->dbIrium}:Informix.demande_intervention
    WHERE numero_demande_dit = '$numDit'
        ";

        $result = $this->connect->executeQuery($statement, ['numDit' => $numDit]);

        $rows = array_column($this->connect->fetchResults($result), 'nombrePiecesJointes');

        $nombrePiecesJointes = isset($rows[0]) ? (int) $rows[0] : 0;


        return $nombrePiecesJointes;
    }
    public function recupItvNumFac($numOr)
    {
        $statement = " SELECT DISTINCT
                        sitv_interv as itv,
                        slor_numfac AS numeroFac
                    FROM
                        sav_itv
                    JOIN
                        sav_lor ON sitv_numor = slor_numor
                        AND sitv_interv = slor_nogrp / 100
                    WHERE
                        sitv_numor = '" . $numOr . "'
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);
        $dataUtf8 = $this->convertirEnUtf8($data);

        return $dataUtf8;
    }

    public function recupItvComment(string $numOr)
    {
        $statement = " SELECT 
                        sitv_interv as numeroItv,
                        TRIM(sitv_comment) as commentair
                    from sav_itv
                    where sitv_numor = '" . $numOr . "'
        ";

        $result = $this->connect->executeQuery($statement);


        $data = $this->connect->fetchResults($result);
        $dataUtf8 =   $this->convertirEnUtf8($data);

        return   $dataUtf8;
    }
}
