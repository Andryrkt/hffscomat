<?php

namespace App\Controller\Magasin\Commande\Soumission;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\magasin\Commande\Soumission\CdeFrnSoumissionService;
use App\Form\magasin\Commande\SoumissionCommande\SoumissionCommandeType;

/**
 * @Route("/magasin/commande")
 */
class SoumissionCommandeController extends Controller
{
    private CdeFrnSoumissionService $cdeFrnSoumissionService;

    public function __construct(CdeFrnSoumissionService $cdeFrnSoumissionService)
    {
        parent::__construct();
        $this->cdeFrnSoumissionService = $cdeFrnSoumissionService;
    }

    /**
     * @Route("/generer-commande-fournisseur", name="generer_commande_fournisseur")
     */
    public function soumissionCommande(Request $request)
    {
        $form = $this->getFormFactory()->createBuilder(SoumissionCommandeType::class, null, [
            'method' => 'POST',
        ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $this->cdeFrnSoumissionService->soumettre($this->getUserMail(), $formData["numCmde"], $formData["generationToken"], $formData["piecesJointesPdf"] ?? [], $this->getUserName());
        }

        $this->logUserVisit('generer_commande_fournisseur');

        return $this->render('magasin/commande/soumission/soumissionCommandeFournisseur.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
