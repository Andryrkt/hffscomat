<?php

namespace App\Controller\Magasin\Commande\Soumission;

use App\Controller\Controller;
use App\Form\magasin\Commande\SoumissionCommande\SoumissionCommandeType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/magasin/commande")
 */
class SoumissionCommandeController extends Controller
{
    /**
     * @Route("/generer-commande-fournisseur", name="generer_commande_fournisseur")
     */
    public function soumissionCommande(Request $request)
    {
        $codeSociette = $this->getSecurityService()->getCodeSocieteUser();
        $form = $this->getFormFactory()
            ->createBuilder(SoumissionCommandeType::class, null, [
                'method' => 'POST',
            ])
            ->getForm();

        $form->handleRequest($request);

        $this->logUserVisit('generer_commande_fournisseur');
        if ($form->isSubmitted()) {
            $this->soumettreAValider($form);
        }


        return $this->render('magasin/commande/soumission/soumissionCommandeFournisseur.html.twig', [
            'form' => $form->createView()
        ]);
    }


    public function soumettreAValider(FormInterface $form)
    {
        // Test
        dump("Soummettre a validation");
        die();

        // Model model = new Model();

        // Check if it not null 

        // if not null -> Make getted data to DTO

        // generate PDF

        // return PDF to frontEnd

        //  if null -> throw errors 

    }
}
