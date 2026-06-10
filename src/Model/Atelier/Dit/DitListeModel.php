<?php

namespace App\Model\Atelier\Dit;

use App\Constants\admin\ApplicationConstant;
use App\Constants\atelier\dit\StatutDitConstant;
use App\Dto\Atelier\Dit\DitSearchDto;
use App\Model\Model;
use App\Service\security\SecurityService;

class DitListeModel extends Model
{
    private SecurityService $securityService;

    public function __construct(
        SecurityService $securityService
    ) {
        parent::__construct();
        $this->securityService = $securityService;
    }

    public function findPaginatedAndFiltered(string $codeSociete, DitSearchDto $ditSearchdto, int $page, int $perPage)
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
        ";
        $conditions = $this->filtre($ditSearchdto);

        if (!empty($conditions)) {
            $statement .= " AND " . implode("AND", $conditions);
        }

        $statement .= " ORDER BY d0_.date_demande DESC, d0_.numero_demande_dit ASC ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        // Compter le total d'items
        $totalItems = $this->compteNombreItem($codeSociete, $conditions);
        // Calculer le nombre de pages
        $lastPage = ceil($totalItems / $perPage);

        // Compter les statuts
        $statusCounts = $this->compteNombreStatut($codeSociete, $conditions);
        return [
            'data' => $this->convertirEnUtf8($data),
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'statusCounts' => $statusCounts,
        ];
    }
    public function DonnerAAjouterExcel(DitSearchDto $ditSearchdto, string $codeSociete)

    {

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
        ";
        $conditions = $this->filtre($ditSearchdto);



        if (!empty($conditions)) {
            $statement .= " AND " . implode("AND", $conditions);
        }


        $statement .= " ORDER BY d0_.date_demande DESC, d0_.numero_demande_dit ASC ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        return  $this->convertirEnUtf8($data);
    }
    private function conditionAgenceService(
        int $agenceIdUser,
        int $serviceIdUser,
        array $agenceServiceAutorises,
        string $codeAgenceUser,
        bool $peutVoirListeAvecDebiteur,
        bool $avecAtelierRealisePar
    ): string {
        $orConditions = [];

        // 1- Émetteur du DOM : agence et service de l'utilisateur
        $orConditions[] = "(d0_.agence_emetteur_id = " . $agenceIdUser . " AND d0_.service_emetteur_id = " . $serviceIdUser . ")";

        // 2- Débiteur du DOM : agence et service de l'utilisateur
        $orConditions[] = "(d0_.agence_debiteur_id = " . $agenceIdUser . " AND d0_.service_debiteur_id = " . $serviceIdUser . ")";

        // 3- Émetteur et Débiteur : agences et services autorisés du profil
        if (!empty($agenceServiceAutorises)) {
            $emetteurSubConditions = [];
            $debiteurSubConditions = [];

            foreach ($agenceServiceAutorises as $tab) {
                $agId = (int)$tab['agence_id'];
                $servId = (int)$tab['service_id'];

                // Sous-condition pour l'émetteur
                $emetteurSubConditions[] = "(d0_.agence_emetteur_id = " . $agId . " AND d0_.service_emetteur_id = " . $servId . ")";

                // Sous-condition pour le débiteur (si autorisé par le profil)
                if ($peutVoirListeAvecDebiteur) {
                    $debiteurSubConditions[] = "(d0_.agence_debiteur_id = " . $agId . " AND d0_.service_debiteur_id = " . $servId . ")";
                }
            }

            if (!empty($emetteurSubConditions)) {
                $orConditions[] = "(" . implode(" OR ", $emetteurSubConditions) . ")";
            }

            if ($peutVoirListeAvecDebiteur && !empty($debiteurSubConditions)) {
                $orConditions[] = "(" . implode(" OR ", $debiteurSubConditions) . ")";
            }
        }

        if (!empty($orConditions)) {
            return "(" . implode(" OR ", $orConditions) . ")";
        }

        return "";
    }


    /**
     * compte le nombre de ligne recupérer
     *
     * @return integer
     */
    private function compteNombreItem(string $codeSociete, array $conditions): int
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
        ";

        if (!empty($conditions)) {
            $countStatement .= " AND " . implode(" AND ", $conditions);
        }

        $countResult = $this->connect->executeQuery($countStatement);
        $countData = $this->connect->fetchResults($countResult);
        return  $countData[0]['total'] ?? 0;
    }

    /**
     * Compte le nombre de chaque statut
     *
     * @return array
     */
    private function compteNombreStatut(string $codeSociete, array $conditions): array
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
        ";
        if (!empty($conditions)) {
            $statusStatement .= " AND " . implode(" AND ", $conditions);
        }
        $statusStatement .= " GROUP BY s3_.description ";

        $statusResult = $this->connect->executeQuery($statusStatement);
        $statusData = $this->connect->fetchResults($statusResult);
        $statusCounts = [];
        foreach ($statusData as $status) {
            $statusCounts[$status['description']] = $status['count'];
        }

        return $statusCounts;
    }

    private function filtre(DitSearchDto $ditSearchdto)
    {
        $conditions = [];
        // filtrer par niveau d'urgence
        if (!empty($ditSearchdto->niveauUrgence)) {
            $conditions[] = "  w2_.description = '$ditSearchdto->niveauUrgence' ";
        }
        // filtrer par statut demande
        if (!empty($ditSearchdto->statut)) {
            $conditions[] = "  s3_.description = '$ditSearchdto->statut' ";
        }

        // filtrer par id matériel
        if (!empty($ditSearchdto->idMateriel)) {
            $conditions[] = "  mmat_nummat = '$ditSearchdto->idMateriel' ";
        }

        // filtrer par numéro parc
        if (!empty($ditSearchdto->numParc)) {
            $conditions[] = " mmat_recalph = '$ditSearchdto->numParc' ";
        }

        // filtrer par numéro serie
        if (!empty($ditSearchdto->numSerie)) {
            $conditions[] = " mmat_numserie = '$ditSearchdto->numSerie' ";
        }

        // filtrer par type Document
        if (!empty($ditSearchdto->typeDocument)) {
            $conditions[] = " w1_.description = '$ditSearchdto->typeDocument' ";
        }

        // filtrer par interne et externe
        if (!empty($ditSearchdto->internetExterne)) {
            $conditions[] = "  d0_.internet_externe = '$ditSearchdto->internetExterne' ";
        }

        // filtrer par date de demande Debut
        if (!empty($ditSearchdto->dateDebut)) {
            $conditions[] = "  d0_.date_demande = '$ditSearchdto->dateDebut' ";
        }

        // filtrer par date de demande FIN
        if (!empty($ditSearchdto->dateFin)) {
            $conditions[] = "  d0_.date_demande = '$ditSearchdto->dateFin' ";
        }

        // filtrer par numero demande d'intervention (DIT)
        if (!empty($ditSearchdto->numDit)) {
            $conditions[] = "  d0_.numero_demande_dit = '$ditSearchdto->numDit' ";
        }

        // filtrer par numero OR
        if (!empty($ditSearchdto->numOr)) {
            $conditions[] = " d0_.numero_or = '$ditSearchdto->numOr' ";
        }

        // filtrer par statut OR
        if (!empty($ditSearchdto->statutOr)) {
            $conditions[] = "  d0_.statut_or = '$ditSearchdto->statutOr' ";
        }

        // filtrer par DIT qui n'a pas d'OR
        if ($ditSearchdto->ditSansOr) {
            $conditions[] = "  (d0_.numero_or IS NULL OR d0_.numero_or = '') ";
        }

        // filtrer par catégorie
        if ($ditSearchdto->categorie) {
            $conditions[] = "  c4_.libelle_categorie_ate_app = '$ditSearchdto->categorie' ";
        }

        // filtrer par utilisateur
        if ($ditSearchdto->utilisateur) {
            $conditions[] = " d0_.utilisateur_demandeur = '$ditSearchdto->utilisateur' ";
        }

        // filtrer par section Affectee
        if ($ditSearchdto->sectionAffectee) {
            $conditions[] = "  d0_.section_affectee = '$ditSearchdto->sectionAffectee' ";
        }

        // filtrer par section support 1
        if ($ditSearchdto->sectionSupport1) {
            $conditions[] = "  d0_.section_support_1 = '$ditSearchdto->sectionSupport1' ";
        }

        // filtrer par section support 2
        if ($ditSearchdto->sectionSupport2) {
            $conditions[] = "  d0_.section_support_2 = '$ditSearchdto->sectionSupport2' ";
        }

        // filtrer par section support 3
        if ($ditSearchdto->sectionSupport3) {
            $conditions[] = " d0_.section_support_3 = '$ditSearchdto->sectionSupport3' ";
        }

        // filrer par statut (etat) facture
        if ($ditSearchdto->etatFacture) {
            $conditions[] = "  d0_.etat_facturation = '$ditSearchdto->etatFacture' ";
        }
        // firtrer par numéro devis
        if ($ditSearchdto->numDevis) {
            $conditions[] = "  d0_.numero_devis_rattache = '$ditSearchdto->numDevis' ";
        }
        // filtrer par réparation realise 
        if ($ditSearchdto->reparationRealise) {
            $conditions[] = "  d0_.reparation_realise = '$ditSearchdto->reparationRealise' ";
        }

        // TODO: filtrer par Agence et service emetteur et debitteur

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->securityService->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);
        // if ($multisuccursale) {

        //     // Agences Services autorisés sur le DIT
        //     $agenceServiceAutorises = $this->securityService->getAgenceServices(ApplicationConstant::CODE_DIT);
        //     if (!empty($agenceServiceAutorises)) {
        //         $conditionsEmetteur = [];
        //         $conditionsDebiteur = [];
        //         // Vérifier le permission de voir liste avec débiteur sur la page courante
        //         $peutVoirListeAvecDebiteur = $this->securityService->verifierPermission(SecurityService::PERMISSION_AUTH_2);

        //         foreach ($agenceServiceAutorises as $i => $tab) {
        //             $conditionsEmetteur[] = "(d.agenceEmetteurId = :agEmetteur_{$i} AND d.serviceEmetteurId = :servEmetteur_{$i})";

        //             if ($peutVoirListeAvecDebiteur) {
        //                 $conditionsDebiteur[] = "(d.agenceDebiteurId = :agDebiteur_{$i} AND d.serviceDebiteurId = :servDebiteur_{$i})";
        //             }
        //         }

        //         $sqlPart = '(' . implode(' OR ', $conditionsEmetteur) . ')';

        //         if ($peutVoirListeAvecDebiteur && !empty($conditionsDebiteur)) {
        //             $sqlPart .= ' OR (' . implode(' OR ', $conditionsDebiteur) . ')';
        //         }

        //         $conditions[] = $sqlPart;
        //     }

        //     $conditions[] = "   (d0_.agence_emetteur_id = {$this->securityService->getAgenceIdUser()} AND d0_.service_emetteur_id = {$this->securityService->getServiceIdUser()})
        //     OR (d0_.agence_debiteur_id = {$this->securityService->getAgenceIdUser()} AND d0_.service_debiteur_id = {$this->securityService->getServiceIdUser()})
        //     ";
        // }

        if ($ditSearchdto->agenceEmetteur) {
            $conditions[] = "  d0_.agence_emetteur_id = '$ditSearchdto->agenceEmetteur' ";
        }

        if ($ditSearchdto->serviceEmetteur) {
            $conditions[] = "  d0_.service_emetteur_id = '$ditSearchdto->serviceEmetteur' ";
        }

        if ($ditSearchdto->agenceDebiteur) {
            $conditions[] = "  d0_.agence_debiteur_id = '$ditSearchdto->agenceDebiteur' ";
        }

        if ($ditSearchdto->serviceDebiteur) {
            $conditions[] = "  d0_.service_debiteur_id = '$ditSearchdto->serviceDebiteur' ";
        }

        return $conditions;
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
}
