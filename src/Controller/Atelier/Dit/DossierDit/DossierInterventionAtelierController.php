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
        $dwModel = new dossierInterventionAtelierModel();

        // Récupération initiale : Demande d'intervention
        $dwDit = $this->fetchAndLabel($dwModel, 'findDwDit', $numDit, "Demande d'intervention");

        // Ordre de réparation et documents liés
        $dwOr = $this->fetchAndLabel($dwModel, 'findDwOr', $numDit, "Ordre de réparation");
        $dwFac = $dwRi = $dwCde = [];

        // Si un ordre de réparation est trouvé, récupérer les autres données liées
        if (!empty($dwOr)) {
            $numeroDocOr = $dwOr[0]['numero_doc'];
            $dwFac   = $this->fetchAndLabel($dwModel, 'findDwFac',   $numeroDocOr, "Facture");
            $dwRi    = $this->fetchAndLabel($dwModel, 'findDwRi',    $numeroDocOr, "Rapport d'intervention");
            $dwCde   = $this->fetchAndLabel($dwModel, 'findDwCde',   $numeroDocOr, "Commande");
        }

        // Documents liés à la demande d'intervention
        $dwBc  = !empty($dwDit) ? $this->fetchAndLabel($dwModel, 'findDwBc',  $dwDit[0]['numero_doc'], "Bon de Commande Client") : [];
        $dwDev = !empty($dwDit) ? $this->fetchAndLabel($dwModel, 'findDwDev', $dwDit[0]['numero_doc'], "Devis") : [];

        // Fusionner toutes les données
        $data = array_merge($dwDit, $dwOr, $dwFac, $dwRi, $dwCde, $dwBc, $dwDev);

        return $this->render('atelier/dit/dossierDit/dwIntervAteAvecDit.html.twig', [
            'numDit' => $numDit,
            'data'   => $data,
        ]);
    }

    /**
     * Méthode utilitaire pour récupérer et étiqueter des documents
     */
    private function fetchAndLabel($model, string $method, $param, string $label): array
    {
        $items = $model->$method($param) ?? [];
        foreach ($items as &$item) {
            $item['nomDoc'] = $label;
        }
        return $items;
    }
}
