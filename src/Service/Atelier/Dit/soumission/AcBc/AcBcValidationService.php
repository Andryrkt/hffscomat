<?php

namespace App\Service\Atelier\Dit\soumission\AcBc;

use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Factory\Atelier\Dit\soumission\AcBc\BcSoumisFactory;
use App\Model\Atelier\Dit\Soumission\AcBc\AcBcSoumisModel;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Service\historiqueOperation\Atelier\Dit\Bc\HistoriqueOperationBCService;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\suivi\SuiviOperationService;
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
            // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
            $suivi = SuiviOperationService::demarrer(SuiviOperationService::TYPE_AC_BC, (string) $accuseReceptionDto->numeroDit);

            try {
                $suivi->etape('Récupération du constructeur', function () use ($accuseReceptionDto) {
                    $this->generateNameForAcSoumis($accuseReceptionDto);
                });

                $traitementDeFichierService = new TraitementDeFichierService($accuseReceptionDto->numeroDit);
                $traitementDeFichierService->traitementDeFichier($accuseReceptionDto);

                $suivi->etape('Enregistrement base de données', function () use ($accuseReceptionDto, $codeSociete) {
                    $numeroVersionMaxBcSoumis = $this->acBcModel->findNumeroVersionMaxBcSoumis($accuseReceptionDto->numeroBc, $codeSociete);

                    $bcSoumisDto = $this->bcSoumisFactory->hydrate($accuseReceptionDto, $numeroVersionMaxBcSoumis);

                    $this->acBcModel->enregistrerBcSoumis($bcSoumisDto);
                });
            } catch (EtapeOperationException $e) {
                $message = "Echec de la soumission du bon de commande et de l'accusé de réception à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
                $this->historiqueOperation->sendNotificationCreation($message, $accuseReceptionDto->numeroDit, 'dit_liste', false);
            }

            $this->notifySuccessSubmission($accuseReceptionDto->numeroDit);
        }
    }

    private function generateNameForAcSoumis(AccuseReceptionDto $accuseReceptionDto): void
    {
        $suffix = $this->ditDevisSoumisAValidationModel->constructeurPieceMagasin($accuseReceptionDto->numeroDevis, $accuseReceptionDto->codeSociete)[0]['retour'];
        $accuseReceptionDto->nomFichierAcSoumis = "sctbc_{$accuseReceptionDto->numeroClient}_{$accuseReceptionDto->numeroDevis}-{$accuseReceptionDto->numeroVersionMaxByDit}#{$suffix}.pdf";
    }
}
