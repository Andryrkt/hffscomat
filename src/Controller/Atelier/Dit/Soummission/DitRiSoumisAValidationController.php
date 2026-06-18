<?php

namespace App\Controller\Atelier\Dit\Soummission;

use App\Controller\Controller;
use App\Factory\Atelier\Dit\soumission\DitRiSoumisAValidationFactory;
use App\Form\Atelier\Dit\soumission\DitRiSoumisAValidationType;
use App\Mapper\Atelier\Dit\Soumission\DitRiSoumisAValidationMapper;
use App\Model\Atelier\Dit\Soumission\DitRiSoumisAValidationModel;
use App\Service\atelier\dit\soumission\Ri\RiValidationService;
use App\Service\atelier\dit\soumission\Ri\TraitementFichierService;
use App\Service\genererPdf\dit\ri\GenererPdfRiSoumisAValidataion;
use App\Service\historiqueOperation\Atelier\Dit\Ri\HistoriqueOperationRIService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitRiSoumisAValidationController extends Controller
{
    /**
     * @Route("/soumission-ri/{numDit}", name="dit_insertion_ri")
     */
    public function riSoumisAValidation(string $numDit, Request $request)
    {
        // initialisation factory
        $ditRiSoumisAValidationFactory = new DitRiSoumisAValidationFactory();
        $dto = $ditRiSoumisAValidationFactory->initialisation($numDit, $this->getSecurityService());

        // bloquer l'affichage du formulaire si les condition n'est pa remplis
        $validationService = new RiValidationService();
        if ($validationService->validateAvantAffichageForm($dto)) {
            return;
        }

        // creation formulaire
        $form = $this->getFormFactory()->createBuilder(DitRiSoumisAValidationType::class, $dto)->getForm();

        $this->traitementFormulaire($form, $request);

        return $this->render('atelier/dit/soumission/ri/soumissionRi.html.twig', [
            'form' => $form->createView(),
            'itvAfficher' => $dto->itvAfficher
        ]);
    }

    private function traitementFormulaire(FormInterface $form, Request $request)
    {
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $dto = $form->getData();

            // recharge du Dto
            $ditRiSoumisAValidationFactory = new DitRiSoumisAValidationFactory();
            $dto = $ditRiSoumisAValidationFactory->apresSoumission($dto, $form);

            // blocage de soumission pour quelque condition
            $validationService = new RiValidationService();
            if ($validationService->validateApresSoumissionForm($dto)) {
                return;
            }

            // Enregistrement dans la base de donnée
            $datas = DitRiSoumisAValidationMapper::map($dto);
            $ditRiSoumisAValidationModel = new DitRiSoumisAValidationModel();
            $ditRiSoumisAValidationModel->enregistrementRi($datas);

            // Traitement des fichiers
            $traitementFichier = new TraitementFichierService($this->getEntityManager());
            $nomDesFichiers = $traitementFichier->traiterFichierJoint($form, $dto);

            // copie des fichiers RI dans DOCUWARE
            $genererPdfRiSoumisAValidataion = new GenererPdfRiSoumisAValidataion();
            foreach ($nomDesFichiers as $fileName) {
                $genererPdfRiSoumisAValidataion->copyToDw($fileName);
            }

            // notification reussi de soumission
            $historiqueOperationRIService = new HistoriqueOperationRIService($this->getEntityManager());
            $message = "Le rapport d'intervention a été soumis avec succès, RI_{$dto->numeroOr}";
            $historiqueOperationRIService->sendNotificationSoumission($message, $dto->numeroDit, 'dit_liste', true);
        }
    }
}
