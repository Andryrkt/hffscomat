<?php

namespace App\Controller\Magasin\Commande\Soumission;

use App\Controller\Controller;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\genererPdf\magasin\GeneratePdfCdeMagasin;
use App\Dto\Magasin\Commande\Soumission\BcSoumisMagasinDTO;
use App\Model\magasin\CommANDe\Soumission\CdeSoumissionModel;
use App\Form\magasin\Commande\SoumissionCommande\SoumissionCommandeType;
use App\Service\historiqueOperation\magasin\commandeFrn\HistoriqueOperationCdeFrnService;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\SuiviOperation;

/**
 * @Route("/magasin/commande")
 */
class SoumissionCommandeController extends Controller
{
    private HistoriqueOperationCdeFrnService $historiqueOperation;
    private CdeSoumissionModel $cdeSoumissionModel;
    private GeneratePdfCdeMagasin $generatePdfCdeMagasin;

    public function __construct()
    {
        parent::__construct();
        $this->historiqueOperation = new HistoriqueOperationCdeFrnService($this->getEntityManager());
        $this->cdeSoumissionModel = new CdeSoumissionModel();
        $this->generatePdfCdeMagasin = new GeneratePdfCdeMagasin();
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

        $this->logUserVisit('generer_commande_fournisseur');

        if ($form->isSubmitted() && $form->isValid()) {
            $this->soumettreAValider($form);
        }

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
        $suivi = new SuiviOperation();
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
            $this->historiqueOperation->setEtapes($suivi->getEtapes());
            $this->historiqueOperation->sendNotificationSoumission($message, $numCommande ?? '-', 'profil_acceuil', false);
        }

        $this->historiqueOperation->setEtapes($suivi->getEtapes());
        $this->historiqueOperation->sendNotificationSoumission($message, $bcSoumisMagasinDto->numeroCommande, 'profil_acceuil', true);
    }
}
