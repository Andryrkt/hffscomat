<?php

namespace App\Controller\magasin\Ors\Livrer;

use App\Controller\Controller;
use App\Model\magasin\Ors\Livrer\OrLivrerModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/or")
 */
class OrLivrerController extends Controller
{
    /**
     * @Route("/liste-or-livrer", name="magasinListe_or_Livrer")
     */
    public function listOrLivrer(Request $request)
    {
        $orLivrerModel = new OrLivrerModel();

        $data = $orLivrerModel->recupereListeMaterielValider();

        return $this->render('magasin/ors/livrer/orLivrer.html.twig', [
            'data' => $data
        ]);
    }
}
