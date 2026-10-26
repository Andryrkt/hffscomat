<?php

namespace App\Controller\magasin\devis\Soumission;

use App\Controller\Controller;
use App\Controller\Traits\PdfConversionTrait;
use App\Dto\Magasin\Devis\Soumission\SoumissionDto;
use App\Factory\magasin\devis\Soumission\VerificationPrixFactory;
use App\Form\magasin\devis\Soumission\VerificationPrixType;
use App\Model\magasin\devis\Soumission\SoumissionModel;
use App\Service\EmailService;
use App\Service\fichier\TraitementDeFichier;
use App\Service\fichier\UploderFileService;
use App\Service\genererPdf\magasin\devis\GeneratePdfDeviMagasinVp;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\HistoriqueOperationDevisMagasinService;
use App\Service\historiqueOperation\suivi\SuiviOperationService;
use App\Service\magasin\devis\Fichier\DevisMagasinGenererNameFileService;
use App\Service\magasin\devis\Validation\ValidationSoumissionVerificationPrix;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/dematerialisation")
 */
class DevisNegVerificationPrixController extends Controller
{
    use PdfConversionTrait;

    private HistoriqueOperationDevisMagasinService $historiqueOperationDeviMagasinService;
    private string $cheminBaseUpload;
    private string $cheminCourtUpload;
    private DevisMagasinGenererNameFileService $nameGenerator;
    private UploderFileService $uploader;

    public function __construct()
    {
        parent::__construct();
        global $container;
        $this->historiqueOperationDeviMagasinService = $container->get(HistoriqueOperationDevisMagasinService::class);
        $this->cheminBaseUpload = $_ENV['BASE_PATH_FICHIER'] . '/magasin/devis/';
        $this->cheminCourtUpload = $_ENV['BASE_PATH_FICHIER_COURT'] . '/magasin/devis/';
        $this->nameGenerator = new DevisMagasinGenererNameFileService();
        $this->uploader = new UploderFileService($this->cheminBaseUpload, $this->nameGenerator);
    }

    /**
     * @Route("/soumission-devis-neg-verification-de-prix/{typeSoumission}/{numeroDevis}", name="devis_neg_soumission_verification_prix", defaults={"numeroDevis"=null})
     */
    public function soumission(?string $typeSoumission = null, ?string $numeroDevis = null, Request $request)
    {
        $codeSociette = $this->getSecurityService()->getCodeSocieteUser() ?? 'HF';

        if ((new ValidationSoumissionVerificationPrix())->validateSoumissionVerificationPrixAvantAffichageFormulaire($numeroDevis, $codeSociette)) return $this->redirectToRoute('liste_devis_neg');

        // Création du DTO à partir des paramètres de la requête
        $dto = VerificationPrixFactory::create($typeSoumission, $numeroDevis, $codeSociette);


        //création du formulaire
        $form = $this->getFormFactory()->createBuilder(VerificationPrixType::class, $dto, [
            "fichier_initialise" => (bool)$dto->remoteUrlCourt, // Indique si un fichier existe déjà pour ce devis
        ])->getForm();

        // traitement du formulaire
        $this->traitementFormulaire($form, $request);

        return $this->render('magasin/devis/soumission/verification_prix.html.twig', [
            'form'        => $form->createView(),
            'remoteUrl'   => $dto->remoteUrlCourt,
        ]);
    }

    private function traitementFormulaire(FormInterface $form, Request $request): void
    {
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var SoumissionDto $dto */
            $dto = $form->getData();

            $dto = VerificationPrixFactory::CreateBeforeSoumission($dto, $this->getUserName(), $this->getUserMail());

            if ((new ValidationSoumissionVerificationPrix())->validateSubmittedFile($form, $dto->remoteUrlCourt, $dto->numeroDevis)) return;

            $criteria = (array) ($this->getSessionService()->get('criteria_for_excel_liste_devis_neg') ?? []);
            $nomDeRoute = 'liste_devis_neg'; // route de redirection après soumission
            $nomInputSearch = 'devis_neg_search'; // initialistion de nom de chaque champ ou input

            // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
            $suivi = SuiviOperationService::demarrer(SuiviOperationService::TYPE_DEVIS_NEG_VERIFICATION_PRIX, (string) $dto->numeroDevis);

            try {
                $this->traitementSoumission($form, $dto, $suivi);
            } catch (EtapeOperationException $e) {
                //HISTORISATION DE L'OPERATION EN ECHEC
                $message = "Echec de la vérification de prix du devis numero : " . $dto->numeroDevis . " à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
                $this->historiqueOperationDeviMagasinService->sendNotificationSoumission($message, $dto->numeroDevis, $nomDeRoute, false, $criteria, $nomInputSearch);
            }

            //HISTORISATION DE L'OPERATION
            $message = "la vérification de prix du devis numero : " . $dto->numeroDevis . " a été envoyée avec succès .";
            $this->historiqueOperationDeviMagasinService->sendNotificationSoumission($message, $dto->numeroDevis, $nomDeRoute, true, $criteria, $nomInputSearch);
        }
    }

    /**
     * Traitement de la soumission, découpé en étapes suivies
     *
     * @throws EtapeOperationException si une étape échoue
     */
    private function traitementSoumission(FormInterface $form, SoumissionDto $dto, SuiviOperationService $suivi): void
    {
        /**
         * Enregistrement de fichier excel
         * @var array $nomEtCheminFichiersEnregistrerExcel
         * @var string $nomAvecCheminFichierExcel
         * @var string $nomFichierExcel
         */
        [$nomEtCheminFichiersEnregistrerExcel, $nomAvecCheminFichierExcel, $nomFichierExcel] = $suivi->etape('Enregistrement fichier Excel', function () use ($form, $dto) {
            return $this->enregistrementFichierExcel($form, $dto->numeroDevis);
        });

        /**
         * Enregistrement de fichier uploder
         * @var array $nomEtCheminFichiersEnregistrer
         * @var string $nomAvecCheminFichier
         * @var string $nomFichier
         */
        [$nomEtCheminFichiersEnregistrer, $nomAvecCheminFichier, $nomFichier] = $suivi->etape('Enregistrement fichiers joints', function () use ($form, $dto) {
            return $this->enregistrementFichier($form, $dto->numeroDevis, $dto->numeroVersion, $dto->suffix, explode('@', $dto->userMail)[0], $dto->remoteUrlCourt);
        });

        // creation du page de garde
        $generatePdfDevisMagasin = new GeneratePdfDeviMagasinVp();
        $suivi->etape('Génération page de garde', function () use ($generatePdfDevisMagasin, $dto, $nomAvecCheminFichier) {
            $tableauMarges = $this->tableauMarge($dto->numeroDevis, $dto->codeSociete);
            $tableauMargeReference = $this->tableauMargeReference($dto->numeroDevis, $dto->codeSociete);
            $generatePdfDevisMagasin->genererPdf($dto, $nomAvecCheminFichier, $tableauMarges, $tableauMargeReference);
        });

        //insertion de la page de garde à la position 0
        $traitementDeFichier = new TraitementDeFichier();
        $nomEtCheminFichiersEnregistrer = $traitementDeFichier->insertFileAtPosition($nomEtCheminFichiersEnregistrer, $nomAvecCheminFichier, 0);

        /** @var array fusions des fichiers */
        $nomEtCheminFichierConvertie = $suivi->etape('Conversion PDF (Ghostscript)', function () use ($nomEtCheminFichiersEnregistrer) {
            return $this->ConvertirLesPdf($nomEtCheminFichiersEnregistrer);
        });
        $suivi->etape('Fusion PDF', function () use ($traitementDeFichier, $nomEtCheminFichierConvertie, $nomAvecCheminFichier) {
            $traitementDeFichier->fusionFichers($nomEtCheminFichierConvertie, $nomAvecCheminFichier);
        });

        // Enregistrement de la soumission en base de données
        $suivi->etape('Enregistrement base de données', function () use ($dto, $nomFichier, $nomFichierExcel) {
            (new SoumissionModel())->enregistrerSoumission($dto, $nomFichier, $nomFichierExcel);
        });

        //envoie du fichier dans DW
        $suivi->etape('Copie DocuWare', function () use ($generatePdfDevisMagasin, $nomFichier, $dto) {
            $generatePdfDevisMagasin->exigerCopieReussie($generatePdfDevisMagasin->copyToDWDevisVpMagasin($nomFichier, $dto->numeroDevis));
        });

        //envoie de mail au PM
        if (!empty($nomAvecCheminFichierExcel) && !empty($nomFichierExcel) && $this->estValidationPm($dto)) {
            $suivi->etape('Envoi mail PM', function () use ($dto, $nomAvecCheminFichierExcel, $nomFichierExcel) {
                $envoye = $this->envoyerMailDevisMagasin($dto->numeroDevis, [
                    'filePath' => $nomAvecCheminFichierExcel,
                    'fileName' => $nomFichierExcel,
                ]);
                if (!$envoye) {
                    throw new \RuntimeException("l'envoi du mail à " . $_ENV['MAIL_TO_NEG'] . " a échoué");
                }
            });
        }
    }

    public function tableauMarge(string $numDevis, string $codeSociete): array
    {
        $tableauMargeCat = [];
        $tableauMargeMfn = [];
        $tableauMargeAutres = [];

        $soumissionModel = new SoumissionModel();
        $tableauMarges = $soumissionModel->tableauDeMarge($codeSociete, $numDevis);

        foreach ($tableauMarges as $value) {
            if ($value['constructeur'] == 'CAT') {
                $tableauMargeCat[] = $value;
            } elseif ($value['constructeur'] == 'MFN') {
                $tableauMargeMfn[] = $value;
            } else {
                $tableauMargeAutres[] = $value;
            }
        }

        return [
            'tableauMargeCat' => $tableauMargeCat,
            'tableauMargeMfn' => $tableauMargeMfn,
            'tableauMargeAutres' => $tableauMargeAutres
        ];
    }

    public function tableauMargeReference(string $numDevis, string $codeSociete): array
    {
        $soumissionModel = new SoumissionModel();

        $infoDevis = $soumissionModel->getInfoDeviSansJointure($numDevis, $codeSociete);

        $tableauMargeCat = [];
        $tableauMargeMfn = [];
        $tableauMargeAutres = [];

        if (!empty($infoDevis)) {
            foreach ($infoDevis as $infoDevis) {
                $afficher = $soumissionModel->tableauDeMargeAvecReference($codeSociete, $numDevis, $infoDevis['ref'], $infoDevis['code_agence'], $infoDevis['constructeur']);

                foreach ($afficher as $value) {
                    if ($value['constructeur'] == 'CAT') {
                        $tableauMargeCat[] = $value;
                    } elseif ($value['constructeur'] == 'MFN') {
                        $tableauMargeMfn[] = $value;
                    } else {
                        $tableauMargeAutres[] = $value;
                    }
                }
            }
        }
        // dd($tableauMargeCat, $tableauMargeMfn, $tableauMargeAutres);
        $tableauMargeReference = [
            'tableauMargeCat' => $tableauMargeCat,
            'tableauMargeMfn' => $tableauMargeMfn,
            'tableauMargeAutres' => $tableauMargeAutres
        ];

        // 1. Définir le chemin où enregistrer le fichier Excel
        $cheminFichier = $_ENV['BASE_PATH_FICHIER'] . '/magasin/devis/' . $numDevis . '/marge_ref_' . $numDevis . '.xlsx';
        // 2. Appel de la fonction de génération Excel
        $this->genererExcelTableauMargeReference($tableauMargeReference, $cheminFichier);

        return $tableauMargeReference;
    }

    /**
     * Génère un fichier Excel contenant les trois tableaux de marge par référence (CAT, MFN, AUTRES).
     *
     * @param array $tableauMargeReference Tableau de marge de référence
     * @param string|null $filePath Chemin où enregistrer le fichier
     * @param string $filename Nom du fichier pour téléchargement
     * @return string|void
     */
    public function genererExcelTableauMargeReference(array $tableauMargeReference, ?string $filePath = null, string $filename = "tableau_marge_reference")
    {
        $excelService = new \App\Service\ExcelService();
        return $excelService->genererExcelTableauMargeReference($tableauMargeReference, $filePath, $filename);
    }

    private function enregistrementFichierExcel(FormInterface $form, string $numDevis): array
    {
        $devisPath = $_ENV['BASE_PATH_FICHIER'] . '/magasin/devis/' . $numDevis . '/';
        if (!is_dir($devisPath)) mkdir($devisPath, 0777, true);

        $nomEtCheminFichiersEnregistrer = $this->uploader->getNomsEtCheminFichiers($form, [
            'pattern' => '/^pieceJointExcel$/i',
            'repertoire' => $devisPath,
            'generer_nom_callback' => function (UploadedFile $file) use ($numDevis) {
                return $this->nameGenerator->generateFichierExcelName($numDevis, $file->getClientOriginalExtension());
            }
        ]);

        $nomAvecCheminFichier = $nomEtCheminFichiersEnregistrer[0] ?? '';
        $nomFichier = $nomAvecCheminFichier ? basename($nomAvecCheminFichier) : "";

        return [$nomEtCheminFichiersEnregistrer, $nomAvecCheminFichier, $nomFichier];
    }

    private function enregistrementFichier(FormInterface $form, string $numDevis, int $numeroVersion, string $suffix, string $mail, string $remoteUrl = ""): array
    {
        $devisPath = $_ENV['BASE_PATH_FICHIER'] . '/magasin/devis/' . $numDevis . '/';
        if (!is_dir($devisPath)) mkdir($devisPath, 0777, true);

        $nomEtCheminFichiersEnregistrer = $remoteUrl ? [$remoteUrl] : $this->uploader->getNomsEtCheminFichiers($form, [
            'repertoire' => $devisPath,
            'generer_nom_callback' => function (
                UploadedFile $file,
                int $index
            ) use ($numDevis, $numeroVersion, $suffix, $mail) {
                return $this->nameGenerator->generateVerificationPrixName($file, $numDevis, $numeroVersion, $suffix, $mail, $index);
            }
        ]);

        $nomAvecCheminFichier = $this->nameGenerator->getCheminEtNomDeFichierSansIndex($nomEtCheminFichiersEnregistrer[0]);
        $nomFichier = $this->nameGenerator->getNomFichier($nomAvecCheminFichier);

        return [$nomEtCheminFichiersEnregistrer, $nomAvecCheminFichier, $nomFichier];
    }

    private function estValidationPm(SoumissionDto $dto): bool
    {
        return true;
    }

    /** 
     * Méthode pour envoyer une email pour le PM
     * @param string $numDevis
     * @param array $resultatExport - nom et chemin du fichier excel
     * @return bool true si le mail est envoyé
     */
    public function envoyerMailDevisMagasin(string $numDevis,  array $resultatExport): bool
    {
        return $this->envoyerEmail([
            'to'          => $_ENV['MAIL_TO_NEG'],
            'variables'   => ['numDevis' => $numDevis],
            'attachments' => [
                $resultatExport['filePath'] => $resultatExport['fileName'],
            ],
        ]);
    }

    /** 
     * Méthode pour envoyer un email
     */
    public function envoyerEmail(array $content): bool
    {
        $emailTemplate = "magasin/devis/email/emailDevis.html.twig";

        $emailService = new EmailService($this->getTwig());

        $emailService->getMailer()->setFrom($_ENV['MAIL_FROM_ADDRESS'], 'noreply.scomat.neg');

        return $emailService->sendEmail($content['to'], $content['cc'] ?? [], $emailTemplate, $content['variables'] ?? [], $content['attachments'] ?? []);
    }
}
