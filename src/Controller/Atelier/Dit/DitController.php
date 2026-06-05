<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use App\Controller\Traits\FormatageTrait;
use App\Controller\Traits\PdfConversionTrait;
use App\Factory\Atelier\Dit\DitFactory;
use App\Form\Atelier\Dit\DitType;
use App\Model\Atelier\Dit\DitModel;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/dit/demande-intervention")
 */
class DitController extends Controller
{
    use FormatageTrait;
    use PdfConversionTrait;


    private HistoriqueOperationDITService $historiqueOperation;
    private DitFactory $ditFactory;
    private DitModel $ditModel;

    public function __construct()
    {
        parent::__construct();
        $this->historiqueOperation = new HistoriqueOperationDITService($this->getEntityManager());
        $this->ditModel = new DitModel();
        $this->ditFactory = new DitFactory($this->getEntityManager(), $this->ditModel, $this->historiqueOperation);
    }

    /**
     * @Route("/new", name="dit_new")
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function new(Request $request)
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        // recupération de l'agence et service par defaut de l'utilisateur
        $agenceService = $this->agenceServiceIpsObjet();

        //INITIALISATION DU FORMULAIRE
        $dto = $this->ditFactory->initialisation($agenceService, $codeSociete);

        //AFFICHAGE ET TRAITEMENT DU FORMULAIRE
        $form = $this->getFormFactory()->createBuilder(DitType::class, $dto)->getForm();
        // $this->traitementFormulaire($form, $request);

        $this->logUserVisit('dit_new'); // historisation du page visité par l'utilisateur

        return $this->render('atelier/dit/new.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
