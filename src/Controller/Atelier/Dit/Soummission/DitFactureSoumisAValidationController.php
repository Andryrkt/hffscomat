<?php

namespace App\Controller\Atelier\Dit\Soummission;

use App\Controller\Controller;
use App\Factory\Atelier\Dit\soumission\DitFactureSoumisAValidationFactory;
use App\Form\Atelier\Dit\soumission\DitFactureSoumisAValidationType;
use App\Mapper\Atelier\Dit\Soumission\DItFactureSoumisAValidationMapper;
use App\Model\Atelier\Dit\Soumission\DitFactureSoumisAValidationModel;
use App\Service\atelier\dit\soumission\Facture\FactureValidationService;
use App\Service\atelier\dit\soumission\Facture\TraitementDeFichierService;
use App\Service\historiqueOperation\Atelier\Dit\Facture\HistoriqueOperationFACService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitFactureSoumisAValidationController extends Controller
{
    private DitFactureSoumisAValidationFactory $ditFactorySoumisAValidationFactory;
    private FactureValidationService $factureValidationService;


    public function __construct()
    {
        parent::__construct();
        $this->ditFactorySoumisAValidationFactory = new DitFactureSoumisAValidationFactory();
        $this->factureValidationService = new FactureValidationService();
    }
    /**
     * @Route("/soumission-facture/{numDit}", name="dit_insertion_facture")
     */
    public function factureSoumisAValidation(Request $request, string $numDit)
    {
        // initialisation du DTO
        $dto = $this->ditFactorySoumisAValidationFactory->initialisation($numDit, $this->getSecurityService());

        // bloquer si une condition n'est pas valide
        if ($this->factureValidationService->validateAvantAffichageForm($dto)) return;

        // creation du formulaire
        $form = $this->getFormFactory()->createBuilder(DitFactureSoumisAValidationType::class, $dto)->getForm();

        //tratiement du formulaire
        $this->traitementFormulaire($form, $request, $numDit);

        return $this->render('atelier/dit/soumission/facture/soumissionFacture.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    private function traitementFormulaire(FormInterface $form, Request $request, string $numDit)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();

            // recharge du DTO
            $dto = $this->ditFactorySoumisAValidationFactory->apresSoumission($dto, $form, $numDit);

            // bloquer si des conditions n'est pas valide
            if ($this->factureValidationService->validateApresSoumissionForm($form, $dto)) return;

            // traitement de fichier
            $traitementDeFichierService = new TraitementDeFichierService($this->getSecurityService());
            $traitementDeFichierService->traitmenetDeFichier($dto, $form);

            // enregistrement dans la base de donnée
            $dtiFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();
            $donnees = DItFactureSoumisAValidationMapper::map($dto);
            $dtiFactureSoumisAValidationModel->enregistrerFacture($donnees);

            // modification etat facture dans le table demande_intervention
            $data = DItFactureSoumisAValidationMapper::updateDit($dto->etatOr);
            $dtiFactureSoumisAValidationModel->updateEtatFacture($dto->numeroDit, $dto->codeSociete, $data);


            // historisation de soumission
            $historiqueOperationFACService = new HistoriqueOperationFACService($this->getEntityManager());
            $message = "Le document de controle a été généré et soumis pour validation {$dto->numeroFact}";
            $historiqueOperationFACService->sendNotificationSoumission($message, $dto->numeroDit, 'dit_liste', true);
        }
    }
}
