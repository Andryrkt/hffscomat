<?php

namespace App\Controller\Atelier\Dit\DossierDit;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\dw\dossierInterventionAtelierModel;
use App\Form\dw\DossierInterventionAtelierSearchType;
use App\Service\security\SecurityService;

/**
 * @Route("/atelier/demande-intervention")
 */
class DossierInterventionAtelierController extends Controller
{
    /**
     * @Route("/dit-dossier-intervention-atelier", name="dit_dossier_intervention_atelier")
     *
     * @return void
     */
    public function dossierInterventionAtelier(Request $request)
    {
        $form = $this->getFormFactory()->createBuilder(DossierInterventionAtelierSearchType::class, null, ['method' => 'GET'])->getForm();

        $dwModel = new dossierInterventionAtelierModel();

        $dwDits = []; // Initialisation du tableau pour les demandes d'intervention
        $criteria = [
            "idMateriel"       => null,
            "typeIntervention" => "INTERNE",
            "dateDebut"        => null,
            "dateFin"          => null,
            "numParc"          => null,
            "numSerie"         => null,
            "numDit"           => null,
            "numOr"            => null,
            "designation"      => null,
        ];

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria = $form->getData();
            $dwDits = $this->ajoutNbDoc($dwModel, $criteria);
        }

        $this->logUserVisit('dit_dossier_intervention_atelier'); // historisation du page visité par l'utilisateur

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

        $this->logUserVisit('dw_interv_ate_avec_dit', [
            'numDit' => $numDit,
        ]); // historisation du page visité par l'utilisateur

        return $this->render('atelier/dit/dossierDit/dwIntervAteAvecDit.html.twig', [
            'numDit' => $numDit,
            'data'   => $data,
        ]);
    }

    public function ajoutNbDoc(dossierInterventionAtelierModel $dwModel, $criteria)
    {
        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        $dwDits = $dwModel->findAllDwDit($criteria, $this->getSecurityService()->getCodeAgenceUser(), $multisuccursale);

        $dwfac = $dwRi = $dwCde = $dwBc = $dwDev = [];

        for ($i = 0; $i < count($dwDits); $i++) {
            $numDit = $dwDits[$i]['numero_dit_intervention'];
            // Récupérer les données de la demande d'intervention et de l'ordre de réparation
            $dwDit = $dwModel->findDwDit($numDit) ?? [];
            $dwOr  = $dwModel->findDwOr($numDit) ?? [];

            // Si un ordre de réparation est trouvé, récupérer les autres données liées
            if (!empty($dwOr)) {
                $numeroDocOr = $dwOr[0]['numero_doc'];
                $dwfac   = $dwModel->findDwFac($numeroDocOr) ?? [];
                $dwRi    = $dwModel->findDwRi($numeroDocOr) ?? [];
                $dwCde   = $dwModel->findDwCde($numeroDocOr) ?? [];
            }
            $dwBc  = $dwModel->findDwBc($dwDit[0]['numero_doc']) ?? [];
            $dwDev = $dwModel->findDwDev($dwDit[0]['numero_doc']) ?? [];

            // Fusionner toutes les données dans un tableau associatif
            $data = array_merge($dwDit, $dwOr, $dwfac, $dwRi, $dwCde, $dwBc, $dwDev);

            // Ajouter le nombre de documents à l'élément actuel de $dwDits
            $dwDits[$i]['nbDoc'] = count($data);
        }

        return $dwDits;
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
