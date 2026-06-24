<?php

namespace App\Controller\Atelier\Dit\Soummission;

ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '5M');


use App\Controller\Controller;
use App\Dto\Atelier\Dit\soumission\OrSoumissionDto;
use App\Factory\Atelier\Dit\Soumission\OrSoumissionFactory;
use App\Form\Atelier\Dit\soumission\DitOrsSoumisAValidationType;
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
    private OrSoumissionFactory $orSoummissionFactory;
    private ValidationService $validationService;


    public function __construct()
    {
        parent::__construct();
        $this->historiqueOperation = new HistoriqueOperationORService($this->getEntityManager());
        $this->ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $this->orSoummissionFactory = new OrSoumissionFactory();
        $this->validationService = new ValidationService();
    }


    /**
     * @Route("/soumission-or/{numDit}", name="dit_insertion_or")
     *
     * @return void
     */
    public function insertionOr(Request $request, string $numDit)
    {
        // initialisation DTO
        $dto = $this->orSoummissionFactory->initialisation($numDit, $this->getSecurityService());

        // bloquer l'affichage du formulaire si les condition n'est pa remplis
        if ($this->validationService->validateAvantAffichageForm($dto)) {
            return;
        }

        // creation du formulaire
        $form = $this->getFormFactory()->createBuilder(DitOrsSoumisAValidationType::class, $dto)->getForm();

        // traitement du formulaire
        $this->traitementFormulaire($form,  $request, $numDit);

        // historisation du page visité par l'utilisateur
        $this->logUserVisit('dit_insertion_or', [
            'numDit' => $numDit,
        ]);

        return $this->render('atelier/dit/soumission/ors/soumissionOr.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function traitementFormulaire(FormInterface $form, Request $request, string $numDit)
    {

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();

            // renforcement du dto
            $dto = $this->orSoummissionFactory->apresSoumission($numDit, $dto);

            // blocage de soumision
            if ($this->validationService->validateSubmittedFile($form, null, $dto)) return;


            /** ENVOIE des DONNEE dans BASE DE DONNEE */
            $this->envoieDonnerDansBd($dto);

            /** CREATION , FUSION, ENVOIE DW du PDF */
            (new TraitementFichierService())->traitementDeFichier($form, $dto, $this->getUserMail());

            /** modifier la colonne id_statut_demande, numero_or, statut_or dans la table demande_intervention */
            $this->modificationDit($dto);

            $this->historiqueOperation->sendNotificationSoumission('Le document de controle a été généré et soumis pour validation', $dto->numeroOr, 'dit_liste', true);
        }
    }

    private function modificationDit(OrSoumissionDto $dto)
    {
        $this->ditOrsoumisAValidationModel->updateDit($dto);
    }

    private function envoieDonnerDansBd(OrSoumissionDto $dto)
    {
        $ors = $this->ditOrsoumisAValidationModel->recupOrSoumisValidation($dto->numeroOr, $dto->codeSociete);
        $this->ditOrsoumisAValidationModel->enregistrementOr($dto, $ors);
    }
}
