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
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\SuiviOperation;
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

            $historiqueOperationFACService = new HistoriqueOperationFACService($this->getEntityManager());

            // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
            $suivi = new SuiviOperation();

            try {
                // traitement de fichier
                $traitementDeFichierService = new TraitementDeFichierService($this->getSecurityService());
                $traitementDeFichierService->traitmenetDeFichier($dto, $form, $suivi);

                $dtiFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();

                // enregistrement dans la base de donnée
                $suivi->etape('Enregistrement base de données', function () use ($dtiFactureSoumisAValidationModel, $dto) {
                    $donnees = DItFactureSoumisAValidationMapper::map($dto);
                    $dtiFactureSoumisAValidationModel->enregistrerFacture($donnees);
                });

                // modification etat facture dans le table demande_intervention
                $suivi->etape('Mise à jour de la DIT', function () use ($dtiFactureSoumisAValidationModel, $dto) {
                    $data = DItFactureSoumisAValidationMapper::updateDit($dto->etatOr);
                    $dtiFactureSoumisAValidationModel->updateEtatFacture($dto->numeroDit, $dto->codeSociete, $data);
                });
            } catch (EtapeOperationException $e) {
                $message = "Echec de la soumission de la facture {$dto->numeroFact} à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
                $historiqueOperationFACService->setEtapes($suivi->getEtapes());
                $historiqueOperationFACService->sendNotificationSoumission($message, $dto->numeroDit, 'dit_liste', false);
            }

            // historisation de soumission
            $message = "Le document de controle a été généré et soumis pour validation {$dto->numeroFact}";
            $historiqueOperationFACService->setEtapes($suivi->getEtapes());
            $historiqueOperationFACService->sendNotificationSoumission($message, $dto->numeroDit, 'dit_liste', true);
        }
    }
}
