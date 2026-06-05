<?php

namespace App\Controller\dit\Ors;

ini_set('upload_max_filesize', '5M');
ini_set('post_max_size', '5M');

use App\Constants\atelier\dit\soumission\ORs\ConstantStatutOr;
use App\Constants\da\StatutDaConstant;
use App\Controller\Controller;

use App\Controller\Traits\dit\DitOrSoumisAValidationTrait;
use App\Controller\Traits\FormatageTrait;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Entity\admin\StatutDemande;


use App\Entity\dit\DemandeIntervention;
use App\Entity\dit\DitOrsSoumisAValidation;
use App\Factory\atelier\Dit\soumission\OrSoumissionFactory;
use App\Form\atelier\dit\soumission\DitOrsSoumisAValidationType;
use App\Model\dit\DitModel;
use App\Model\dit\DitOrSoumisAValidationModel;
use App\Model\magasin\MagasinListeOrLivrerModel;

use App\Repository\dit\DitOrsSoumisAValidationRepository;
use App\Repository\dit\DitRepository;
use App\Service\atelier\dit\ors\OrGeneratorNameService as OrsOrGeneratorNameService;
use App\Service\dit\ors\OrGeneratorNameService;
use App\Service\fichier\TraitementDeFichier;
use App\Service\fichier\UploderFileService;
use App\Service\FusionPdf;
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
    // use DaTrait;

    private MagasinListeOrLivrerModel $magasinListOrLivrerModel;
    private HistoriqueOperationService $historiqueOperation;
    private DitOrSoumisAValidationModel $ditOrsoumisAValidationModel;
    private DitRepository $ditRepository;
    private DitOrsSoumisAValidationRepository $orRepository;


    // private DemandeApproLRepository $demandeApproLRepository;
    // private DemandeApproLRRepository $demandeApproLRRepository;
    // private DemandeApproRepository $demandeApproRepository;
    // private DaAfficherRepository $daAfficherRepository;

    private $ditModel;
    private $fusionPdf;


    public function __construct()
    {
        parent::__construct();
        $this->magasinListOrLivrerModel = new MagasinListeOrLivrerModel();
        $this->historiqueOperation      = new HistoriqueOperationORService($this->getEntityManager());
        $this->ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $this->ditModel = new DitModel();
        $this->fusionPdf = new FusionPdf();
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

        // verification si l'OR est lié à un DA
        $lierAUnDa = false;



        $numOrBaseDonner = $this->ditOrsoumisAValidationModel->recupNumeroOr($numDit, $codeSociete);

        if (empty($numOrBaseDonner)) {
            $message = "Le DIT n'a pas encore de numéro OR";
            $this->historiqueOperation->sendNotificationSoumission($message, '-', 'dit_index');
        }
        $numOr = $numOrBaseDonner[0]['numor'];

        // vérifier si le catégorie de la DIT est DAILY CHECK et le type de l'OR est 930 sinon bloqué
        // $demandeIntervention = $this->getEntityManager()->getRepository(DemandeIntervention::class)->findOneBy(['numeroDemandeIntervention' => $numDit]);

        // Informix
        $demandeIntervention = $this->ditOrsoumisAValidationModel->findByNumeroDit($numDit);

        $typeOr = $this->ditOrsoumisAValidationModel->recupTypeOr($numOr);
        // $condition1 = $demandeIntervention->getCategorieDemande()->getId() === 10;

        // Informix
        $condition1 = $demandeIntervention["categorie_demande"] === 10;


        $condition2 = $typeOr !== 930;
        if ($condition1 && $condition2) {
            $message = "Merci de vérifier l'OR car le type de l'OR ne correspond pas à la DIT rattaché qui est un DAILY CHECK";
            $this->historiqueOperation->sendNotificationSoumission($message, '-', 'dit_index');
        }

        // factory
        $orSoummissionFactory = new OrSoumissionFactory();

        $dto = $orSoummissionFactory->initialisation($numDit, $numOr, $codeSociete);

        $form = $this->getFormFactory()->createBuilder(DitOrsSoumisAValidationType::class, $dto)->getForm();

        $this->traitementFormulaire($form,  $request, $numDit, $numOr,);

        $this->logUserVisit('dit_insertion_or', [
            'numDit' => $numDit,
        ]); // historisation du page visité par l'utilisateur

        return $this->render('dit/DitInsertionOr.html.twig', [
            'form' => $form->createView(),
            // 'cdtArticleDa' => $cdtArticleDa,
            // 'lierAUnDa' => $lierAUnDa,
        ]);
    }
    private function traitementFormulaire(FormInterface $form, Request $request, string $numDit, string $numOr)
    {

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $orSoummissionFactory = new OrSoumissionFactory();
            $dto = $form->getData();
            $dto = $orSoummissionFactory->apresSoumission($numDit, $numOr, $dto);
            $originalName = $dto->originalNamePj1;
            $numeroOr =  $dto->numeroOr;
            $numeroDit =  $dto->numeroDit;
            $codeSociete =  $dto->codeSociete;
            $numeroVersionMax = $dto->numeroVersion;


            // DONE
            /** DEBUT CONDITION DE BLOCAGE */

            $conditionBloquage = $this->conditionsDeBloquegeSoumissionOr($dto);

            /** FIN CONDITION DE BLOCAGE */
            if ($this->bloquageOrSoumsi($conditionBloquage, $originalName, $numeroOr)) {

                // $orSoumisValidationModel = $this->ditModel->recupOrSoumisValidation($numeroOr, $codeSociete);
                // $orSoumisValidataion = $this->orSoumisValidataion($orSoumisValidationModel, $numeroVersionMax, $numDit, $numeroOr, $codeSociete);

                /** Modification de la colonne statut_or dans la table demande_intervention */
                $this->modificationStatutOr($numDit, $codeSociete);

                /** ENVOIE des DONNEE dans BASE DE DONNEE */

                $this->envoieDonnerDansBd($dto);

                /** CREATION , FUSION, ENVOIE DW du PDF */
                $this->traitementDeFichier($form, $dto);

                /** modifier la colonne numero_or dans la table demande_intervention */
                $this->modificationDuNumeroOrDansDit($numDit, $codeSociete);


                $this->historiqueOperation->sendNotificationSoumission('Le document de controle a été généré et soumis pour validation', $ditInsertionOrSoumis->getNumeroOR(), 'dit_index', true);
            } else {
                $message = "Echec lors de la soumission, . . .";
                $this->historiqueOperation->sendNotificationSoumission($message, $numOr, 'dit_index');
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
        $this->creationPdf($ditInsertionOrSoumis, $orSoumisValidataion, $suffix, $numOr, $nomAvecCheminFichier, $codeSociete, $genererPdfOrSoumisAValidation);

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

    private function enregistrementFichier(FormInterface $form, $numeroOr, $numeroVersion, string $suffix): array
    {

        $nameGenerator = new OrsOrGeneratorNameService();
        $numDit = $ditInsertionOrSoumis->getNumeroDit();
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
            ) use ($nameGenerator, $ditInsertionOrSoumis, $suffix) {
                return $nameGenerator->generateNameFile($file, $numeroOr, $numeroVersion, $suffix, $index);
            }
        ]);


        $nomFichier = $nameGenerator->generateNamePrincipal($numeroOr, $numeroVersion, $suffix);
        $nomAvecCheminFichier = $path . $nomFichier;

        return [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer, $nomAvecCheminFichier, $nomFichier];
    }



    private function conditionsDeBloquegeSoumissionOr(OrSoumissionDto $dto): array
    {

        $numeroOr = $dto->numeroOr;
        $codeSociete =  $dto->codeSociete;
        $numeroDit =  $dto->numeroDit;
        $numclient = $this->ditOrsoumisAValidationModel->getNumcli($numeroOr, $codeSociete);
        $numcli = empty($numclient) ? '' : $numclient[0];
        $nbrNumcli = $this->ditOrsoumisAValidationModel->numcliExiste($numcli, $dto->codeSociete);
        $originalName = $dto->originalNamePj1;
        $nmbrOr_soumis = $dto->nmbrOr_soumis;


        $numOrNomFIchier = array_key_exists(1, explode('_', $originalName)) ? explode('_', $originalName)[1] : '';



        $agServDebiteurBDSql = $dto->info_materiel["agence_debiteur_id"];
        $agServInformix = $this->ditModel->recupAgenceServiceDebiteur($dto->numeroOr, $dto->codeSociete);

        $datePlanning = $this->verificationDatePlanning($numeroOr, $codeSociete, $this->ditOrsoumisAValidationModel);

        $pos = $this->ditOrsoumisAValidationModel->recupPositonOr($numeroOr, $codeSociete);
        $invalidPositions = ['FC', 'FE', 'CP', 'ST'];

        $refClient = $this->ditOrsoumisAValidationModel->recupRefClient($numeroOr, $codeSociete);

        $situationOrSoumis = $dto->statut;

        $countAgServDeb = $this->ditOrsoumisAValidationModel->countAgServDebit($numeroOr, $codeSociete);

        return [
            'nomFichier'            => strpos($originalName, 'Ordre de réparation') !== 0, //OK
            'numeroOrDifferent'     => $numeroOr !== $dto->numeroOr,
            'datePlanningExiste'    => $datePlanning,
            'agenceDebiteur'        => !in_array($agServDebiteurBDSql, $agServInformix),
            'invalidePosition'      => in_array($pos[0]['position'], $invalidPositions),
            'idMaterielDifferent'   => $dto->id_materiel_ips !== (int)$dto->info_materiel['id'],
            'sansrefClient'         => empty($refClient),
            'situationOrSoumis'     => $situationOrSoumis === 'bloquer',
            'countAgServDeb'        => (int)$countAgServDeb > 1,
            'numOrFichier'          => $numOrNomFIchier <> $numeroOr,
            'numcliExiste'          => $nbrNumcli[0] != 'existe_bdd',
            'premierSoumissionDatePlanningInferieurDateDuJour' => $this->premierSoumissionDatePlanningInferieurDateDuJour($numeroOr, $codeSociete, $nmbrOr_soumis),
        ];
    }

    private function bloquageOrSoumsi(array $conditionBloquage, string $originalName, string $numeroOr): bool
    {
        $okey = false;
        if ($conditionBloquage['nomFichier']) {
            $message = "Le fichier '{$originalName}' soumis a été renommé ou ne correspond pas à un OR";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message, '-', 'dit_index');
            exit;
        }
        if ($conditionBloquage['numeroOrDifferent']) {
            $message = "Echec lors de la soumission, le fichier soumis semble ne pas correspondre à la DIT";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
            exit;
        } elseif ($conditionBloquage['datePlanningExiste']) {
            $message = "Echec de la soumission car il existe une ou plusieurs interventions non planifiées dans l'OR";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
            exit;
        } elseif ($conditionBloquage['agenceDebiteur']) {
            $message = "Echec de la soumission car l'agence / service débiteur de l'OR ne correspond pas à l'agence / service de la DIT";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
            exit;
        } elseif ($conditionBloquage['invalidePosition']) {
            $message = "Echec de la soumission de l'OR, la position de l'OR est parmis 'FC', 'FE', 'CP', 'ST'";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
            exit;
        } elseif ($conditionBloquage['idMaterielDifferent']) {
            $message = "Echec de la soumission car le materiel de l'OR ne correspond pas au materiel de la DIT";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
            exit;
        } elseif ($conditionBloquage['sansrefClient']) {
            $message = "Echec de la soumission car la référence client est vide.";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
            exit;
        } elseif ($conditionBloquage['situationOrSoumis']) {
            $message = "Echec de la soumission de l'OR . . . un OR est déjà en cours de validation ";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
        } elseif ($conditionBloquage['countAgServDeb']) {
            $message = "Echec de la soumission de l'OR . . . un OR a plusieurs service débiteur ";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
        } elseif ($conditionBloquage['numOrFichier']) {
            $message = "Echec de la soumission de l'OR . . . le numéro OR ne correspond pas ";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
        }
        // elseif ($conditionBloquage['datePlanningInferieureDateDuJour']) {
        //     $message = "Echec de la soumission de l'OR . . . la date de planning est inférieure à la date du jour";
        //     $this->historiqueOperation->sendNotificationSoumission($message, $ditInsertionOrSoumis->getNumeroOR(), 'dit_index');
        // } 
        // elseif ($conditionBloquage['articleDas']) {
        //     $message = "Echec de la soumission de l'OR . . . incohérence entre le bon d’achat validé et celui saisi dans l’OR";
        //     $okey = false;
        //     $this->historiqueOperation->sendNotificationSoumission($message, $ditInsertionOrSoumis->getNumeroOR(), 'dit_index');
        // } 
        elseif ($conditionBloquage['numcliExiste']) {
            $message = "La soumission n'a pas pu être effectuée car le client rattaché à l'OR est introuvable";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message, $numeroOr, 'dit_index');
        } elseif ($conditionBloquage['premierSoumissionDatePlanningInferieurDateDuJour']) {
            $message = " Impossible de soumettre l’OR, la date de planning est déjà dépassée";
            $okey = false;
            $this->historiqueOperation->sendNotificationSoumission($message,  $numeroOr, 'dit_index');
        } else {
            $okey = true;
        }
        return $okey;
    }

    private function creationPdf($ditInsertionOrSoumis, $orSoumisValidataion, string $suffix, string $numOr, string $nomAvecCheminFichier, string $codeSociete, GenererPdfOrSoumisAValidation $genererPdfOrSoumisAValidation)
    {
        /** @var DitOrsSoumisAValidationRepository $repository */
        $repository = $this->getEntityManager()->getRepository(DitOrsSoumisAValidation::class);
        $OrSoumisAvant = $repository->findOrSoumiAvant($ditInsertionOrSoumis->getNumeroOR(), $codeSociete);
        // dump($OrSoumisAvant);
        $OrSoumisAvantMax = $repository->findOrSoumiAvantMax($ditInsertionOrSoumis->getNumeroOR(), $codeSociete);
        // dump($OrSoumisAvantMax);
        $montantPdf = $this->montantpdf($orSoumisValidataion, $OrSoumisAvant, $OrSoumisAvantMax, $codeSociete);
        // dd($montantPdf);
        $quelqueaffichage = $this->quelqueAffichage($ditInsertionOrSoumis->getNumeroOR(), $codeSociete);

        // information sur les pièces à faible achat
        $pieceFaibleAchat = $this->preparationDesPiecesFaibleAchat($numOr, $codeSociete);

        $genererPdfOrSoumisAValidation->GenererPdf($ditInsertionOrSoumis, $montantPdf, $quelqueaffichage, $this->nomUtilisateur()['mailUtilisateur'], $suffix, $pieceFaibleAchat, $nomAvecCheminFichier);
    }

    private function modificationStatutOr($numDit, $codeSociete)
    {
        // <Original
        // $demandeIntervention = $this->getEntityManager()->getRepository(DemandeIntervention::class)->findOneBy(['numeroDemandeIntervention' => $numDit, 'codeSociete' => $codeSociete]);

        // Informix
        $ditModel = new DitModel();
        $ditModel->updateStatut($numDit, $codeSociete,  ConstantStatutOr::STATUT_SOUMIS_A_VALIDATION);
    }

    private function envoieDonnerDansBd($dto)
    {

        $ditModel = new DitModel();
        $ditModel->enregistrerDit($dto);
    }

    private function modificationDuNumeroOrDansDit($numDit, $ditInsertionOrSoumis, $codeSociete)
    {
        $dit = $this->getEntityManager()->getRepository(DemandeIntervention::class)->findOneBy(['numeroDemandeIntervention' => $numDit, 'codeSociete' => $codeSociete]);


        $dit->setNumeroOR($ditInsertionOrSoumis->getNumeroOR());
        // recuperation du statut DIT CLOTUREE VALIDER
        $statutCloturerValider = $this->getEntityManager()->getRepository(StatutDemande::class)->find(DemandeIntervention::STATUT_CLOTUREE_VALIDER);
        $dit->setIdStatutDemande($statutCloturerValider);

        $this->getEntityManager()->flush();
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
