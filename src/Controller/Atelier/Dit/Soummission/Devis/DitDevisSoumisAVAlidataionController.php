<?php

namespace App\Controller\Atelier\Dit\Soummission\Devis;

use App\Controller\Controller;
use App\Factory\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationFactory;
use App\Form\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationType;
use App\Service\atelier\dit\soumission\Devis\DevisValidationService;
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
        if($devisValidationService->validateAvantAffichageForm($dto)) {
            if ($request->query->get('continueDevis') == 1) {
                $this->getSessionService()->set('devis_version_valide', 'KO');
            }
        }

        // Creation du formulaire
        $form = $this->getFormFactory()->createBuilder(DitDevisSoumisAValidationType::class, $dto)->getForm();

        return $this->render('atelier/dit/soumission/devis/soumissionDevis.html.twig', [
            'form' => $form->createView(),
            'numDevis' => $dto->numeroDevis,
            'numDit' => $numDit,
            'type' => $type
        ]);
    }
}
