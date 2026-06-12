<?php

namespace App\Controller\Atelier\Dit\Soummission;

use App\Controller\Controller;
use App\Factory\Atelier\Dit\soumission\DitFactureSoumisAValidationFactory;
use App\Form\Atelier\Dit\soumission\DitFactureSoumisAValidationType;
use App\Mapper\Atelier\Dit\Soumission\DItFactureSoumisAValidationMapper;
use App\Model\Atelier\Dit\Soumission\DitFactureSoumisAValidationModel;
use App\Service\atelier\dit\soumission\Facture\FactureValidationService;
use App\Service\atelier\dit\soumission\Facture\TraitementDeFichierService;
use App\Service\historiqueOperation\atelier\dit\Facture\HistoriqueOperationFACService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitFactureSoumisAValidationController extends Controller
{
    /**
     * @Route("/soumission-facture/{numDit}", name="dit_insertion_facture")
     */
    public function factureSoumisAValidation(Request $request, string $numDit)
    {
        // initialisation du DTO
        $ditFactorySoumisAValidationFactory = new DitFactureSoumisAValidationFactory();
        $dto = $ditFactorySoumisAValidationFactory->initialisation($numDit, $this->getSecurityService());

        // bloquer si une condition n'est pas valide
        $factureValidationService = new FactureValidationService();
        if ($factureValidationService->validateAvantAffichageForm($dto)) return;

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
            $ditFactorySoumisAValidationFactory = new DitFactureSoumisAValidationFactory();
            $dto = $ditFactorySoumisAValidationFactory->apresSoumission($dto, $form, $numDit);

            // bloquer si des conditions n'est pas valide
            $factureValidationService = new FactureValidationService();
            if ($factureValidationService->validateApresSoumissionForm($form, $dto)) return;

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
            $historiqueOperationRIService = new HistoriqueOperationFACService($this->getEntityManager());
            $message = "Le document de controle a été généré et soumis pour validation {$dto->numeroFact}";
            $historiqueOperationRIService->sendNotificationSoumission($message, $dto->numeroDit, 'dit_liste', true);
        }
    }
}
