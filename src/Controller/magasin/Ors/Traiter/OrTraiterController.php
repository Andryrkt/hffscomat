<?php

namespace App\Controller\magasin\Ors\Traiter;

use App\Controller\Controller;
use App\Model\magasin\Ors\Traiter\OrTraiterModel;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/or")
 */
class OrTraiterController extends Controller
{
    /**
     * @Route("/liste-magasin", name="magasinListe_index")
     *
     * @return void
     */
    public function index()
    {
        $orTraiterModel = new OrTraiterModel();
        $data = $orTraiterModel->recupereListeMaterielValider();

        return $this->render('magasin/ors/traiter/orATraiter.html.twig', [
            'data' => $data
        ]);
    }
}
