<?php

namespace App\Controller\Atelier\Dit\DossierDit;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\dw\dossierInterventionAtelierModel;
use App\Form\dw\DossierInterventionAtelierSearchType;
use App\Service\Atelier\DossierDit\DossierDitService;
use App\Dto\Atelier\Dit\DossierDit\DossierInterventionAtelierSearchDto;

/**
 * @Route("/atelier/demande-intervention")
 */
class DossierInterventionAtelierController extends Controller
{
    private DossierDitService $service;

    public function __construct()
    {
        parent::__construct();
        $this->service = new DossierDitService();
    }

    /**
     * @Route("/dit-dossier-intervention-atelier", name="dit_dossier_intervention_atelier")
     *
     * @return void
     */
    public function dossierInterventionAtelier(Request $request)
    {
        $dwDits = [];
        $form = $this->getFormFactory()->createBuilder(DossierInterventionAtelierSearchType::class, null, ['method' => 'GET'])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DossierInterventionAtelierSearchDto $dossierInterventionAtelierSearchDto */
            $dossierInterventionAtelierSearchDto = $form->getData();
            $dwDits = $this->service->getFilteredDwDit($dossierInterventionAtelierSearchDto);
        }

        return $this->render('atelier/dit/dossierDit/dossierInterventionAtelier.html.twig', [
            'form'   => $form->createView(),
            'dwDits' => $dwDits
        ]);
    }

    /**
     * @Route("/dw-intervention-atelier-avec-dit/{numDit}", name="dw_interv_ate_avec_dit")
     */
    public function dwintervAteAvecDit(string $numDit)
    {
        return $this->render('atelier/dit/dossierDit/dwIntervAteAvecDit.html.twig', [
            'numDit' => $numDit,
            'data'   => $this->service->getDwDocs($numDit),
        ]);
    }
}
