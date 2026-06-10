<?php

namespace App\Controller\Atelier\Dit\Soummission;

use App\Controller\Controller;
use App\Factory\Atelier\Dit\soumission\DitRiSoumisAValidationFactory;
use App\Form\Atelier\Dit\soumission\DitRiSoumisAValidationType;
use App\Service\atelier\dit\soumission\Ri\RiValidationService;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitRiSoumisAValidationController extends Controller
{
    /**
     * @Route("/soumission-ri/{numDit}", name="dit_insertion_ri")
     */
    public function riSoumisAValidation(string $numDit)
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

        return $this->render('atelier/dit/soumission/ri/soumissionRi.html.twig', [
            'form' => $form->createView(),
            'itvAfficher' => $dto->itvAfficher
        ]);
    }
}
