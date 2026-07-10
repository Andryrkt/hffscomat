<?php

namespace App\Controller\Magasin\Commande\Livrer;

use App\Controller\Controller;
use App\Factory\magasin\Commande\Livrer\CommandeLivrerSearchFactory;
use App\Form\magasin\Commande\Livrer\CommandeLivrerSearchType;
use App\Model\magasin\Commande\Livrer\CommandeLivrerModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/commande")
 */
class CommandeLivrerController extends Controller
{
    /**
     * @Route("/liste-commande-livrer", name="magasinListe_commande_livrer")
     */
    public function listCommandeLivrer(Request $request)
    {
        $dtoSearch = (new CommandeLivrerSearchFactory($this->getSecurityService()))->initialisationSearch();

        $form = $this->getFormFactory()->createBuilder(CommandeLivrerSearchType::class, $dtoSearch, [
            'method' => 'GET'
        ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoSearch = $form->getData();
            //enregistrer les critère de recherche dans la session
            $this->getSessionService()->set('magasin_liste_commande_livrer_search_criteria', $dtoSearch);
        }

        $commandeLivrerModel = new CommandeLivrerModel();
        $data = $commandeLivrerModel->recupereListeCommandeLivrer($dtoSearch);

        return $this->render('magasin/commande/livrer/commandeLivrer.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }
}
