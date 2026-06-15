<?php

namespace App\Controller\Atelier\Dit\Soummission\Devis;

use App\Controller\Controller;
use App\Factory\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationFactory;
use App\Form\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationType;
use App\Mapper\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationMapper;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Service\atelier\dit\soumission\Devis\DevisValidationService;
use App\Service\atelier\dit\soumission\Devis\TraitementDeFicherService;
use App\Service\historiqueOperation\atelier\dit\Devis\HistoriqueOperationDEVService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitDevisSoumisAVAlidataionController extends Controller
{
    /**
     * @Route("/insertion-devis/{numDit}/{type}", name="dit_insertion_devis")
     */
    public function insertionDevis(string $numDit, string $type, Request $request)
    {

        // initialisation DTO
        $ditDevisSoumisAValidationFactory = new DitDevisSoumisAValidationFactory($this->getSecurityService());
        $dto = $ditDevisSoumisAValidationFactory->initialisation($numDit, $type);

        // Validation des données
        $devisValidationService = new DevisValidationService();
        if ($devisValidationService->validateAvantAffichageForm($dto)) {
            if ($request->query->get('continueDevis') == 1) {
                $this->getSessionService()->set('devis_version_valide', 'KO');
            }
        }

        // Creation du formulaire
        $form = $this->getFormFactory()->createBuilder(DitDevisSoumisAValidationType::class, $dto)->getForm();

        // traitement du formulaire
        $this->traitementFormulaire($form, $request, $numDit);

        return $this->render('atelier/dit/soumission/devis/soumissionDevis.html.twig', [
            'form' => $form->createView(),
            'numDevis' => $dto->numeroDevis,
            'numDit' => $numDit,
            'type' => $type
        ]);
    }


    private function traitementFormulaire(FormInterface $form, Request $request, string $numDit)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dto = $form->getData();

            $ditDevisSoumisAValidationFactory = new DitDevisSoumisAValidationFactory($this->getSecurityService());
            $dto = $ditDevisSoumisAValidationFactory->apresSoumission($dto, $numDit);

            $ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();

            // enregistrement dans la base de donnée
            $datas = DitDevisSoumisAValidationMapper::enregistreDevis($dto);
            $ditDevisSoumisAValidationModel->enregistrerDevis($datas);
            // edit du table demande_itervention en modifiant les colonnes, numero_devis_rattache et statut_devis
            $donnees = DitDevisSoumisAValidationMapper::updateDit($dto);
            $ditDevisSoumisAValidationModel->updateNumeroEtStatuDevis($dto->numeroDit, $dto->codeSociete, $donnees);

            // traitement de fichier et copie dans DOCUWARE
            $traitementDuFichier = new TraitementDeFicherService($this->getSecurityService());
            $traitementDuFichier->traitementDeFicher($form, $dto);

            // historisation
            $historiqueOperationDEVService = new HistoriqueOperationDEVService($this->getEntityManager());
            $message = 'Le devis a été soumis avec succès';
            $historiqueOperationDEVService->sendNotificationCreation($message, $dto->numeroDevis, 'dit_liste', true);
        }
    }
}
