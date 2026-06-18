<?php

namespace App\Controller\magasin\Ors\Traiter;

use App\Controller\Controller;
use App\Factory\magasin\Ors\Traiter\OrATraiterSearchFactory;
use App\Form\magasin\Ors\Traiter\OrATraiterSearchType;
use App\Model\magasin\Ors\Traiter\OrTraiterModel;
use Symfony\Component\HttpFoundation\Request;
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
    public function index(Request $request)
    {

        $dtoSearch = (new OrATraiterSearchFactory($this->getSecurityService()))->initialisationSearch();

        $form = $this->getFormFactory()->createBuilder(OrATraiterSearchType::class, $dtoSearch, [
            'method' => 'GET'
        ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoSearch = $form->getData();

            //enregistrer les critère de recherche dans la session
            $this->getSessionService()->set('magasin_liste_or_traiter_search_criteria', $dtoSearch);
        }

        $orTraiterModel = new OrTraiterModel();
        $data = $orTraiterModel->recupereListeMaterielValider($dtoSearch);

        return $this->render('magasin/ors/traiter/orATraiter.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }
}
