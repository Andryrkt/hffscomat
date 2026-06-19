<?php

namespace App\Controller\Atelier\Dit\Soummission;

use App\Controller\Controller;
use App\Form\Atelier\Dit\soumission\AcSoumisType;
use App\Model\Atelier\Dit\Soumission\AcBc\AcBcSoumisModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\Atelier\Dit\soumission\AcBc\AcBcValidationService;

/**
 * @Route("/atelier/demande-intervention")
 */
class AcBcSoumisController extends Controller
{
    private AcBcValidationService $acBcValidationService;
    private AcBcSoumisModel $acBcModel;

    public function __construct()
    {
        parent::__construct();

        $this->acBcModel = new AcBcSoumisModel();
        $this->acBcValidationService = new AcBcValidationService($this->getEntityManager(), $this->acBcModel);
    }

    /**
     * @Route("/ac-bc-soumis/{numDit}", name="dit_ac_bc_soumis")
     */
    public function traitementFormulaire(Request $request, string $numDit)
    {
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        $accuseReceptionDto = $this->acBcModel->findInfoDevis($numDit, $codeSociete);

        if (!$this->acBcValidationService->isValidAvantAffichageForm($accuseReceptionDto, $numDit)) return;

        $form = $this->getFormFactory()->createBuilder(AcSoumisType::class, $accuseReceptionDto)->getForm();

        $this->acBcValidationService->submitForm($form, $request, $accuseReceptionDto, $codeSociete);

        return $this->render('atelier/dit/soumission/acBc/soumissionAcBc.html.twig', [
            'form' => $form->createView()
        ]);
    }
}
