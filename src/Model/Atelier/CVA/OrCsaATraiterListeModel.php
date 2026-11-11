<?php

namespace App\Model\Atelier\CVA;

use App\Dto\Atelier\CVA\OrCsaATraiterSearchDto;
use App\Model\Model;
use App\Service\Atelier\CVA\FiltreService;
use App\Service\security\SecurityService;

class OrCsaATraiterListeModel extends Model
{
    // -----------------------------------------------------------------
    // Paramètres métier (cf. spec : valeurs paramétrables, pas codées en dur)
    // -----------------------------------------------------------------
    private int   $delaiAllocation  = 90;
    private int   $toleranceFenetre = 10;
    // Types a changer en ['222', '229'] pour la prod
    // private array $typesOr          = ['222', '229'];
    private array $typesOr          = ['201', '202'];
    private int   $anneeMin         = 2025;

    private FiltreService $filtreService;

    public function __construct(
        SecurityService $securityService,
        int $delaiAllocation = 90,
        int $toleranceFenetre = 10,
        // Types a changer en ['222', '229'] pour la prod
        // array $typesOr          = ['222', '229'],
        array $typesOr          = ['201', '202'],


        int $anneeMin = 2025
    ) {
        parent::__construct();

        $this->delaiAllocation  = $delaiAllocation;
        $this->toleranceFenetre = $toleranceFenetre;
        $this->typesOr          = $typesOr;
        $this->anneeMin         = $anneeMin;

        // ⚠️ FiltreService doit aussi connaître délai/tolérance pour
        // traduire les codes TOUS / RETARD / AUTOUR_J90 / A_VENIR.
        $this->filtreService = new FiltreService($securityService);
    }

    public function findPaginatedAndFiltered(
        string $codeSociete,
        OrCsaATraiterSearchDto $orCsaATraiterSearchDto,
        int $page,
        int $perPage
    ): array {
        $skip = ($page - 1) * $perPage;

        // Conditions des filtres de l'écran (fenêtre, lead time, client, etc.)
        $conditions              = $this->filtreService->filtre($orCsaATraiterSearchDto);
        $conditionsMultisucursal = ''; // réservé au filtrage agence/service

        $innerQuery = $this->buildBaseQuery($conditions, $conditionsMultisucursal);
        // ---------- Requête paginée ----------

        $statement = "
            SELECT SKIP {$skip} FIRST {$perPage} t.*
            FROM ({$innerQuery}) AS t
            ORDER BY t.lead_time ASC, t.seor_numor ASC
        ";

        $result = $this->connect->executeQuery($statement);
        $data   = $this->convertirEnUtf8($this->connect->fetchResults($result));
        // ---------- Total ----------
        $totalItems = $this->compteNombreItem($innerQuery);
        $lastPage   = $totalItems > 0 ? (int) ceil($totalItems / $perPage) : 1;

        // ---------- Comptage par statut ----------
        $statusCounts = $this->compteNombreStatut($innerQuery);

        return [
            'data'         => $data,
            'totalItems'   => $totalItems,
            'currentPage'  => $page,
            'lastPage'     => $lastPage,
            'statusCounts' => $statusCounts,
        ];
    }

    public function DonnerAAjouterExcel(
        OrCsaATraiterSearchDto $orCsaATraiterSearchDto,
        string $codeSociete
    ): array {
        // Conditions des filtres de l'écran (fenêtre, lead time, client, etc.)
        $conditions              = $this->filtreService->filtre($orCsaATraiterSearchDto);
        $conditionsMultisucursal = ''; // réservé au filtrage agence/service

        $innerQuery = $this->buildBaseQuery($conditions, $conditionsMultisucursal);

        $statement = "
            SELECT t.*
            FROM ({$innerQuery}) AS t
            ORDER BY t.lead_time ASC, t.seor_numor ASC
        ";

        $result = $this->connect->executeQuery($statement);

        return $this->convertirEnUtf8($this->connect->fetchResults($result));
    }

    /**
     * Construit la requête de base (SELECT DISTINCT + WHERE).
     * Les paramètres métier sont injectés ici, jamais codés en dur
     * dans le SQL final.
     */
    private function buildBaseQuery(string $conditions, string $conditionsMultisucursal): string
    {
        // Liste des types OR, échappée par sécurité (paramètres serveur,
        // pas d'input utilisateur direct ici, mais on protège).
        $typesOrSql = implode(', ', array_map(
            static fn(string $t): string => "'" . str_replace("'", "''", $t) . "'",
            $this->typesOr
        ));

        $d = $this->delaiAllocation;                       // seuil « J-90 »
        $h = $this->delaiAllocation + $this->toleranceFenetre; // borne haute fenêtre

        return "SELECT DISTINCT
                seor_numor,
                seor_numcli || ' - ' || seor_nomcli AS client,
                seor_lib                            AS reference_client,
                seor_typeor || ' - ' || aces_lib    AS type_or,
                seor_delai                          AS lead_time,
                (seor_delai - TODAY)                AS nb_jours_dispo,
                CAST((seor_delai - TODAY) AS VARCHAR(10)) || ' j'
                                                    AS nb_jours_dispo_lib,
                CASE
                    WHEN (seor_delai - TODAY) < {$d}
                        THEN 'Retard de '
                             || CAST({$d} - (seor_delai - TODAY) AS VARCHAR(10))
                             || ' j'
                    WHEN (seor_delai - TODAY) = {$d}
                        THEN 'J-{$d} aujourd''hui'
                    ELSE 'J-{$d} dans '
                             || CAST((seor_delai - TODAY) - {$d} AS VARCHAR(10))
                             || ' j'
                END AS statut_allocation,
                CASE
                    WHEN (seor_delai - TODAY) <  {$d} THEN 'RETARD'
                    WHEN (seor_delai - TODAY) <= {$h} THEN 'A_ALLOUER'
                    ELSE 'A_VENIR'
                END AS code_couleur
            FROM {$this->dbIps}.sav_eor
            INNER JOIN {$this->dbIps}.agr_ces
                ON aces_soc = seor_soc
               AND aces_nat = seor_typeor
            INNER JOIN {$this->dbIps}.sav_lor
                ON slor_soc  = seor_soc
               AND slor_succ = seor_succ
               AND slor_numor = seor_numor
            WHERE seor_typeor IN ({$typesOrSql})
              AND YEAR(seor_dateor) >= {$this->anneeMin}
              AND slor_qtewait > 0
              AND seor_delai IS NOT NULL

              ---SOLUTION PROVISOIRE A REVOIR
              AND NOT EXISTS (
                        SELECT 1 
                            FROM {$this->dbIrium}.demande_intervention d0_
                        WHERE d0_.numero_or    = seor_numor
                            AND d0_.code_societe = seor_soc
              )
              -----
              {$conditions}
              {$conditionsMultisucursal}
        ";
    }

    /**
     * Compte le nombre total de lignes (sur la requête de base, sans pagination).
     */
    private function compteNombreItem(string $innerQuery): int
    {
        $countStatement = "SELECT COUNT(*) AS total FROM ({$innerQuery}) AS t";

        $countResult = $this->connect->executeQuery($countStatement);
        $countData   = $this->connect->fetchResults($countResult);

        return (int) ($countData[0]['total'] ?? $countData[0]['TOTAL'] ?? 0);
    }

    /**
     * Compte les OR par code couleur (RETARD / A_ALLOUER / A_VENIR).
     * Le résultat alimentera la légende / les badges côté vue.
     */
    private function compteNombreStatut(string $innerQuery): array
    {
        $statusStatement = "
            SELECT t.code_couleur AS description, COUNT(*) AS count
            FROM ({$innerQuery}) AS t
            GROUP BY t.code_couleur
        ";

        $statusResult = $this->connect->executeQuery($statusStatement);
        $statusData   = $this->connect->fetchResults($statusResult);

        $statusCounts = [];
        foreach ($statusData as $status) {
            $desc = $status['description'] ?? $status['DESCRIPTION'] ?? null;
            $cnt  = $status['count']       ?? $status['COUNT']       ?? 0;
            if ($desc !== null) {
                $statusCounts[$desc] = (int) $cnt;
            }
        }

        return $statusCounts;
    }

    /**
     * Récupère les libellés des types d'OR depuis agr_ces pour le formulaire de recherche.
     */
    public function getTypesOrChoices(): array
    {
        $typesOrSql = implode(', ', array_map(
            static fn(string $t): string => "'" . str_replace("'", "''", $t) . "'",
            $this->typesOr
        ));

        $statement = "SELECT DISTINCT aces_nat, aces_lib 
                      FROM {$this->dbIps}.agr_ces 
                      WHERE aces_nat IN ({$typesOrSql}) 
                      ORDER BY aces_nat";
        $result = $this->connect->executeQuery($statement);
        $data   = $this->convertirEnUtf8($this->connect->fetchResults($result));

        $choices = [];
        foreach ($data as $row) {
            // Prise en charge des majuscules/minuscules selon le driver
            $code    = $row['aces_nat'] ?? $row['ACES_NAT'] ?? null;
            $libelle = $row['aces_lib'] ?? $row['ACES_LIB'] ?? null;

            if ($code && $libelle) {
                // On crée la clé d'affichage : "222 - CSA JOB"
                $choices["{$code} - {$libelle}"] = $code;
            }
        }

        return $choices;
    }

    /**
     * Récupère un OR par son numéro, dans le périmètre société de l'utilisateur.
     * Retourne un tableau normalisé (clés en minuscules) prêt pour
     * DitCreationService::creerDepuisOr().
     *
     * @return array<string,mixed>|null
     */
    public function findOrByNumero(string $numOr, string $codeSociete): ?array
    {
        $numOrEsc   = str_replace("'", "''", $numOr);
        $codeSocEsc = str_replace("'", "''", $codeSociete);

        $statement = "SELECT
            e.seor_numor            AS numero_or,
            e.seor_soc              AS code_societe,
            e.seor_succ             AS code_agence,
            e.seor_servcrt          AS code_service,
            e.seor_dateor           AS date_or,
            e.seor_delai            AS date_prevue_travaux,
            e.seor_delai            AS date_limite_souhaite,
            e.seor_numcli           AS numero_client,
            e.seor_nomcli           AS nom_client,
            e.seor_lib              AS reference_client,
            e.seor_typeor           AS code_type_or,
            e.seor_nummat           AS id_materiel,
            c.aces_lib              AS libelle_type_or,
            e.seor_typeor || ' - ' || c.aces_lib AS type_or,
            m.mmat_recalph           AS numero_parc,
            m.mmat_numserie           AS numero_serie
        FROM {$this->dbIps}.sav_eor e
        INNER JOIN {$this->dbIps}.agr_ces c
            ON  c.aces_soc = e.seor_soc
            AND c.aces_nat = e.seor_typeor
        LEFT JOIN mat_mat m
            ON  m.mmat_nummat = e.seor_nummat
        WHERE e.seor_numor = '$numOrEsc'
        AND e.seor_soc   = '$codeSocEsc'
        AND 
        EXISTS (
            SELECT 1 FROM {$this->dbIps}.sav_lor l
            WHERE l.slor_soc   = e.seor_soc
                AND l.slor_succ  = e.seor_succ
                AND l.slor_numor = e.seor_numor
                AND l.slor_qtewait > 0
                );
        
    ";

        $result = $this->connect->executeQuery($statement);
        $rows   = $this->convertirEnUtf8($this->connect->fetchResults($result));

        if (empty($rows)) {
            return null;
        }

        // Normalisation des clés (DB2 renvoie parfois en MAJUSCULES)
        $normalized = [];
        foreach ($rows[0] as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        return $normalized;
    }

    /**
     * Met à jour la colonne ips_scomat.sav_eor.seor_refdem
     * avec le numéro de DIT correspondant à l'OR.
     *
     * @param string $numOr        Numéro de l'OR (ex: "OR-2024-000123")
     * @param string $numeroDit    Numéro de DIT à enregistrer
     * @param string $codeSociete  Code société (filtre de sécurité)
     *
     * @return bool true si au moins une ligne a été mise à jour
     */
    public function misAjourNumeroDitSurOr(string $numOr, string $numeroDit, string $codeSociete): bool
    {
        $numOrEsc    = str_replace("'", "''", $numOr);
        $numeroDitEsc = str_replace("'", "''", $numeroDit);
        $codeSocEsc  = str_replace("'", "''", $codeSociete);
        $statement = "UPDATE {$this->dbIps}.sav_eor
                         SET seor_refdem = '{$numeroDitEsc}'
                       WHERE seor_numor = '{$numOrEsc}'
                         AND seor_soc   = '{$codeSocEsc}'";
        $this->connect->executeQuery($statement);

        return true;
    }
}
