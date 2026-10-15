<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\DitDto;
use App\Factory\Atelier\Dit\DitFactory;
use App\Form\Atelier\Dit\DitType;
use App\Mapper\Atelier\Dit\DitMapper;
use App\Model\Atelier\Dit\CategorieAteAppModel;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\WorTypeDocumentModel;
use App\Service\atelier\dit\TraitementFichierService;
use App\Service\genererPdf\dit\GenererPdfDit;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\SuiviOperation;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitDuplicationController extends Controller
{
    use FormatageTrait;


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
     * @Route("/dit-duplication/{numDit}", name="dit_duplication")
     * @param Request $request
     */
    public function duplication($numDit, Request $request)
    {
        $user = $this->getUser();
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        $ditInformations = $this->ditModel->recupInformationsDit($numDit, $codeSociete);

        // Simplification de la logique de duplication
        $ditDto = $this->initialisationForm($ditInformations);

        $worTypeDocumentModel = new WorTypeDocumentModel();
        $descriptionTypeDocument = $worTypeDocumentModel->getDescriptionById($ditInformations["type_document"]);
        $ditDto->typeDocument = $descriptionTypeDocument;
        $categorieAteAppModel = new CategorieAteAppModel();
        $libelleCategorie = $categorieAteAppModel->getDescriptionById($ditInformations["categorie_demande"]);

        $ditDto->categorieDemande = $libelleCategorie;


        $form = $this->getFormFactory()->createBuilder(DitType::class, $ditDto)->getForm();

        $this->traitementFormulaire($form, $request);

        $this->logUserVisit('dit_duplication', ['numDit' => $numDit]);

        return $this->render('atelier/dit/duplication.html.twig', [
            'form' => $form->createView(),
            'dit' => $ditDto,

        ]);
    }

    public function  initialisationForm($ditInformations)
    {
        $dtiDto = DitMapper::transformToDto($ditInformations, $this->getEntityManager());
        return $dtiDto;
    }
    private function traitementFormulaire(FormInterface $form, Request $request)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var DitDto $dto */
            $dto = $form->getData();

            //  1. Enrichir le DTO avec les informations système (initialisation ou ajout des info par defaut)
            $dto = $this->ditFactory->apresSoumission($dto);

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
                $message = "Echec de la duplication de la DIT " . $dto->numeroDemandeIntervention . " à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
                $this->historiqueOperation->setEtapes($suivi->getEtapes());
                $this->historiqueOperation->sendNotificationCreation($message, $dto->numeroDemandeIntervention, 'dit_liste', false);
            }

            // 6. enregistrement dans l'historisation de la sucès de la demande
            $this->historiqueOperation->setEtapes($suivi->getEtapes());
            $this->historiqueOperation->sendNotificationCreation($message, $dto->numeroDemandeIntervention, 'dit_liste', true);
        }
    }
}
