<?php

namespace App\Controller\Atelier\Dit\Soummission;

use Exception;
use App\Entity\dit\AcSoumis;
use App\Entity\dit\BcSoumis;
use App\Controller\Controller;
use App\Entity\dit\DemandeIntervention;
use App\Service\TableauEnStringService;
use Symfony\Component\Form\FormInterface;
use App\Service\fichier\FileUploaderService;
use App\Entity\dit\DitDevisSoumisAValidation;
use App\Factory\Atelier\Dit\soumission\AcBc\AccuseReceptionFactory;
use App\Factory\Atelier\Dit\soumission\AcBc\BcSoumisFactory;
use App\Form\Atelier\Dit\soumission\AcSoumisType;
use App\Model\Atelier\Dit\Soumission\AcBc\AcBcSoumisModel;
use Symfony\Component\HttpFoundation\Request;
use App\Service\genererPdf\GenererPdfAcSoumis;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\dit\BcSoumisRepository;
use App\Repository\dit\DitDevisSoumisAValidationRepository;
use App\Repository\dit\DitRepository;
use App\Service\Atelier\Dit\soumission\AcBc\AcBcValidationService;
use App\Service\historiqueOperation\Atelier\Dit\Bc\HistoriqueOperationBCService;

/**
 * @Route("/atelier/demande-intervention")
 */
class AcBcSoumisController extends Controller
{
    // private $acSoumis;
    // private $bcSoumis;
    // private $genererPdfAc;
    private AcBcValidationService $acBcValidationService;
    private BcSoumisFactory $bcSoumisFactory;
    // private DitRepository $ditRepository;
    // private BcSoumisRepository $bcRepository;
    private AcBcSoumisModel $acBcModel;

    public function __construct()
    {
        parent::__construct();

        $this->acBcValidationService = new AcBcValidationService($this->getEntityManager());
        $this->bcSoumisFactory = new BcSoumisFactory();
        // $this->acSoumis = new AcSoumis();
        // $this->bcSoumis = new BcSoumis();
        // $this->bcRepository = $this->getEntityManager()->getRepository(BcSoumis::class);
        // $this->genererPdfAc = new GenererPdfAcSoumis();
        // $this->historiqueOperation = new HistoriqueOperationBCService($this->getEntityManager());
        // $this->ditRepository = $this->getEntityManager()->getRepository(DemandeIntervention::class);
        $this->acBcModel = new AcBcSoumisModel();
    }

    /**
     * @Route("/ac-bc-soumis/{numDit}", name="dit_ac_bc_soumis")
     */
    public function traitementFormulaire(Request $request, string $numDit)
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        $accuseReceptionDto = $this->acBcModel->findInfoDevis($numDit, $codeSociete);

        if (!$this->acBcValidationService->isValidAvantAffichageForm($accuseReceptionDto, $numDit)) return;

        $form = $this->getFormFactory()->createBuilder(AcSoumisType::class, $accuseReceptionDto)->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $numeroVersionMaxBcSoumis = $this->acBcModel->findNumeroVersionMaxBcSoumis($accuseReceptionDto->numeroBc, $codeSociete);

            $bcSoumisDto = $this->bcSoumisFactory->hydrate($accuseReceptionDto, $numeroVersionMaxBcSoumis);

            dd($bcSoumisDto);
            $acSoumis = $this->initialisation($devis, $numDit, $codeSociete);
            $numBc = $acSoumis->getNumeroBc(); // recupère le numero bon de commande
            $numDevis = $acSoumis->getNumeroDevis(); // recupère le numero devis
            $numClient = $this->ditRepository->findNumClient($numDit, $codeSociete); //recupère le numero cline
            $numeroVersionMax = $this->bcRepository->findNumeroVersionMax($numBc, $codeSociete); // récupération de la version maximal du numero version
            // ajouter les données nécessaire pour l'enregistrement dans la table bc_soumis
            $bcSoumis = $this->ajoutDonneeBc($acSoumis, $numeroVersionMax);

            /** CREATION , FUSION, ENVOIE DW du PDF */
            $acSoumis->setNumeroVersion($bcSoumis->getNumVersion());
            $numClientBcDevis = $numClient . '_' . $numDevis;
            $numeroVersionMaxDit = $this->bcRepository->findNumeroVersionMaxParDit($numDit, $codeSociete) + 1;
            $suffix = $this->ditDevisSoumisAValidationModel->constructeurPieceMagasin($numDevis, $codeSociete)[0]['retour'];
            $nomFichier = 'bc_' . $numClientBcDevis . '-' . $numeroVersionMaxDit . '#' . $suffix . '.pdf';

            //crée le pdf
            $this->genererPdfAc->genererPdfAc($acSoumis, $numClientBcDevis, $numeroVersionMaxDit, $nomFichier);

            //fusionne le pdf
            $chemin = $_ENV['BASE_PATH_FICHIER']  . '/dit/ac_bc/';
            $fileUploader = new FileUploaderService($chemin);
            $file = $form->get('pieceJoint01')->getData();

            $uploadedFilePath = $fileUploader->uploadFileSansName($file, $nomFichier);
            $uploadedFiles = $fileUploader->insertFileAtPosition([$uploadedFilePath], $chemin . $nomFichier, count([$uploadedFilePath]));

            $this->ConvertirLesPdf($uploadedFiles); // très important pour les pdf externe

            $fileUploader->fusionFichers($uploadedFiles,  $chemin . $nomFichier);

            //envoie le pdf dans docuware
            $this->genererPdfAc->copyToDWAcSoumis($nomFichier); // copier le fichier dans docuware

            /** Envoie des information du bc dans le table bc_soumis */
            $bcSoumis->setNomFichier($nomFichier);
            $this->envoieBcDansBd($bcSoumis);

            $this->acBcValidationService->notifySuccessSubmission($numDit);
        }

        return $this->render('atelier/dit/soumission/acBc/soumissionAcBc.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
