<?php

namespace App\Controller\planningMagasin;

use App\Controller\Controller;
use App\Entity\magasin\bc\BcMagasin;
use App\Service\TableauEnStringService;
use App\Controller\Traits\PlanningTraits;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\planningMagasin\PlanningMagasinModel;
use App\Entity\planningMagasin\PlanningMagasinSearch;
use App\Form\planningMagasin\PlanningMagasinSearchType;
use App\Service\security\SecurityService;

/**
 * @Route("/magasin")
 */
class planningMagasinController extends Controller
{
    use PlanningTraits;


    private PlanningMagasinModel $planningMagasinModel;
    private PlanningMagasinSearch $planningMagasinSearch;


    public function __construct()
    {
        parent::__construct();
        $this->planningMagasinModel = new PlanningMagasinModel();
        $this->planningMagasinSearch = new PlanningMagasinSearch();
    }
    /**
     * @Route("/Planning", name = "interface_planningMag")
     */
    public function headPlanning(Request $request)
    {
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        $codeAgence = $multisuccursale ? "-0" : $this->getSecurityService()->getCodeAgenceUser();
        /** FIN AUtorisation acées */
        //initialisation
        $this->planningMagasinSearch
            ->setAnnee(date('Y'))
            ->setFacture('ENCOURS')
            ->setPlan('PLANIFIE')
            ->setInterneExterne('TOUS')
            ->setTypeLigne('TOUETS')
            ->setMonths(3)
            ->setAgence($codeAgence)
            ->setCodeSociete($codeSociete)
        ;

        $form = $this->getFormFactory()->createBuilder(
            PlanningMagasinSearchType::class,
            $this->planningMagasinSearch,
            [
                'method' => 'GET'
            ]
        )->getForm();

        $form->handleRequest($request);
        //initialisation criteria
        $criteria = $this->planningMagasinSearch;
        if ($form->isSubmitted() && $form->isValid()) {
            $criteria =  $form->getdata();
        }
        //recupère le condition clicsur la légende
        $condition = $request->query->get('condition', "1");



        $data = $this->planningMagasinModel->recuperationCommadeplanifier($criteria, $condition, $codeAgence, $codeSociete);
        $tabObjetPlanning = $this->creationTableauObjetPlanningMagasin($data);
        $fusionResult = $this->ajoutMoiDetailMagasin($tabObjetPlanning);
        $forDisplay = $this->prepareDataForDisplay($fusionResult, $criteria->getMonths() == null ? 3 : $criteria->getMonths());
        return $this->render('planningMagasin/planning.html.twig', [
            'form'           => $form->createView(),
            'criteria'       => $criteria->toArray(),
            'uniqueMonths'   => $forDisplay['uniqueMonths'],
            'preparedData'   => $forDisplay['preparedData'],
        ]);
    }
}
