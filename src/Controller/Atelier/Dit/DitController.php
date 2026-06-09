<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use App\Controller\Traits\FormatageTrait;
use App\Controller\Traits\PdfConversionTrait;
use App\Dto\Atelier\Dit\DitDto;
use App\Factory\Atelier\Dit\DitFactory;
use App\Form\Atelier\Dit\DitType;
use App\Mapper\Atelier\Dit\DitMapper;
use App\Model\Atelier\Dit\DitModel;
use App\Service\atelier\dit\TraitementFichierService;
use App\Service\genererPdf\dit\GenererPdfDit;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use Symfony\Component\Form\FormInterface;
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
        $this->ditFactory = new DitFactory($this->getSecurityService(), $this->getEntityManager());
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
        $this->traitementFormulaire($form, $request);

        $this->logUserVisit('dit_new'); // historisation du page visité par l'utilisateur

        return $this->render('atelier/dit/new.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function traitementFormulaire(FormInterface $form, Request $request)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DitDto $dto */
            $dto = $form->getData();

            //  1. Enrichir le DTO avec les informations système (initialisation ou ajout des info par defaut)
            $dto = $this->ditFactory->apresSoumission($dto);

            // 2. Traitement de Fichier 
            [$nomFichierEnregistrer, $nomFichier] = (new TraitementFichierService)->traitementDeFichier($form, $dto);

            // 3. Enregistremenr dans la base de donnée
            $data = DitMapper::DtoToArray($dto, $this->getEntityManager(), $nomFichierEnregistrer);
            $this->ditModel->enregistrementDit($data);

            // 4. copie dans DOCUWARE
            $genererPdfDit = new GenererPdfDit();
            $reponse = $genererPdfDit->copyToDOCUWARE($nomFichier, $dto->numeroDemandeIntervention);

            // 5. modification de la colonne pdf_deposer_dw et date_depot_pdf_dw
            $donnees = DitMapper::updateDit($reponse);
            $this->ditModel->updateDitDW($donnees, $dto);

            // 6. enregistrement dans l'historisation de la sucès de la demande
            $this->historiqueOperation->sendNotificationCreation('Votre demande a été enregistrée', $dto->numeroDemandeIntervention, 'dit_liste', true);
        }
    }
}
