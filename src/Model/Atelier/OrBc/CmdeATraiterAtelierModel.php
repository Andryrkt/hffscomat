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

        $statement = "
            SELECT SKIP $skip FIRST $perPage *
            FROM ($unionQuery) t
            WHERE 1 = 1
            $conditions
            ORDER BY date_doc DESC, numero_doc ASC, numero_ligne ASC
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

    private function creerJointureQuery(): string
    {
        return "
            SELECT 'OR' AS type_doc,
                   seor_dateor   AS date_doc,
                   slor_numor    AS numero_doc,
                   slor_nolign   AS numero_ligne,
                   slor_constp   AS constructeur,
                   slor_refp     AS reference,
                   slor_desi     AS designation,
                   CASE WHEN slor_typlig = 'P'
                        THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                        WHEN slor_typlig IN ('F','M','U','C') THEN slor_qterea
                        ELSE 0 END AS qte_demandee,
                   slor_qterel    AS qte_reliquat,
                   slor_datealloc AS date_alloc,
                   slor_qteres    AS qte_reservee
            FROM sav_lor
            INNER JOIN sav_eor
                ON seor_soc  = slor_soc
               AND seor_succ = slor_succ
               AND seor_numor = slor_numor
            WHERE NVL(slor_numcf, 0) = 0
              AND slor_constp = 'ATC'
              AND slor_qterel > 0
              AND seor_serv <> 'DEV'
              AND slor_nolign <> NVL(slor_nolmait, 0)

            UNION ALL

            SELECT 'BC' AS type_doc,
                   nlig_datecde   AS date_doc,
                   nlig_numcde    AS numero_doc,
                   nlig_nolign    AS numero_ligne,
                   nlig_constp    AS constructeur,
                   nlig_refp      AS reference,
                   nlig_desi      AS designation,
                   nlig_qtecde    AS qte_demandee,
                   nlig_qtecde - (nlig_qtealiv + nlig_qteliv + nlig_qtefac) AS qte_reliquat,
                   nlig_datealloc AS date_alloc,
                   nlig_qtealiv   AS qte_reservee
            FROM neg_lig
            INNER JOIN neg_ent
                ON nent_soc  = nlig_soc
               AND nent_succ = nlig_succ
               AND nent_numcde = nlig_numcde
            WHERE nlig_succ = '01'
              AND nlig_constp = 'CAT'
              AND nlig_qtecde > 0
              AND nlig_qtecde > (nlig_qtealiv + nlig_qteliv + nlig_qtefac)
              AND nlig_qtewait = 0
              AND NVL(nlig_numcf, 0) = 0
              AND nlig_numcli < 1990000
              AND nlig_nolign <> NVL(nlig_nolmait, 0)
              AND nlig_natop <> 'DEV'
        ";
    }
}
