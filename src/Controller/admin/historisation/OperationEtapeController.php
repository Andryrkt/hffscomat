<?php

namespace App\Controller\admin\historisation;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\admin\historisation\documentOperation\TypeDocument;
use App\Entity\admin\historisation\documentOperation\HistoriqueOperationEtape;
use App\Repository\admin\historisation\documentOperation\HistoriqueOperationEtapeRepository;

/**
 * Visualisation du suivi par étape des opérations (upload, fusion, BD, DocuWare, mail...)
 */
class OperationEtapeController extends Controller
{
    private const NOMBRE_JOURS_PAR_DEFAUT = 7;

    /**
     * @Route("/admin/operation-etape", name="operation_etape_index")
     */
    public function index(Request $request)
    {
        $filtres = $this->getFiltres($request);

        //recupère le numero de page
        $page = max(1, $request->query->getInt('page', 1));
        //nombre de ligne par page
        $limit = 50;

        /** @var HistoriqueOperationEtapeRepository $repository */
        $repository = $this->getEntityManager()->getRepository(HistoriqueOperationEtape::class);

        $paginationData = $repository->findOperationsPaginees($filtres, $page, $limit);
        $idsOperation = array_map(fn($operation) => $operation->getId(), $paginationData['data']);

        return $this->render('admin/historisation/operation-etape/index.html.twig', [
            'data'               => $paginationData['data'],
            'etapesParOperation' => $repository->findEtapesParOperation($idsOperation),
            'statistiques'       => $repository->statistiquesParEtape($filtres),
            'nomsEtapes'         => $repository->findNomsEtapes(),
            'typesDocument'      => $this->getEntityManager()->getRepository(TypeDocument::class)->findBy([], ['libelleDocument' => 'ASC']),
            'filtres'            => $filtres,
            'criteria'           => $this->filtresPourUrl($filtres),
            'currentPage'        => $paginationData['currentPage'],
            'totalPages'         => $paginationData['lastPage'],
            'resultat'           => $paginationData['totalItems'],
        ]);
    }

    /**
     * Lecture des filtres de la recherche (GET), par défaut les 7 derniers jours
     */
    private function getFiltres(Request $request): array
    {
        $dateDebut = $this->convertirDate($request->query->get('dateDebut'));
        $dateFin   = $this->convertirDate($request->query->get('dateFin'));

        if (!$request->query->has('dateDebut') && !$request->query->has('dateFin')) {
            $dateDebut = new \DateTime('-' . self::NOMBRE_JOURS_PAR_DEFAUT . ' days');
            $dateFin   = new \DateTime();
        }

        return [
            'dateDebut'      => $dateDebut,
            'dateFin'        => $dateFin,
            'statut'         => in_array($request->query->get('statut'), [HistoriqueOperationEtape::STATUT_SUCCES, HistoriqueOperationEtape::STATUT_ECHEC], true) ? $request->query->get('statut') : '',
            'numeroDocument' => trim((string) $request->query->get('numeroDocument', '')),
            'typeDocument'   => $request->query->getInt('typeDocument') ?: null,
            'etape'          => trim((string) $request->query->get('etape', '')),
        ];
    }

    private function convertirDate(?string $date): ?\DateTime
    {
        if (empty($date)) return null;

        return \DateTime::createFromFormat('!Y-m-d', $date) ?: null;
    }

    /**
     * Filtres sous forme de paramètres GET (pour la pagination)
     */
    private function filtresPourUrl(array $filtres): array
    {
        return array_filter([
            'dateDebut'      => $filtres['dateDebut'] ? $filtres['dateDebut']->format('Y-m-d') : '',
            'dateFin'        => $filtres['dateFin'] ? $filtres['dateFin']->format('Y-m-d') : '',
            'statut'         => $filtres['statut'],
            'numeroDocument' => $filtres['numeroDocument'],
            'typeDocument'   => $filtres['typeDocument'],
            'etape'          => $filtres['etape'],
        ], fn($valeur) => $valeur !== '' && $valeur !== null);
    }
}
