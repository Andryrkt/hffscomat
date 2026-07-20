<?php

namespace App\Controller\Magasin\Commande\Traiter;

use App\Controller\Controller;
use App\Factory\magasin\Commande\Traiter\CommandeTraiterSearchFactory;
use App\Form\magasin\Commande\Traiter\CommandeTraiterSearchType;
use App\Model\magasin\Commande\Traiter\CommandeTraiterModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/commande")
 */
class CommandeTraiterController extends Controller
{
    /**
     * @Route("/liste-commande-traiter", name="magasinListe_commande_traiter")
     */
    public function listCommandeTraiter(Request $request)
    {
        $dtoSearch = (new CommandeTraiterSearchFactory($this->getSecurityService()))->initialisationSearch();

        $form = $this->getFormFactory()->createBuilder(CommandeTraiterSearchType::class, $dtoSearch, [
            'method' => 'GET'
        ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dtoSearch = $form->getData();
            //enregistrer les critère de recherche dans la session
            $this->getSessionService()->set('magasin_liste_or_livrer_search_criteria', $dtoSearch);
        }

        $commandeTraiterModel = new CommandeTraiterModel();
        $data = $commandeTraiterModel->recupereListeCommandeTraiter($dtoSearch);

        return $this->render('magasin/commande/traiter/commandeATraiter.html.twig', [
            'data' => $data,
            'form' => $form->createView()
        ]);
    }
}
