<?php

namespace App\Repository\admin\historisation\documentOperation;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as DoctrinePaginator;
use App\Entity\admin\historisation\documentOperation\HistoriqueOperationEtape;
use App\Entity\admin\historisation\documentOperation\HistoriqueOperationDocument;

class HistoriqueOperationEtapeRepository extends EntityRepository
{
    /**
     * Opérations ayant un suivi par étape, filtrées et paginées
     *
     * @param array $filtres clés possibles : dateDebut, dateFin (\DateTime), statut, numeroDocument, typeDocument (id), etape
     */
    public function findOperationsPaginees(array $filtres, int $page = 1, int $limit = 50): array
    {
        $queryBuilder = $this->getEntityManager()->createQueryBuilder()
            ->select('h', 'td', 'tp')
            ->from(HistoriqueOperationDocument::class, 'h')
            ->leftJoin('h.idTypeDocument', 'td')
            ->leftJoin('h.idTypeOperation', 'tp')
            ->andWhere('EXISTS (SELECT 1 FROM ' . HistoriqueOperationEtape::class . ' e0 WHERE e0.historiqueOperation = h)');

        $this->appliquerFiltresOperation($queryBuilder, $filtres);

        // filtre sur une étape précise : opérations où cette étape a été exécutée (et en échec si statut = Echec)
        if (!empty($filtres['etape'])) {
            $sousRequete = 'SELECT 1 FROM ' . HistoriqueOperationEtape::class . ' e1 WHERE e1.historiqueOperation = h AND e1.etape = :etape';
            if (($filtres['statut'] ?? '') === HistoriqueOperationEtape::STATUT_ECHEC) {
                $sousRequete .= ' AND e1.statut = :statutEtape';
                $queryBuilder->setParameter('statutEtape', HistoriqueOperationEtape::STATUT_ECHEC);
            }
            $queryBuilder->andWhere("EXISTS ($sousRequete)")->setParameter('etape', $filtres['etape']);
        }

        $queryBuilder
            ->orderBy('h.id', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        $paginator = new DoctrinePaginator($queryBuilder->getQuery());
        $totalItems = count($paginator);

        return [
            'data'        => iterator_to_array($paginator->getIterator()),
            'totalItems'  => $totalItems,
            'currentPage' => $page,
            'lastPage'    => (int) ceil($totalItems / $limit),
        ];
    }

    /**
     * Étapes des opérations données, regroupées par id d'opération et triées par ordre
     *
     * @param int[] $idsOperation
     * @return array<int, HistoriqueOperationEtape[]>
     */
    public function findEtapesParOperation(array $idsOperation): array
    {
        if (empty($idsOperation)) return [];

        /** @var HistoriqueOperationEtape[] $etapes */
        $etapes = $this->createQueryBuilder('e')
            ->andWhere('e.historiqueOperation IN (:ids)')
            ->setParameter('ids', $idsOperation)
            ->orderBy('e.historiqueOperation', 'DESC')
            ->addOrderBy('e.ordre', 'ASC')
            ->getQuery()
            ->getResult();

        $etapesParOperation = [];
        foreach ($etapes as $etape) {
            $etapesParOperation[$etape->getHistoriqueOperation()->getId()][] = $etape;
        }

        return $etapesParOperation;
    }

    /**
     * Statistiques par étape (exécutions, échecs, durées) sur les opérations filtrées
     */
    public function statistiquesParEtape(array $filtres): array
    {
        $queryBuilder = $this->createQueryBuilder('e')
            ->select(
                'e.etape',
                'COUNT(e.id) AS nombreExecutions',
                "SUM(CASE WHEN e.statut = :echec THEN 1 ELSE 0 END) AS nombreEchecs",
                'AVG(e.dureeMs) AS dureeMoyenneMs',
                'MAX(e.dureeMs) AS dureeMaxMs',
                'MAX(e.dateEtape) AS dernierPassage'
            )
            ->innerJoin('e.historiqueOperation', 'h')
            ->setParameter('echec', HistoriqueOperationEtape::STATUT_ECHEC)
            ->groupBy('e.etape')
            ->orderBy('nombreEchecs', 'DESC')
            ->addOrderBy('e.etape', 'ASC');

        $this->appliquerFiltresOperation($queryBuilder, $filtres);

        return $queryBuilder->getQuery()->getArrayResult();
    }

    /**
     * Noms des étapes déjà enregistrées (pour la liste de choix du filtre)
     *
     * @return string[]
     */
    public function findNomsEtapes(): array
    {
        $resultats = $this->createQueryBuilder('e')
            ->select('DISTINCT e.etape')
            ->orderBy('e.etape', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($resultats, 'etape');
    }

    /**
     * Filtres portant sur l'opération (alias h)
     */
    private function appliquerFiltresOperation(QueryBuilder $queryBuilder, array $filtres): void
    {
        if (!empty($filtres['dateDebut'])) {
            $queryBuilder->andWhere('h.dateOperation >= :dateDebut')
                ->setParameter('dateDebut', (clone $filtres['dateDebut'])->setTime(0, 0));
        }

        if (!empty($filtres['dateFin'])) {
            $queryBuilder->andWhere('h.dateOperation < :dateFin')
                ->setParameter('dateFin', (clone $filtres['dateFin'])->setTime(0, 0)->modify('+1 day'));
        }

        if (!empty($filtres['statut'])) {
            $queryBuilder->andWhere('h.statutOperation = :statut')
                ->setParameter('statut', $filtres['statut']);
        }

        if (!empty($filtres['numeroDocument'])) {
            $queryBuilder->andWhere('h.numeroDocument LIKE :numeroDocument')
                ->setParameter('numeroDocument', '%' . $filtres['numeroDocument'] . '%');
        }

        if (!empty($filtres['typeDocument'])) {
            $queryBuilder->andWhere('IDENTITY(h.idTypeDocument) = :typeDocument')
                ->setParameter('typeDocument', (int) $filtres['typeDocument']);
        }
    }
}
