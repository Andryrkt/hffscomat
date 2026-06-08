<?php

namespace App\Model\Atelier\Dit;

use App\Constants\admin\ApplicationConstant;
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

    public function findPaginatedAndFiltered(string $codeSociete, DitSearchDto $dtoSearch, $page = 1, $perPage = 100)
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
        $conditions = $this->filtre($dtoSearch);
        if (!empty($conditions)) {
            $statement .= " AND " . implode("AND", $conditions);
        }
        $statement .= " ORDER BY d0_.date_demande DESC, d0_.numero_demande_dit ASC ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        // Compter le total d'items
        $totalItems = $this->compteNombreItem();

        // Calculer le nombre de pages
        $lastPage = ceil($totalItems / $perPage);

        // Compter les statuts
        $statusCounts = $this->compteNombreStatut();

        return [
            'data' => $this->convertirEnUtf8($data),
            'totalItems' => $totalItems,
            'currentPage' => $page,
            'lastPage' => $lastPage,
            'statusCounts' => $statusCounts,
        ];
    }

    /**
     * compte le nombre de ligne recupérer
     *
     * @return integer
     */
    private function compteNombreItem(): int
    {
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
        return  $countData[0]['total'] ?? 0;
    }

    /**
     * Compte le nombre de chaque statut
     *
     * @return array
     */
    private function compteNombreStatut(): array
    {
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

        return $statusCounts;
    }

    private function filtre(DitSearchDto $dtoSearch)
    {
        $conditions = [];
        // filtrer par niveau d'urgence
        if (!empty($dtoSearch->niveauUrgence)) {
            $conditions[] = "  w2_.description = '$dtoSearch->niveauUrgence' ";
        }
        // filtrer par statut demande
        if (!empty($dtoSearch->statut)) {
            $conditions[] = "  s3_.description = '$dtoSearch->statut' ";
        }

        // filtrer par id matériel
        if (!empty($dtoSearch->idMateriel)) {
            $conditions[] = "  mmat_nummat = '$dtoSearch->idMateriel' ";
        }

        // filtrer par numéro parc
        if (!empty($dtoSearch->numParc)) {
            $conditions[] = " mmat_recalph = '$dtoSearch->numParc' ";
        }

        // filtrer par numéro serie
        if (!empty($dtoSearch->numSerie)) {
            $conditions[] = " mmat_numserie = '$dtoSearch->numSerie' ";
        }

        // filtrer par type Document
        if (!empty($dtoSearch->typeDocument)) {
            $conditions[] = " w1_.description = '$dtoSearch->typeDocument' ";
        }

        // filtrer par interne et externe
        if (!empty($dtoSearch->internetExterne)) {
            $conditions[] = "  d0_.internet_externe = '$dtoSearch->internetExterne' ";
        }

        // filtrer par date de demande Debut
        if (!empty($dtoSearch->dateDebut)) {
            $conditions[] = "  d0_.date_demande = '$dtoSearch->dateDebut' ";
        }

        // filtrer par date de demande FIN
        if (!empty($dtoSearch->dateFin)) {
            $conditions[] = "  d0_.date_demande = '$dtoSearch->dateFin' ";
        }

        // filtrer par numero demande d'intervention (DIT)
        if (!empty($dtoSearch->numDit)) {
            $conditions[] = "  d0_.numero_demande_dit = '$dtoSearch->numDit' ";
        }

        // filtrer par numero OR
        if (!empty($dtoSearch->numOr)) {
            $conditions[] = " d0_.numero_or = '$dtoSearch->numOr' ";
        }

        // filtrer par statut OR
        if (!empty($dtoSearch->statutOr)) {
            $conditions[] = "  d0_.statut_or = '$dtoSearch->statutOr' ";
        }

        // filtrer par DIT qui n'a pas d'OR
        if ($dtoSearch->ditSansOr) {
            $conditions[] = "  (d0_.numero_or IS NULL OR d0_.numero_or = '') ";
        }

        // filtrer par catégorie
        if ($dtoSearch->categorie) {
            $conditions[] = "  c4_.libelle_categorie_ate_app = '$dtoSearch->categorie' ";
        }

        // filtrer par utilisateur
        if ($dtoSearch->utilisateur) {
            $conditions[] = " d0_.utilisateur_demandeur = '$dtoSearch->utilisateur' ";
        }

        // filtrer par section Affectee
        if ($dtoSearch->sectionAffectee) {
            $conditions[] = "  d0_.section_affectee = '$dtoSearch->sectionAffectee' ";
        }

        // filtrer par section support 1
        if ($dtoSearch->sectionSupport1) {
            $conditions[] = "  d0_.section_support_1 = '$dtoSearch->sectionSupport1' ";
        }

        // filtrer par section support 2
        if ($dtoSearch->sectionSupport2) {
            $conditions[] = "  d0_.section_support_2 = '$dtoSearch->sectionSupport2' ";
        }

        // filtrer par section support 3
        if ($dtoSearch->sectionSupport3) {
            $conditions[] = " d0_.section_support_3 = '$dtoSearch->sectionSupport3' ";
        }

        // filrer par statut (etat) facture
        if ($dtoSearch->etatFacture) {
            $conditions[] = "  d0_.etat_facturation = '$dtoSearch->etatFacture' ";
        }
        // firtrer par numéro devis
        if ($dtoSearch->numDevis) {
            $conditions[] = "  d0_.numero_devis_rattache = '$dtoSearch->numDevis' ";
        }
        // filtrer par réparation realise 
        if ($dtoSearch->reparationRealise) {
            $conditions[] = "  d0_.reparation_realise = '$dtoSearch->reparationRealise' ";
        }

        // TODO: filtrer par Agence et service emetteur et debitteur

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->securityService->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);
        if ($multisuccursale) {

            // Agences Services autorisés sur le DIT
            $agenceServiceAutorises = $this->securityService->getAgenceServices(ApplicationConstant::CODE_DIT);
            if (!empty($agenceServiceAutorises)) {
                $conditionsEmetteur = [];
                $conditionsDebiteur = [];
                // Vérifier le permission de voir liste avec débiteur sur la page courante
                $peutVoirListeAvecDebiteur = $this->securityService->verifierPermission(SecurityService::PERMISSION_AUTH_2);

                foreach ($agenceServiceAutorises as $i => $tab) {
                    $conditionsEmetteur[] = "(d.agenceEmetteurId = :agEmetteur_{$i} AND d.serviceEmetteurId = :servEmetteur_{$i})";

                    if ($peutVoirListeAvecDebiteur) {
                        $conditionsDebiteur[] = "(d.agenceDebiteurId = :agDebiteur_{$i} AND d.serviceDebiteurId = :servDebiteur_{$i})";
                    }
                }

                $sqlPart = '(' . implode(' OR ', $conditionsEmetteur) . ')';

                if ($peutVoirListeAvecDebiteur && !empty($conditionsDebiteur)) {
                    $sqlPart .= ' OR (' . implode(' OR ', $conditionsDebiteur) . ')';
                }

                $conditions[] = $sqlPart;
            }

            $conditions[] = "   (d0_.agence_emetteur_id = {$this->securityService->getAgenceIdUser()} AND d0_.service_emetteur_id = {$this->securityService->getServiceIdUser()})
            OR (d0_.agence_debiteur_id = {$this->securityService->getAgenceIdUser()} AND d0_.service_debiteur_id = {$this->securityService->getServiceIdUser()})
            ";
        }

        if ($dtoSearch->agenceEmetteur) {
            $conditions[] = "  d0_.agence_emetteur_id = '$dtoSearch->agenceEmetteur' ";
        }

        if ($dtoSearch->serviceEmetteur) {
            $conditions[] = "  d0_.service_emetteur_id = '$dtoSearch->serviceEmetteur' ";
        }

        if ($dtoSearch->agenceDebiteur) {
            $conditions[] = "  d0_.agence_debiteur_id = '$dtoSearch->agenceDebiteur' ";
        }

        if ($dtoSearch->serviceDebiteur) {
            $conditions[] = "  d0_.service_debiteur_id = '$dtoSearch->serviceDebiteur' ";
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
