<?php

namespace App\Service\Atelier\Dit\soumission\AcBc;

use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Factory\Atelier\Dit\soumission\AcBc\BcSoumisFactory;
use App\Model\Atelier\Dit\Soumission\AcBc\AcBcSoumisModel;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Service\historiqueOperation\Atelier\Dit\Bc\HistoriqueOperationBCService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class AcBcValidationService
{
    private AcBcSoumisModel $acBcModel;
    private BcSoumisFactory $bcSoumisFactory;
    private HistoriqueOperationBCService $historiqueOperation;
    private DitDevisSoumisAValidationModel $ditDevisSoumisAValidationModel;

    public function __construct(EntityManagerInterface $em, AcBcSoumisModel $acBcModel)
    {
        $this->acBcModel                  = $acBcModel;
        $this->historiqueOperation        = new HistoriqueOperationBCService($em);
        $this->bcSoumisFactory            = new BcSoumisFactory();
        $this->ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();
    }

    /** 
     * Vérifier si la soumission est valide avant l'affichage du formulaire
     * 
     * @param ?AccuseReceptionDto $accuseReceptionDto
     * @param string $numDit
     * 
     * @return bool
     */
    public function isValidAvantAffichageForm(?AccuseReceptionDto $accuseReceptionDto, string $numDit): bool
    {
        $isValid = true;
        $message = "";

        if (!$accuseReceptionDto) {
            $isValid = false;
            $message = "L'information du devis est vide ou le statut n'est pas 'Validé atelier' pour le DIT n° {$numDit}";
        }

        if ($accuseReceptionDto->interneExterne === "INTERNE") {
            $isValid = false;
            $message = "Le DIT n° {$numDit} est interne";
        }

        if (!$isValid) $this->historiqueOperation->sendNotificationCreation("Erreur lors de la soumission, Impossible de soumettre le BC . . . $message", $numDit, 'dit_liste');

        return $isValid;
    }

    /** 
     * Notifier le succès de la soumission
     * 
     * @param string $numDit
     * 
     * @return void
     */
    private function notifySuccessSubmission(string $numDit)
    {
        $message = "Le bon de commande et l'accusé de réception ont été soumis avec succès.";
        $this->historiqueOperation->sendNotificationCreation($message, $numDit, 'dit_liste', true);
    }

    public function submitForm(FormInterface $form, Request $request, AccuseReceptionDto $accuseReceptionDto, string $codeSociete)
    {

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->generateNameForAcSoumis($accuseReceptionDto);

            $traitementDeFichierService = new TraitementDeFichierService($accuseReceptionDto->numeroDit);
            $traitementDeFichierService->traitementDeFichier($accuseReceptionDto);

            $numeroVersionMaxBcSoumis = $this->acBcModel->findNumeroVersionMaxBcSoumis($accuseReceptionDto->numeroBc, $codeSociete);

            $bcSoumisDto = $this->bcSoumisFactory->hydrate($accuseReceptionDto, $numeroVersionMaxBcSoumis);

            $this->acBcModel->enregistrerBcSoumis($bcSoumisDto);

            $this->notifySuccessSubmission($accuseReceptionDto->numeroDit);
        }
    }

    private function generateNameForAcSoumis(AccuseReceptionDto $accuseReceptionDto): void
    {
        $suffix = $this->ditDevisSoumisAValidationModel->constructeurPieceMagasin($accuseReceptionDto->numeroDevis, $accuseReceptionDto->codeSociete)[0]['retour'];
        $accuseReceptionDto->nomFichierAcSoumis = "bc_{$accuseReceptionDto->numeroClient}_{$accuseReceptionDto->numeroDevis}-{$accuseReceptionDto->numeroVersionMaxByDit}#{$suffix}.pdf";
    }
}
