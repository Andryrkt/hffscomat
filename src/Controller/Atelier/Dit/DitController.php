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
        $this->ditFactory = new DitFactory($this->getEntityManager(), $this->ditModel, $this->historiqueOperation, $this->securityService);
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

    private function traitementFormulaire($form, Request $request)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DitDto $dto */
            $dto = $form->getData();


            // // 2. Enrichir le DTO avec les informations système (initialisation ou ajout des info par defaut)
            // $em = $this->getEntityManager();



            // /**   @var DemandeIntervention[] $demandeInterventions 3. Utiliser la factory pour créer l'entité complète*/
            // $demandeInterventions = $this->createDemandeInterventionFromDto($dto);

            // foreach ($demandeInterventions as $demandeIntervention) {
            //     // Type de DIT
            //     $ditPneumatique = $demandeIntervention->getReparationRealise() === "ATE POL TANA";

            //     // 4. recuperation du dernière numero demande d'intervention et generation du numero de demande 
            //     $application = $em->getRepository(Application::class)->findOneBy(['codeApp' => DemandeIntervention::CODE_APP]);
            //     $numeroDemandeIntervention = $this->genererNumeroDemandeIntervention($application);

            //     // 5.enregistrement du numero demande d'intervention et Modifie la colonne dernière_id dans la table applications
            //     $demandeIntervention->setNumeroDemandeIntervention($numeroDemandeIntervention);
            //     AutoIncDecService::mettreAJourDerniereIdApplication($application, $em, $numeroDemandeIntervention);

            //     /** 6. Traitement des fichiers (PDF, pièces jointes) @var array $nomFichierEnregistrer @var string $nomFichier  */
            //     $genererPdfDit = new GenererPdfDit();
            //     [$nomFichierEnregistrer, $nomFichier]  = $this->traitementDeFichier($form, $demandeIntervention, $genererPdfDit, $ditPneumatique);

            //     // 7. Enregistrement dans la base de donnée
            //     $this->enregistrementBd($demandeIntervention, $nomFichierEnregistrer);

            //     // 8.Copier le PDF DANS DOXCUWARE
            //     $reponse = $genererPdfDit->copyToDOCUWARE($nomFichier, $demandeIntervention->getNumeroDemandeIntervention(), $ditPneumatique);

            //     // 9. modification de la colonne pdf_deposer_dw et date_depot_pdf_dw
            //     $this->modificationBdPourHitorisationDw($em, $demandeIntervention, $reponse);
            // }

            // // 10. enregistrement dans l'historisation de la sucès de la demande
            // $this->historiqueOperation->sendNotificationCreation('Votre demande a été enregistrée', $demandeInterventions[0]->getNumeroDemandeIntervention(), 'dit_index', true);
        }
    }
}
