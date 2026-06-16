<?php

namespace App\Controller\magasin\Ors\Livrer;

use App\Controller\Controller;
use App\Factory\magasin\Ors\Livrer\OrLivrerSearchFactory;
use App\Form\magasin\Ors\Livrer\OrLivrerSearchType;
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
        $dtoSearch = (new OrLivrerSearchFactory($this->getSecurityService()))->initialisationSearch();

        $form = $this->getFormFactory()->createBuilder(OrLivrerSearchType::class, $dtoSearch, [
            'method' => 'GET'
        ])->getForm();

        $orLivrerModel = new OrLivrerModel();
        $data = $orLivrerModel->recupereListeMaterielValider();

        return $this->render('magasin/ors/livrer/orLivrer.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }
}
