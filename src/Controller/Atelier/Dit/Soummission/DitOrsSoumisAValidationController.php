<?php

namespace App\Controller\Atelier\Dit\Soummission;

ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '5M');

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Controller\Controller;
use App\Controller\Traits\dit\DitOrSoumisAValidationTrait;
use App\Controller\Traits\FormatageTrait;
use App\Dto\Atelier\Dit\soumission\OrSoumissionDto;
use App\Factory\Atelier\Dit\Soumission\OrSoumissionFactory;
use App\Form\Atelier\Dit\soumission\DitOrsSoumisAValidationType;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\atelier\dit\soumission\ORs\OrGeneratorNameService;
use App\Service\atelier\dit\soumission\ORs\ValidationService;
use App\Service\fichier\TraitementDeFichier;
use App\Service\fichier\UploderFileService;
use App\Service\genererPdf\dit\ors\GenererPdfOrSoumisAValidation;
use App\Service\historiqueOperation\HistoriqueOperationORService;
use App\Service\historiqueOperation\HistoriqueOperationService;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitOrsSoumisAValidationController extends Controller
{
    use FormatageTrait;
    use DitOrSoumisAValidationTrait;


    private HistoriqueOperationService $historiqueOperation;

    private DitOrSoumisAValidationModel $ditOrsoumisAValidationModel;

    private DitModel $ditModel;

    public function __construct()
    {
        parent::__construct();
        $this->historiqueOperation      = new HistoriqueOperationORService($this->getEntityManager());
        $this->ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $this->ditModel = new DitModel();
    }

    /**
     * @Route("/soumission-or/{numDit}", name="dit_insertion_or")
     *
     * @return void
     */
    public function insertionOr(Request $request, string $numDit)
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        // recupérer le numéro OR dans la base de donnée IPS
        $numOr = $this->ditOrsoumisAValidationModel->recupNumeroOr($numDit, $codeSociete);

        // factory
        $orSoummissionFactory = new OrSoumissionFactory();
        $dto = $orSoummissionFactory->initialisation($numDit, $numOr, $codeSociete);

        // bloquer l'affichage du formulaire si les condition n'est pa remplis
        $validationService = new ValidationService();
        if (!$validationService->validateAvantAffichageForm($dto)) {
            return;
        }

        $form = $this->getFormFactory()->createBuilder(DitOrsSoumisAValidationType::class, $dto)->getForm();

        $this->traitementFormulaire($form,  $request, $numDit, $numOr,);

        $this->logUserVisit('dit_insertion_or', [
            'numDit' => $numDit,
        ]); // historisation du page visité par l'utilisateur

        return $this->render('atelier/dit/soumission/ors/soumissionOr.html.twig', [
            'form' => $form->createView()
        ]);
    }

    private function traitementFormulaire(FormInterface $form, Request $request, string $numDit, string $numOr)
    {

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orSoummissionFactory = new OrSoumissionFactory();
            $dto = $form->getData();

            $dto = $orSoummissionFactory->apresSoumission($numDit, $numOr, $dto);
 
            // DONE
            /** DEBUT CONDITION DE BLOCAGE */
            $validationService = new ValidationService();
            $conditionBloquage =   $validationService->validateSubmittedFile($form, null, $dto);
            /** FIN CONDITION DE BLOCAGE */
            if (!$conditionBloquage) {

                /** Modification de la colonne statut_or dans la table demande_intervention */
                $this->modificationDuNumeroOrDansDit($dto);


                /** ENVOIE des DONNEE dans BASE DE DONNEE */

                $this->envoieDonnerDansBd($dto);

                /** CREATION , FUSION, ENVOIE DW du PDF */
                $this->traitementDeFichier($form, $dto);

                /** modifier la colonne numero_or dans la table demande_intervention */
                $this->modificationStatutOr($dto);


                $this->historiqueOperation->sendNotificationSoumission('Le document de controle a été généré et soumis pour validation', $numOr, 'dit_liste', true);
            } else {
                $message = "Echec lors de la soumission, . . .";
                $this->historiqueOperation->sendNotificationSoumission($message, $numOr, 'dit_liste');
                exit;
            }
        }
    }

    private function preparationDesPiecesFaibleAchat(string $numOr, string $codeSociete): array
    {
        $infoOrs = $this->ditOrsoumisAValidationModel->getInformationOr($numOr, $codeSociete);

        $infoPieceFaibleAchat = [];
        if (!empty($infoOrs)) {
            foreach ($infoOrs as $infoOr) {
                $afficher = $this->ditOrsoumisAValidationModel->getPieceFaibleActiviteAchat($infoOr['constructeur'], $infoOr['reference'], $numOr, $codeSociete);

                if (isset($afficher[0]) && $afficher[0]['retour'] === 'a afficher') {

                    $infoPieceFaibleAchat[] = [
                        'numero_itv'        => $infoOr['numero_itv'],
                        'libelle_itv'       => $infoOr['libelle_itv'],
                        'constructeur'      => $infoOr['constructeur'],
                        'reference'         => $infoOr['reference'],
                        'designation'       => $infoOr['designation'],
                        'pmp'               => $afficher[0]['pmp'],
                        'date_derniere_cde' => $afficher[0]['date_derniere_cde'],
                    ];
                }
            }
        }
        return $infoPieceFaibleAchat;
    }

    private function traitementDeFichier(FormInterface $form, OrSoumissionDto $dto): void
    {

        $numeroOr = $dto->numeroOr;
        $numeroVersion = $dto->numeroVersion;
        $numeroDit = $dto->numeroDit;
        $suffix = $this->ditOrsoumisAValidationModel->constructeurPieceMagasin($numeroOr)[0]['retour'];

        /** 
         * 1. gestion des pieces jointes et generer le nom du fichier PDF
         * Enregistrement de fichier uploder
         * @var array $nomEtCheminFichiersEnregistrer 
         * @var array $nomFichierEnregistrer 
         * @var string $nomAvecCheminFichier
         * @var string $nomFichier
         */
        [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer, $nomAvecCheminFichier, $nomFichier] = $this->enregistrementFichier($form, $numeroOr, $numeroVersion, $suffix);

        // 2. creation de la page de garde
        $genererPdfOrSoumisAValidation = new GenererPdfOrSoumisAValidation();
        $this->creationPdf($dto, $suffix, $nomAvecCheminFichier, $genererPdfOrSoumisAValidation);

        // 3. ajout du page de garde à la premier position
        $traitementDeFichier = new TraitementDeFichier();
        $nomEtCheminFichiersEnregistrer = $traitementDeFichier->insertFileAtPosition($nomEtCheminFichiersEnregistrer, $nomAvecCheminFichier, 0);

        // 4. fusion du page de garde et des pieces jointes (conversion avant la fusion)
        $nomEtCheminFichierConvertie = $this->ConvertirLesPdf($nomEtCheminFichiersEnregistrer);
        $traitementDeFichier->fusionFichers($nomEtCheminFichierConvertie, $nomAvecCheminFichier);


        // 5. fusion de pdf Demande appro avec le pdf OR fusionner
        // $this->fusionPdfDaAvecORfusionner($numDit, $mainPdf, $daAfficherRepository);

        // 6.  envoyer le pdf fusionner dans DW
        $genererPdfOrSoumisAValidation->copyToDw($nomFichier, $numeroDit);
    }

    private function enregistrementFichier(FormInterface $form, $dto, string $suffix): array
    {

        $nameGenerator = new OrGeneratorNameService();
        $numDit = $dto->getNumeroDit();
        $cheminBaseUpload = $_ENV['BASE_PATH_FICHIER'] . '/dit/';
        $uploader = new UploderFileService($cheminBaseUpload, $nameGenerator);
        $path = $cheminBaseUpload . $numDit . '/';
        if (!is_dir($path)) {
            mkdir($path, 0777, true);
        }

        /**
         * recupère les noms + chemins dans un tableau et les noms dans une autre
         * @var array $nomEtCheminFichiersEnregistrer
         * @var array $nomFichierEnregistrer
         */
        [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer] = $uploader->getFichiers($form, [
            'repertoire' => $path,
            'generer_nom_callback' => function (
                UploadedFile $file,
                int $index
            ) use ($nameGenerator, $dto, $suffix) {
                return $nameGenerator->generateNameFile($file, $dto->numeroOr, $dto->numeroVersion, $suffix, $index);
            }
        ]);


        $nomFichier = $nameGenerator->generateNamePrincipal($dto->numeroOr, $dto->numeroVersion, $suffix);
        $nomAvecCheminFichier = $path . $nomFichier;

        return [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer, $nomAvecCheminFichier, $nomFichier];
    }


    private function creationPdf(OrSoumissionDto $dto, string $suffix, string $nomAvecCheminFichier, GenererPdfOrSoumisAValidation $genererPdfOrSoumisAValidation)
    {

        $numeroOr = $dto->numeroOr;
        $codeSociete = $dto->codeSociete;

        // /** @var DitOrsSoumisAValidationRepository $repository */
        // $repository = $this->getEntityManager()->getRepository(DitOrsSoumisAValidation::class);

        // Original 
        // $OrSoumisAvant = $repository->findOrSoumiAvant($ditInsertionOrSoumis->getNumeroOR(), $codeSociete)
        // Informix
        $OrSoumisAvant = $this->orSoumissionFactory
            ->fromFirstResult($this->ditOrsoumisAValidationModel->findOrSoumiAvantMax($numeroOr, $codeSociete));

        //    Original 
        // dump($OrSoumisAvant);
        // $OrSoumisAvantMax = $repository->findOrSoumiAvantMax($ditInsertionOrSoumis->getNumeroOR(), $codeSociete);
        //    Informix

        $OrSoumisAvantMax = $this->orSoumissionFactory
            ->fromFirstResult($this->ditOrsoumisAValidationModel->findOrSoumiAvantMax($numeroOr, $codeSociete));

        // dump($OrSoumisAvantMax);
        $montantPdf = $this->montantpdf($dto, $OrSoumisAvant, $OrSoumisAvantMax, $codeSociete);
        // dd($montantPdf);
        $quelqueaffichage = $this->quelqueAffichage($numeroOr, $codeSociete);

        // information sur les pièces à faible achat
        $pieceFaibleAchat = $this->preparationDesPiecesFaibleAchat($numeroOr, $codeSociete);

        $genererPdfOrSoumisAValidation->GenererPdf($dto, $montantPdf, $quelqueaffichage, $this->nomUtilisateur()['mailUtilisateur'], $suffix, $pieceFaibleAchat, $nomAvecCheminFichier);
    }

    private function modificationStatutOr(OrSoumissionDto $dto)
    {
        // Informix
        $ditModel = new DitModel();
        $ditModel->updateStatut($dto->numeroDit, $dto->codeSociete,  ConstantStatutOr::STATUT_SOUMIS_A_VALIDATION);
    }

    private function envoieDonnerDansBd(OrSoumissionDto $dto)
    {
        $ditModel = new DitModel();
        $ors = $ditModel->recupOrSoumisValidation($dto->numeroOr, $dto->codeSociete);
        $ditModel->enregistrerDit($dto, $ors);
    }

    private function modificationDuNumeroOrDansDit(OrSoumissionDto $dto)
    {
        $ditModel = new DitModel();
        $ditModel->updateNumeroOr($dto, ConstantStatutOr::STATUT_SOUMIS_A_VALIDATION);
    }

    private function quelqueAffichage($numOr, $codeSociete)
    {
        $numDevis = $this->ditModel->recupererNumdevis($numOr, $codeSociete);

        $nbSotrieMagasin = $this->ditOrsoumisAValidationModel->recupNbPieceMagasin($numOr, $codeSociete);
        $nbAchatLocaux = $this->ditOrsoumisAValidationModel->recupNbAchatLocaux($numOr, $codeSociete);
        $nbPol = $this->ditOrsoumisAValidationModel->recupNbPol($numOr, $codeSociete);

        if (!empty($nbSotrieMagasin) && $nbSotrieMagasin[0]['nbr_sortie_magasin'] !== "0") {
            $sortieMagasin = 'OUI';
        } else {
            $sortieMagasin = 'NON';
        }

        if (!empty($nbAchatLocaux) && $nbAchatLocaux[0]['nbr_achat_locaux'] !== "0") {
            $achatLocaux = 'OUI';
        } else {
            $achatLocaux = 'NON';
        }

        if (!empty($nbPol) && $nbPol[0]['nbr_pol'] !== "0") {
            $pol = 'OUI';
        } else {
            $pol = 'NON';
        }

        return [
            "numDevis" => $numDevis,
            "sortieMagasin" => $sortieMagasin,
            "achatLocaux" => $achatLocaux,
            "pol" => $pol,
        ];
    }

    function compareTableaux($a, $b)
    {
        if (count($a) != count($b)) {
            return false;
        }

        foreach ($a as $item) {
            $found = false;
            foreach ($b as $key => $value) {
                if ($item == $value) {
                    $found = true;
                    unset($b[$key]);
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }

        return true;
    }
}
