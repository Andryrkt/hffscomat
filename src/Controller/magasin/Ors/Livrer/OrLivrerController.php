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

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoSearch = $form->getData();
            dd($dtoSearch);
            //enregistrer les critère de recherche dans la session
            $this->getSessionService()->set('magasin_liste_or_livrer_search_criteria', $dtoSearch);
        }

        $orLivrerModel = new OrLivrerModel();
        $data = $orLivrerModel->recupereListeMaterielValider($dtoSearch);

        return $this->render('magasin/ors/livrer/orLivrer.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }
}
