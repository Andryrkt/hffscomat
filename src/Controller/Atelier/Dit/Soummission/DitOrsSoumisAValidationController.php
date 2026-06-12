<?php

namespace App\Controller\Atelier\Dit\Soummission;

ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '5M');


use App\Controller\Controller;
use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\soumission\OrSoumissionDto;
use App\Factory\Atelier\Dit\DitFactory;
use App\Factory\Atelier\Dit\Soumission\OrSoumissionFactory;
use App\Form\Atelier\Dit\soumission\DitOrsSoumisAValidationType;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\atelier\dit\soumission\ORs\TraitementFichierService;
use App\Service\atelier\dit\soumission\ORs\ValidationService;
use App\Service\historiqueOperation\Atelier\Dit\ORs\HistoriqueOperationORService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitOrsSoumisAValidationController extends Controller
{

    private HistoriqueOperationORService $historiqueOperation;

    private DitOrSoumisAValidationModel $ditOrsoumisAValidationModel;

    private DitModel $ditModel;
    private DitFactory $ditFactory;


    public function __construct()
    {
        parent::__construct();
        $this->historiqueOperation = new HistoriqueOperationORService($this->getEntityManager());
        $this->ditModel = new DitModel();
        $this->ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $this->ditFactory = new DitFactory($this->getSecurityService(), $this->getEntityManager());
    }


    /**
     * @Route("/soumission-or/{numDit}", name="dit_insertion_or")
     *
     * @return void
     */
    public function insertionOr(Request $request, string $numDit)
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        // recupérer le numéro OR dans la base de donnée IPS
        $numOr = $this->ditOrsoumisAValidationModel->recupNumeroOr($numDit, $codeSociete);

        // factory
        $orSoummissionFactory = new OrSoumissionFactory();
        $dto = $orSoummissionFactory->initialisation($numDit, $numOr, $codeSociete);

        // bloquer l'affichage du formulaire si les condition n'est pa remplis
        $validationService = new ValidationService();
        if ($validationService->validateAvantAffichageForm($dto)) {
            return;
        }

        $form = $this->getFormFactory()->createBuilder(DitOrsSoumisAValidationType::class, $dto)->getForm();

        $this->traitementFormulaire($form,  $request, $numDit, $numOr,);

        $this->logUserVisit('dit_insertion_or', [
            'numDit' => $numDit,
        ]); // historisation du page visité par l'utilisateur

        return $this->render('atelier/dit/soumission/ors/soumissionOr.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function traitementFormulaire(FormInterface $form, Request $request, string $numDit, string $numOr)
    {

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orSoummissionFactory = new OrSoumissionFactory();
            $dto = $form->getData();

            $dto = $orSoummissionFactory->apresSoumission($numDit, $numOr, $dto);
 
            // DONE
            /** DEBUT CONDITION DE BLOCAGE */
            $validationService = new ValidationService();
            $conditionBloquage =   $validationService->validateSubmittedFile($form, null, $dto);
            /** FIN CONDITION DE BLOCAGE */
            if (!$conditionBloquage) {

                /** ENVOIE des DONNEE dans BASE DE DONNEE */
                $this->envoieDonnerDansBd($dto);

                /** CREATION , FUSION, ENVOIE DW du PDF */
                (new TraitementFichierService())->traitementDeFichier($form, $dto, $this->getUserMail());

                /** modifier la colonne id_statut_demande, numero_or, statut_or dans la table demande_intervention */
                $this->modificationDit($dto);

                $this->historiqueOperation->sendNotificationSoumission('Le document de controle a été généré et soumis pour validation', $numOr, 'dit_liste', true);
            } else {
                $message = "Echec lors de la soumission, . . .";
                $this->historiqueOperation->sendNotificationSoumission($message, $numOr, 'dit_liste');
                exit;
            }
        }
    }

    private function modificationDit(OrSoumissionDto $dto)
    {
        $this->DitOrSoumisAValidationModel->updateDit($dto);
    }

    private function envoieDonnerDansBd(OrSoumissionDto $dto)
    {
        $ors = $this->DitOrSoumisAValidationModel->recupOrSoumisValidation($dto->numeroOr, $dto->codeSociete);
        $this->DitOrSoumisAValidationModel->enregistrementOr($dto, $ors);
    }
}
