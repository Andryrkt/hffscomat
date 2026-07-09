<?php

namespace App\Service\atelier\dit\soumission\Devis;

use App\Controller\Traits\PdfConversionTrait;
use App\Dto\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationDto;
use App\Mapper\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationMapper;
use App\Model\Atelier\Dit\Soumission\Devis\DitDevisSoumisAValidationModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\autres\MontantPdfService;
use App\Service\fichier\FileUploaderService;
use App\Service\genererPdf\dit\devis\GenererPdfDevisSoumisAValidation;
use App\Service\security\SecurityService;
use Symfony\Component\Form\FormInterface;

class TraitementDeFicherService
{
    use PdfConversionTrait;

    private DitDevisSoumisAValidationModel $ditDevisSoumisAValidationModel;
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
        $this->ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();
    }

    public function traitementDeFicher(FormInterface $form, DitDevisSoumisAValidationDto $dto)
    {
        $generePdfDevis = new GenererPdfDevisSoumisAValidation();
        $chemin = $_ENV['BASE_PATH_FICHIER'] . '/dit/dev/';
        $fileUploader = new FileUploaderService($chemin);


        $suffix = $this->ditDevisSoumisAValidationModel->constructeurPieceMagasin($dto->numeroDevis, $dto->codeSociete)[0]['retour'];
        //recuperation du fichier ajouter par l'utilisateur
        $file =  $form->get('pieceJoint01')->getData();

        if ($dto->type == 'VP') {
            //generer le nom du fichier
            $nomFichierGenerer = "sctverificationprix_{$dto->numeroDevis}-{$dto->numeroVersion}#{$suffix}~{$dto->tacheValidateur}.pdf";
            $nomFichierGenererSansTache = "sctverificationprix_{$dto->numeroDevis}-{$dto->numeroVersion}#{$suffix}.pdf";
            $nomFichierCtrl = "sctdevisctrl_{$dto->numeroDevis}-{$dto->numeroVersion}#{$suffix}.pdf";

            // telecharger le fichier en copiant sur son repertoire
            $fileUploader->uploadFileSansName($file, $nomFichierGenerer);

            // creation du pdf de verification de prix
            $tableauMarge = $this->tableauMarge($dto->numeroDevis, $dto->codeSociete);
            $generePdfDevis->genererPdfVerificationPrix($tableauMarge, $chemin . $nomFichierCtrl);
            // fusion du pdf de verification de prix avec le fichier ajouter par l'utilisateur en le mettant à la dernière position
            $fichierConvertis = $this->ConvertirLesPdf([$chemin . $nomFichierGenererSansTache, $chemin . $nomFichierCtrl]);
            $fileUploaderService = new FileUploaderService($chemin);
            $fusionPdf           = $fileUploaderService->getFusionPdf();
            $fusionPdf->mergePdfs($fichierConvertis, $nomFichierGenerer);

            //envoye des fichier fusionner dans le DW pour les types "Vente" et "Forfait"
            $generePdfDevis->copyToDWFichierDevisSoumisVp($nomFichierGenerer); // copier le fichier de devis dans docuware
        } else {
            $nomFichierCtrl = "sctdevisctrl_{$dto->numeroDevis}-{$dto->numeroVersion}#{$suffix}.pdf";
            //generer le nom du fichier
            $nomFichierGenerer = "sctdevisatelier_{$dto->numeroDevis}-{$dto->numeroVersion}#{$suffix}.pdf";

            // telecharger le fichier en copiant sur son repertoire
            $fileUploader->uploadFileSansName($file, $nomFichierGenerer);

            //pour création du pdf
            $this->creationPdf($dto, $generePdfDevis, $nomFichierCtrl, $dto->codeSociete);

            // envoyer les fichiers dans DW pour les types "Vente" et "Forfait"
            $generePdfDevis->copyToDWDevisSoumis($nomFichierCtrl); // copier le fichier de controlle dans docuware
            $generePdfDevis->copyToDWFichierDevisSoumis($nomFichierGenerer); // copier le fichier de devis dans docuware
        }
    }

    /**
     * Methode pour la création du pdf
     *
     * @param DitDevisSoumisAValidationDto $dto
     * @param GenererPdfDevisSoumisAValidation $generePdfDevis
     * @return void
     */
    private function creationPdf(
        DitDevisSoumisAValidationDto $dto,
        GenererPdfDevisSoumisAValidation $generePdfDevis,
        string $nomFichierCtrl,
        string $codeSociete
    ) {
        $numDevis = $dto->numeroDevis;

        $devisSoumisAvant = $this->donnerDevisSoumisAvant($numDevis, $codeSociete);

        $montantPdfService = new MontantPdfService();
        $montantPdf = $montantPdfService->montantpdf($devisSoumisAvant);

        $quelqueaffichage = $this->quelqueAffichage($numDevis, $codeSociete);

        $variationPrixRefPiece = $this->variationPrixRefPiece($numDevis, $codeSociete);

        $mailUtilisateur = $this->securityService->getDataService()->getUserMail();

        // dd($montantPdf, $quelqueaffichage);
        if ($dto->estCeVente) { // vente
            $generePdfDevis->GenererPdfDevisVente($dto, $montantPdf, $quelqueaffichage, $variationPrixRefPiece, $mailUtilisateur, $nomFichierCtrl);
        } else { // sinom forfait
            $generePdfDevis->GenererPdfDevisForfait($dto, $montantPdf, $quelqueaffichage, $variationPrixRefPiece, $mailUtilisateur, $nomFichierCtrl);
        }
    }

    private function donnerDevisSoumisAvant(string $numDevis, string $codeSociete): array
    {
        $ditDevisSoumisAValidationModel = new DitDevisSoumisAValidationModel();
        return [
            'devisSoumisAvantForfait'    => DitDevisSoumisAValidationMapper::mapArrayToDto($ditDevisSoumisAValidationModel->findDevisSoumiAvantForfait($numDevis, $codeSociete)),
            'devisSoumisAvantMaxForfait' => DitDevisSoumisAValidationMapper::mapArrayToDto($ditDevisSoumisAValidationModel->findDevisSoumiAvantMaxForfait($numDevis, $codeSociete)),
            'devisSoumisAvantVte'        => DitDevisSoumisAValidationMapper::mapArrayToDto($ditDevisSoumisAValidationModel->findDevisSoumiAvant($numDevis, $codeSociete)),
            'devisSoumisAvantMaxVte'     => DitDevisSoumisAValidationMapper::mapArrayToDto($ditDevisSoumisAValidationModel->findDevisSoumiAvantMax($numDevis, $codeSociete)),
        ];
    }

    private function quelqueAffichage(string $numDevis, string $codeSociete): array
    {
        return [
            "numDevis" => $numDevis,
            "sortieMagasin" => $this->estCeSortieMagasin($numDevis, $codeSociete),
            "achatLocaux" => $this->estCeAchatLocaux($numDevis, $codeSociete)
        ];
    }

    private function estCeSortieMagasin(string $numDevis, string $codeSociete): string
    {
        $nbSotrieMagasin = $this->ditDevisSoumisAValidationModel->recupNbPieceMagasin2($numDevis, $codeSociete);
        if (!empty($nbSotrieMagasin) && $nbSotrieMagasin[0]['nbr_sortie_magasin'] !== "0") {
            $sortieMagasin = 'OUI';
        } else {
            $sortieMagasin = 'NON';
        }

        return $sortieMagasin;
    }

    private function estCeAchatLocaux(string $numDevis, string $codeSociete): string
    {
        $nbAchatLocaux = $this->ditDevisSoumisAValidationModel->recupNbAchatLocaux($numDevis, $codeSociete);
        if (!empty($nbAchatLocaux) && $nbAchatLocaux[0]['nbr_achat_locaux'] !== "0") {
            $achatLocaux = 'OUI';
        } else {
            $achatLocaux = 'NON';
        }

        return $achatLocaux;
    }

    private function variationPrixRefPiece(string $numDevis, string $codeSociete): array
    {
        $infoPieceClients = $this->ditDevisSoumisAValidationModel->recupInfoPieceClient($numDevis, $codeSociete);

        $infoPieces = array_map(function ($piece) use ($codeSociete) {
            return $this->ditDevisSoumisAValidationModel->recupInfoPourChaquePiece($piece, $codeSociete);
        }, $infoPieceClients);
        // $infoPieces = [];
        // foreach ($infoPieceClients as $value) {
        //     $infoPieces[] = $this->ditDevisSoumisAValidationModel->recupInfoPourChaquePiece($value);
        // }

        $infoPrix = [];
        if (!empty($infoPieces)) {
            foreach ($infoPieces as $infoPiece) {
                if (!empty($infoPiece)) {
                    $infoPrix[] = [
                        'lineType' => isset($infoPiece[0]) ? ($infoPiece[0]['type_ligne'] ?? '-') : '-',
                        'cst' => isset($infoPiece[0]) ? ($infoPiece[0]['cst'] ?? '-') : '-',
                        'refPieces' => isset($infoPiece[0]) ? ($infoPiece[0]['refpiece'] ?? '-') : '-',
                        'pu1' => isset($infoPiece[0]) ? ($infoPiece[0]['prixvente'] ?? '-') : '-',
                        'datePu1' => isset($infoPiece[0]) ? ($infoPiece[0]['dateligne'] ?? '-') : '-',
                        'pu2' => isset($infoPiece[1]) ? ($infoPiece[1]['prixvente'] ?? '-') : '-',
                        'datePu2' => isset($infoPiece[1]) ? ($infoPiece[1]['dateligne'] ?? '-') : '-',
                        'pu3' => isset($infoPiece[2]) ? ($infoPiece[2]['prixvente'] ?? '-') : '-',
                        'datePu3' => isset($infoPiece[2]) ? ($infoPiece[2]['dateligne'] ?? '-') : '-',
                    ];
                }
            }
        }

        return $infoPrix;
    }

    public function tableauMarge(string $numOr, string $codeSociete): array
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();

        $infoOrs = $ditOrsoumisAValidationModel->getInformationOr($numOr, $codeSociete);

        $tableauMargeCat = [];
        $tableauMargeMfn = [];
        $tableauMargeAutres = [];

        if (!empty($infoOrs)) {
            foreach ($infoOrs as $infoOr) {
                $afficher = $ditOrsoumisAValidationModel->tableauDeMarge($codeSociete, $numOr, $infoOr['reference'], $infoOr['code_agence']);

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
        return [
            'tableauMargeCat' => $tableauMargeCat,
            'tableauMargeMfn' => $tableauMargeMfn,
            'tableauMargeAutres' => $tableauMargeAutres
        ];
    }
}
