<?php

namespace App\Model\Atelier\CVA;


use App\Dto\Atelier\CVA\OrCsaATraiterSearchDto;
use App\Model\Model;
use App\Service\Atelier\CVA\FiltreService;
use App\Service\security\SecurityService;




class OrCsaATraiterListeModel extends Model
{
    private FiltreService $filtreService;


    public function __construct(
        SecurityService $securityService
    ) {
        parent::__construct();
        $this->filtreService = new FiltreService($securityService);
    }

    public function findPaginatedAndFiltered(string $codeSociete, OrCsaATraiterSearchDto $orCsaATraiterSearchDto, int $page, int $perPage)
    {
        // Calculer le SKIP
        $skip = ($page - 1) * $perPage;

        $conditions = $this->filtreService->filtre($orCsaATraiterSearchDto);

        // $conditionsMultisucursal = $this->filtreService->conditionAgenceService();
        $conditionsMultisucursal = '';

        $statement = "SELECT DISTINCT
                        seor_numor,
                        seor_numcli || ' - ' || seor_nomcli as client,
                        seor_lib as reference_client,
                        seor_typeor || ' - ' || aces_lib as type_or,
                        seor_delai as lead_time,
                        (seor_delai - TODAY) as nb_jours_dispo,
                        CAST((seor_delai - TODAY) as varchar(10)) || ' j' as nb_jours_dispo_lib,
                        CASE
                            WHEN (seor_delai - TODAY) < 90 then 'Retard de ' || cast(90 - (seor_delai - TODAY) as varchar(10)) || ' j'
                            WHEN (seor_delai - TODAY) = 90 then 'J-90 aujourd''hui'
                            else 'J-90 dans ' || cast((seor_delai - TODAY) - 90 as varchar(10)) || ' j'
                        END as statut_allocation,
                        CASE
                            WHEN (seor_delai - TODAY) < 90 then 'RETARD'
                            WHEN (seor_delai - TODAY) <= 100 then 'A_ALLOUER'
                            else 'A_VENIR'
                        END as code_couleur
                        FROM
                        sav_eor
                        INNER JOIN agr_ces ON aces_soc = seor_soc
                        AND aces_nat = seor_typeor
                        INNER JOIN sav_lor ON slor_soc = seor_soc
                        AND slor_succ = seor_succ
                        AND slor_numor = seor_numor
                        WHERE
                        seor_typeor in ('222', '229')
                        AND year (seor_dateor) >= 2026
                        AND slor_qtewait > 0 -- qte en attente d'allocation
                        AND seor_delai is not null
                        --AND (seor_delai - TODAY) between 80 AND 100
                        ORDER BY
                        5;           
                     ";
        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

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


    /**
     * compte le nombre de ligne recupérer
     *
     * @return integer
     */
    private function compteNombreItem(string $codeSociete, string $conditions, string $conditionsMultisucursal): int
    {
        $countStatement = "
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

        $statusStatement = "
        ";

        $statusResult = $this->connect->executeQuery($statusStatement);
        $statusData = $this->connect->fetchResults($statusResult);
        $statusCounts = [];
        foreach ($statusData as $status) {
            $statusCounts[$status['description']] = $status['count'];
        }

        return $statusCounts;
    }
}
