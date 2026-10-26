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
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\suivi\SuiviOperationService;
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

            $historiqueOperationRIService = new HistoriqueOperationRIService($this->getEntityManager());

            // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
            $suivi = SuiviOperationService::demarrer(SuiviOperationService::TYPE_RI, 'RI_' . $dto->numeroOr);

            try {
                // Enregistrement dans la base de donnée
                $suivi->etape('Enregistrement base de données', function () use ($dto) {
                    $datas = DitRiSoumisAValidationMapper::map($dto);
                    (new DitRiSoumisAValidationModel())->enregistrementRi($datas);
                });

                // Traitement des fichiers
                $nomDesFichiers = $suivi->etape('Enregistrement fichiers RI', function () use ($form, $dto) {
                    $traitementFichier = new TraitementFichierService($this->getEntityManager());
                    return $traitementFichier->traiterFichierJoint($form, $dto);
                });

                // copie des fichiers RI dans DOCUWARE
                $suivi->etape('Copie DocuWare', function () use ($nomDesFichiers, $dto) {
                    $genererPdfRiSoumisAValidataion = new GenererPdfRiSoumisAValidataion();
                    foreach ($nomDesFichiers as $fileName) {
                        $genererPdfRiSoumisAValidataion->exigerCopieReussie($genererPdfRiSoumisAValidataion->copyToDw($fileName, $dto->numeroDit));
                    }
                });
            } catch (EtapeOperationException $e) {
                $message = "Echec de la soumission du rapport d'intervention RI_{$dto->numeroOr} à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
                $historiqueOperationRIService->sendNotificationSoumission($message, $dto->numeroDit, 'dit_liste', false);
            }

            // notification reussi de soumission
            $message = "Le rapport d'intervention a été soumis avec succès, RI_{$dto->numeroOr}";
            $historiqueOperationRIService->sendNotificationSoumission($message, $dto->numeroDit, 'dit_liste', true);
        }
    }
}
