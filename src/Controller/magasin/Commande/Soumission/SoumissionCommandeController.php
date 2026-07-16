<?php

namespace App\Controller\Magasin\Commande\Soumission;

use App\Controller\Controller;
use App\Dto\Magasin\Commande\Soumission\BcSoumisMagasinDTO;
use App\Dto\Magasin\Commande\Soumission\CommandeSoumissionDTO;
use App\Form\magasin\Commande\SoumissionCommande\SoumissionCommandeType;
use App\Model\magasin\CommANDe\Soumission\CdeSoumissionModel;
use App\Service\genererPdf\magasin\GeneratePdfCdeMagasin;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use Override;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/magasin/commande")
 */
class SoumissionCommandeController extends Controller
{
    private HistoriqueOperationDITService $historiqueOperation;
    private CdeSoumissionModel $cdeSoumissionModel;
    private GeneratePdfCdeMagasin $generatePdfCdeMagasin;


    public function __construct()
    {
        parent::__construct();
        $this->historiqueOperation = new HistoriqueOperationDITService($this->getEntityManager());
        $this->cdeSoumissionModel = new CdeSoumissionModel();
        $this->generatePdfCdeMagasin = new GeneratePdfCdeMagasin();
    }



    /**
     * @Route("/generer-commande-fournisseur", name="generer_commande_fournisseur")
     */
    public function soumissionCommande(Request $request)
    {
        $codeSociette = $this->getSecurityService()->getCodeSocieteUser();
        $form = $this->getFormFactory()
            ->createBuilder(SoumissionCommandeType::class, null, [
                'method' => 'POST',
            ])
            ->getForm();

        $form->handleRequest($request);

        $this->logUserVisit('generer_commande_fournisseur');
        if ($form->isSubmitted()) {
            $this->soumettreAValider($form);
        }


        return $this->render('magasin/commande/soumission/soumissionCommandeFournisseur.html.twig', [
            'form' => $form->createView()
        ]);
    }


    public function soumettreAValider(FormInterface $form)
    {

        $numCommande = $form->get('numCmde')->getData();
        $fileName = "Commande_Fournisseur_{$numCommande}.pdf";
        $bcSoumisMagasinDto = new  BcSoumisMagasinDTO();


        $bcSoumisMagasinDto->numeroCommande = $numCommande;
        $bcSoumisMagasinDto->operateur = $this->getUserName();
        $bcSoumisMagasinDto->numeroCommande = $numCommande;
        $bcSoumisMagasinDto->statut = "Soumis à validation";
        $bcSoumisMagasinDto->dateHeureSoumission = new \DateTime();



        $isNumCmdExist = true; // Change logic on Model ->isExist($numCommande)

        if (!$isNumCmdExist) {
            return;
        }

        $isCopiedToDWFilePath = $this->generatePdfCdeMagasin->copyToDOCUWARE(
            $fileName,
            $numCommande
        );

        if ($isCopiedToDWFilePath) {
            $bcSoumisMagasinDto->deposerDw = true;
        };

        $this->cdeSoumissionModel->enregistrerBcSoumisMagasin($bcSoumisMagasinDto);
        $this->historiqueOperation->sendNotificationCreation('Votre demande a été enregistrée', $bcSoumisMagasinDto->numeroCommande, 'profil_acceuil', true);
    }
}
