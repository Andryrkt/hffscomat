<?php

namespace App\Controller\Magasin\Commande\Soumission;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\magasin\Commande\Soumission\CdeFrnSoumissionService;
use App\Form\magasin\Commande\SoumissionCommande\SoumissionCommandeType;
use App\Service\historiqueOperation\magasin\commandeFrn\HistoriqueOperationCdeFrnService;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\suivi\SuiviOperationService;

/**
 * @Route("/magasin/commande")
 */
class SoumissionCommandeController extends Controller
{
    private CdeFrnSoumissionService $cdeFrnSoumissionService;

    public function __construct(CdeFrnSoumissionService $cdeFrnSoumissionService)
    {
        parent::__construct();
        $this->cdeFrnSoumissionService = $cdeFrnSoumissionService;
    }

    /**
     * @Route("/generer-commande-fournisseur", name="generer_commande_fournisseur")
     */
    public function soumissionCommande(Request $request)
    {
        $form = $this->getFormFactory()->createBuilder(SoumissionCommandeType::class, null, [
            'method' => 'POST',
        ])->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $formData = $form->getData();

            $this->cdeFrnSoumissionService->soumettre($this->getUserMail(), $formData["numCmde"], $formData["generationToken"], $formData["piecesJointesPdf"] ?? [], $this->getUserName());
        }

        $this->logUserVisit('generer_commande_fournisseur');

        return $this->render('magasin/commande/soumission/soumissionCommandeFournisseur.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function soumettreAValider(FormInterface $form)
    {
        $numCommande = $form->get('numCmdeAValider')->getData();
        $generatedFilePath = $form->get('generatedFilePath')->getData();

        $bcSoumisMagasinDto = new  BcSoumisMagasinDTO();
        $bcSoumisMagasinDto->operateur           = $this->getUserName();
        $bcSoumisMagasinDto->numeroCommande      = $numCommande;
        $bcSoumisMagasinDto->statut              = "Soumis à validation";
        $bcSoumisMagasinDto->dateHeureSoumission = new \DateTime();

        $cheminDuFichier = $_ENV["BASE_PATH_FICHIER"] . str_replace('/Upload', '', $generatedFilePath);

        // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
        $suivi = SuiviOperationService::demarrer(SuiviOperationService::TYPE_CDE_FOURNISSEUR, (string) $numCommande);
        $message = 'Votre demande a été enregistrée';

        try {
            $suivi->etape('Vérification du fichier PDF', function () use ($cheminDuFichier, $generatedFilePath) {
                if (!file_exists($cheminDuFichier)) {
                    throw new \RuntimeException("Le fichier PDF n'existe pas : " . $generatedFilePath);
                }
            });

            // un échec de copie DocuWare ne bloque pas l'enregistrement : il est tracé par deposerDw = false
            try {
                $suivi->etape('Copie DocuWare', function () use ($cheminDuFichier, $numCommande) {
                    $this->generatePdfCdeMagasin->exigerCopieReussie($this->generatePdfCdeMagasin->copyToDOCUWARE($cheminDuFichier, $numCommande));
                });
                $bcSoumisMagasinDto->deposerDw = true;
            } catch (EtapeOperationException $e) {
                $message .= ", mais la copie dans DocuWare a échoué";
            }

            $suivi->etape('Enregistrement base de données', function () use ($bcSoumisMagasinDto) {
                $this->cdeSoumissionModel->enregistrerBcSoumisMagasin($bcSoumisMagasinDto);
            });
        } catch (EtapeOperationException $e) {
            $message = "Echec de la soumission de la commande " . $numCommande . " à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
            $this->historiqueOperation->sendNotificationSoumission($message, $numCommande ?? '-', 'profil_acceuil', false);
        }

        $this->historiqueOperation->sendNotificationSoumission($message, $bcSoumisMagasinDto->numeroCommande, 'profil_acceuil', true);
    }
}
