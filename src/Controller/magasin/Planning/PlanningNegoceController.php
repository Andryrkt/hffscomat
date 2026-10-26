<?php

namespace App\Controller\magasin\Planning;

use App\Controller\Controller;
use App\Controller\Traits\PlanningTraits;
use App\Service\security\SecurityService;
use App\Entity\planning\PlanningNegoceSearch;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\magasin\planning\PlanningNegoceModel;
use App\Form\magasin\Planning\PlanningNegoceSearchType;

/**
 * @Route("/magasin")
 */
class PlanningNegoceController extends Controller
{
    use PlanningTraits;

    private PlanningNegoceModel $planningMagasinModel;
    private PlanningNegoceSearch $planningNegoceSearch;

    public function __construct()
    {
        parent::__construct();
        $this->planningMagasinModel = new PlanningNegoceModel();
        $this->planningNegoceSearch = new PlanningNegoceSearch();
    }
    /**
     * @Route("/planning-negoce", name = "interface_planningMag")
     */
    public function headPlanning(Request $request)
    {
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        $codeAgence = $multisuccursale ? "-0" : $this->getSecurityService()->getCodeAgenceUser();
        /** FIN AUtorisation acées */

        // Tentative de récupération des critères depuis la session
        $sessionCriteria = $this->getSessionService()->get('criteria_for_search_planning_devis_neg');

        if ($sessionCriteria instanceof PlanningNegoceSearch && $sessionCriteria->getCodeSociete() === $codeSociete) {
            $this->planningNegoceSearch = $sessionCriteria;
        } else {
            //initialisation par défaut si pas de session ou changement de société
            $this->planningNegoceSearch
                ->setAnnee(date('Y'))
                ->setFacture('ENCOURS')
                ->setPlan('PLANIFIE')
                ->setInterneExterne('TOUS')
                ->setTypeLigne('TOUETS')
                ->setMonths(3)
                ->setAgence($codeAgence)
                ->setCodeSociete($codeSociete)
            ;
        }

        $form = $this->getFormFactory()->createBuilder(
            PlanningNegoceSearchType::class,
            $this->planningNegoceSearch,
            [
                'method' => 'GET'
            ]
        )->getForm();

        $form->handleRequest($request);
        //initialisation criteria
        $criteria = $this->planningNegoceSearch;

        if ($form->isSubmitted() && $form->isValid()) {
            $criteria =  $form->getData();
            $this->getSessionService()->set('criteria_for_search_planning_devis_neg', $criteria);
        }
        //recupère le condition clicsur la légende
        $condition = $request->query->get('condition', "1");

        $numeroDevisValideBcClient = $this->planningMagasinModel->getNumeroDevisValideBcClient();


        $data = $this->planningMagasinModel->recuperationCommadeplanifier($criteria, $condition, $codeAgence, $codeSociete, $numeroDevisValideBcClient);
        $tabObjetPlanning = $this->creationTableauObjetPlanningMagasin($data);
        $fusionResult = $this->ajoutMoiDetailMagasin($tabObjetPlanning);
        $forDisplay = $this->prepareDataForDisplay($fusionResult, $criteria->getMonths() == null ? 3 : $criteria->getMonths());
        return $this->render('magasin/planning/negoce.html.twig', [
            'form'           => $form->createView(),
            'criteria'       => $criteria->toArray(),
            'uniqueMonths'   => $forDisplay['uniqueMonths'],
            'preparedData'   => $forDisplay['preparedData'],
        ]);
    }
}
