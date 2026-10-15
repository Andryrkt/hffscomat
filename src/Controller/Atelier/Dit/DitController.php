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
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\SuiviOperation;
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
        $this->getSessionService()->set('microtime_start', microtime(true));

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
            $avecMateriel = $request->request->has('interventionAvecMateriel');
            $dto = $this->ditFactory->apresSoumission($dto, $avecMateriel);

            // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
            $suivi = new SuiviOperation();
            $message = 'Votre demande a été enregistrée';

            try {
                // 2. Traitement de Fichier
                [$nomFichierEnregistrer, $nomFichier] = (new TraitementFichierService)->traitementDeFichier($form, $dto, $suivi);

                // 3. Enregistremenr dans la base de donnée
                $suivi->etape('Enregistrement base de données', function () use ($dto, $nomFichierEnregistrer) {
                    $data = DitMapper::DtoToArray($dto, $this->getEntityManager(), $nomFichierEnregistrer);
                    $this->ditModel->enregistrementDit($data);
                });

                // 4. copie dans DOCUWARE (un échec ne bloque pas : il est tracé dans pdf_deposer_dw)
                $genererPdfDit = new GenererPdfDit();
                try {
                    $reponse = $suivi->etape('Copie DocuWare', function () use ($genererPdfDit, $nomFichier, $dto) {
                        $genererPdfDit->exigerCopieReussie($genererPdfDit->copyToDOCUWARE($nomFichier, $dto->numeroDemandeIntervention));
                        return true;
                    });
                } catch (EtapeOperationException $e) {
                    $reponse = false;
                    $message .= ", mais la copie dans DocuWare a échoué";
                }

                // 5. modification de la colonne pdf_deposer_dw et date_depot_pdf_dw
                $suivi->etape('Mise à jour dépôt DocuWare', function () use ($reponse, $dto) {
                    $donnees = DitMapper::updateDit($reponse);
                    $this->ditModel->updateDitDW($donnees, $dto);
                });
            } catch (EtapeOperationException $e) {
                $message = "Echec de l'enregistrement de la DIT " . $dto->numeroDemandeIntervention . " à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
                $this->historiqueOperation->setEtapes($suivi->getEtapes());
                $this->historiqueOperation->sendNotificationCreation($message, $dto->numeroDemandeIntervention, 'dit_liste', false);
            }

            // 6. enregistrement dans l'historisation de la sucès de la demande
            $this->historiqueOperation->setEtapes($suivi->getEtapes());
            $this->historiqueOperation->sendNotificationCreation($message, $dto->numeroDemandeIntervention, 'dit_liste', true);
        }
    }
}
