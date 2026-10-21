<?php


namespace App\Model\Atelier\OrBc;


use App\Dto\Atelier\OrBc\CmdeATraitrerAtelierSearchDto;
use App\Model\Model;
use App\Service\Atelier\OrBc\CmdeATraiterAtelierFiltreService;
use App\Service\security\SecurityService;

class CmdeATraiterAtelierModel extends Model
{

    private CmdeATraiterAtelierFiltreService $cmdeATraiterAtelierFiltreService;


    public function __construct(SecurityService $securityService)
    {
        parent::__construct();
        $this->cmdeATraiterAtelierFiltreService = new CmdeATraiterAtelierFiltreService($securityService);
    }
    public function findPaginatedAndFiltered(string $codeSociete, CmdeATraitrerAtelierSearchDto $cmdeATraiterAtelierSearchDto, int $page, int $perPage)
    {
        $page  = max(1, $page);
        $skip  = ($page - 1) * $perPage;
        $conditions = $this->cmdeATraiterAtelierFiltreService->filtre($cmdeATraiterAtelierSearchDto);
        $unionQuery = $this->creerJointureQuery();

        $statement = "SELECT SKIP $skip FIRST $perPage *
            FROM ($unionQuery) t 
            WHERE 1 = 1
             $conditions
            ORDER BY date_doc DESC, numero_doc ASC, numero_ligne ASC;
            ";
        $result = $this->connect->executeQuery($statement);
        $data   = $this->convertirEnUtf8($this->connect->fetchResults($result));

        $countStatement = "
            SELECT COUNT(*) AS total
            FROM ($unionQuery) t
            WHERE 1 = 1
            $conditions
        ";
        $countResult = $this->connect->executeQuery($countStatement);
        $countData   = $this->connect->fetchResults($countResult);
        $totalItems  = (int) ($countData[0]['total'] ?? 0);

        $lastPage = $perPage > 0 ? (int) ceil($totalItems / $perPage) : 1;
        return [
            'data'        => $data,
            'totalItems'  => $totalItems,
            'currentPage' => $page,
            'lastPage'    => $lastPage,
        ];
    }
    /**
     * Récupération de toutes les données filtrées pour l'export Excel (sans pagination)
     */
    public function DonnerAAjouterExcel(CmdeATraitrerAtelierSearchDto $dto, string $codeSociete): array
    {
        // On récupère les conditions générées par ton service de filtre
        $filtreService = new \App\Service\Atelier\OrBc\CmdeATraiterAtelierFiltreService();
        $conditions = $filtreService->filtre($dto);

        // On récupère la requête UNION ALL de base
        $unionQuery = $this->creerJointureQuery();

        // On applique les filtres sans pagination
        $statement = "
            SELECT * 
            FROM ($unionQuery) t
            WHERE 1 = 1
            $conditions
            ORDER BY date_doc DESC, numero_doc ASC, numero_ligne ASC
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    private function creerJointureQuery(): string
    {
        $sucNeg = $_ENV['SUC_NEG'];
        return "
            SELECT
                    'OR-' || slor_nolign        AS id,
                    'OR'                        AS type_doc,
                    seor_dateor                 AS date_doc,
                    slor_numor                  AS numero_doc,
                    slor_nolign                 AS numero_ligne,
                    slor_constp                 AS constructeur,
                    slor_refp                   AS reference,
                    slor_desi                   AS designation,
                    CASE
                        WHEN slor_typlig = 'P'
                            THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                        WHEN slor_typlig IN ('F','M','U','C')
                            THEN slor_qterea
                        ELSE 0
                    END                         AS qte_demandee,
                    slor_qterel                 AS qte_reliquat,
                    slor_datealloc              AS date_alloc,
                    slor_qteres                 AS qte_reservee
                FROM sav_lor
                    INNER JOIN sav_eor
                                ON seor_soc  = slor_soc
                            AND seor_succ = slor_succ
                            AND seor_numor = slor_numor
                    INNER JOIN art_bse a1 ON a1.abse_constp = slor_constp
                         AND a1.abse_refp = slor_refp
                WHERE 
                    NVL(slor_numcf, 0) = 0
                    AND a1.abse_codg = 'ST'
                    AND slor_qterel > 0
                    AND seor_serv <> 'DEV'
                    AND slor_nolign <> NVL(slor_nolmait, 0)

                UNION ALL

                SELECT
                    'BC-' || nlig_nolign        AS id,
                    'BC'                        AS type_doc,
                    nlig_datecde                AS date_doc,
                    nlig_numcde                 AS numero_doc,
                    nlig_nolign                 AS numero_ligne,
                    nlig_constp                 AS constructeur,
                    nlig_refp                   AS reference,
                    nlig_desi                   AS designation,
                    nlig_qtecde                 AS qte_demandee,
                    nlig_qtecde - (nlig_qtealiv + nlig_qteliv + nlig_qtefac) AS qte_reliquat,
                    nlig_datealloc              AS date_alloc,
                    nlig_qtealiv                AS qte_reservee
                FROM 
                        neg_lig
                    INNER JOIN neg_ent
                        ON nent_soc  = nlig_soc
                    AND nent_succ = nlig_succ
                    AND nent_numcde = nlig_numcde
                    INNER JOIN art_bse a2 ON a2.abse_constp = nlig_constp
                         AND a2.abse_refp = nlig_refp
                WHERE
                    nlig_succ = '$sucNeg'
                    AND a2.abse_codg = 'ST'
                    AND nlig_qtecde > 0
                    AND nlig_qtecde > (nlig_qtealiv + nlig_qteliv + nlig_qtefac)
                    AND nlig_qtewait = 0
                    AND NVL(nlig_numcf, 0) = 0
                    AND nlig_numcli < 1990000
                    AND nlig_nolign <> NVL(nlig_nolmait,0)
                    AND nlig_natop <> 'DEV'
        ";
    }

    /**
     * Récupère les constructeurs uniquement depuis art_bse avec abse_codg = 'ST'
     */
    public function findConstructeurs(): array
    {
        $statement = "
            SELECT DISTINCT abse_constp AS constructeur
            FROM art_bse
            WHERE abse_codg = 'ST'
              AND abse_constp IS NOT NULL
              AND TRIM(abse_constp) <> ''
            ORDER BY abse_constp
        ";

        $result = $this->connect->executeQuery($statement);
        $data   = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'constructeur');
    }
}
