<?php

namespace App\Model\Atelier\Dit;

use App\Model\Model;

class DitListeModel extends Model
{
    public function findPaginatedAndFiltered(string $codeSociete, $page = 1, $perPage = 100)
    {
        // Calculer le SKIP
        $skip = ($page - 1) * $perPage;

        $statement = " SELECT SKIP $skip FIRST $perPage
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
                    d0_.utilisateur_demandeur AS utilisateur

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

                ORDER BY d0_.date_demande DESC, d0_.numero_demande_dit ASC
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        // Compter le total d'items
        $countStatement = "SELECT COUNT(*) as total
                FROM {$this->dbIrium}:informix.demande_intervention d0_
                LEFT JOIN {$this->dbIrium}:informix.statut_demande s3_
                    ON d0_.id_statut_demande = s3_.ID_Statut_Demande
                    AND s3_.code_application = 'DIT'
                    AND s3_.ID_Statut_Demande IN (50,51,52,53,54,57,78)
                WHERE d0_.code_societe = 'HF'
                AND (d0_.statut_or NOT LIKE 'Refus%' OR d0_.statut_or IS NULL)
        ";

        $countResult = $this->connect->executeQuery($countStatement);
        $countData = $this->connect->fetchResults($countResult);
        $totalItems = $countData[0]['total'] ?? 0;

        // Calculer le nombre de pages
        $lastPage = ceil($totalItems / $perPage);

        // Compter les statuts
        $statusStatement = "SELECT s3_.description, COUNT(*) as count
                FROM {$this->dbIrium}:informix.demande_intervention d0_
                LEFT JOIN {$this->dbIrium}:informix.statut_demande s3_
                    ON d0_.id_statut_demande = s3_.ID_Statut_Demande
                    AND s3_.code_application = 'DIT'
                    AND s3_.ID_Statut_Demande IN (50,51,52,53,54,57,78)
                WHERE d0_.code_societe = 'HF'
                AND (d0_.statut_or NOT LIKE 'Refus%' OR d0_.statut_or IS NULL)
                GROUP BY s3_.description
        ";

        $statusResult = $this->connect->executeQuery($statusStatement);
        $statusData = $this->connect->fetchResults($statusResult);
        $statusCounts = [];
        foreach ($statusData as $status) {
            $statusCounts[$status['description']] = $status['count'];
        }

        return [
            'data' => $this->convertirEnUtf8($data),
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'statusCounts' => $statusCounts,
        ];
    }

    /**===================================
     * SECTION AFFECTER ET SUPPORT
     *===================================*/


    public function findSectionAffectee()
    {
        $statement = " SELECT distinct section_affectee  as sectionAffectee
                    from Informix.demande_intervention 
                    where section_affectee is not null 
                    and section_affectee <> ' ' 
                    and section_affectee <> 'Autres'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'sectionAffectee');
    }

    public function findSectionSupport1()
    {
        $statement = " SELECT distinct section_support_1  as sectionSupport1
                    from Informix.demande_intervention 
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
                    from Informix.demande_intervention 
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
                    from Informix.demande_intervention 
                    where section_support_3 is not null 
                    and section_support_3 <> ' ' 
                    and section_support_3 <> 'Autres'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'sectionSupport3');
    }
}
