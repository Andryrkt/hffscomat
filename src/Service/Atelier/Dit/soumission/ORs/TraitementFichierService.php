<?php

namespace App\Service\atelier\dit\soumission\ORs;

use App\Controller\Traits\PdfConversionTrait;
use App\Dto\atelier\dit\soumission\OrSoumissionDto;
use App\Factory\atelier\Dit\Soumission\OrSoumissionFactory;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\fichier\TraitementDeFichier;
use App\Service\fichier\UploderFileService;
use App\Service\genererPdf\dit\ors\GenererPdfOrSoumisAValidation;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TraitementFichierService
{
    use PdfConversionTrait;

    public function traitementDeFichier(FormInterface $form, OrSoumissionDto $dto, string $email): void
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();

        $numeroOr = $dto->numeroOr;
        $numeroDit = $dto->numeroDit;
        $suffix = $ditOrsoumisAValidationModel->constructeurPieceMagasin($numeroOr)[0]['retour'];

        /** 
         * 1. gestion des pieces jointes et generer le nom du fichier PDF
         * Enregistrement de fichier uploder
         * @var array $nomEtCheminFichiersEnregistrer 
         * @var array $nomFichierEnregistrer 
         * @var string $nomAvecCheminFichier
         * @var string $nomFichier
         */
        [$nomEtCheminFichiersEnregistrer, $nomFichierEnregistrer, $nomAvecCheminFichier, $nomFichier] = $this->enregistrementFichier($form, $dto, $suffix);

        // 2. creation de la page de garde
        $genererPdfOrSoumisAValidation = new GenererPdfOrSoumisAValidation();
        $this->creationPdf($dto, $nomAvecCheminFichier, $genererPdfOrSoumisAValidation, $email);

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

    private function enregistrementFichier(FormInterface $form, OrSoumissionDto $dto, string $suffix): array
    {

        $nameGenerator = new OrGeneratorNameService();
        $numDit = $dto->numeroDit;
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

    public function preparationDesPiecesFaibleAchat(string $numOr, string $codeSociete): array
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();

        $infoOrs = $ditOrsoumisAValidationModel->getInformationOr($numOr, $codeSociete);

        $infoPieceFaibleAchat = [];
        if (!empty($infoOrs)) {
            foreach ($infoOrs as $infoOr) {
                $afficher = $ditOrsoumisAValidationModel->getPieceFaibleActiviteAchat($infoOr['constructeur'], $infoOr['reference'], $numOr, $codeSociete);

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


    private function creationPdf(OrSoumissionDto $dto, string $nomAvecCheminFichier, GenererPdfOrSoumisAValidation $genererPdfOrSoumisAValidation, string $email)
    {

        $numeroOr = $dto->numeroOr;
        $codeSociete = $dto->codeSociete;
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();
        $orSoummissionFactory = new OrSoumissionFactory();


        $OrSoumisAvant = $ditOrsoumisAValidationModel->findOrSoumiAvantMax($numeroOr, $codeSociete);


        $OrSoumisAvantMax = $ditOrsoumisAValidationModel->findOrSoumiAvantMax($numeroOr, $codeSociete);

        // dump($OrSoumisAvantMax);
        $montantPdf = $this->montantpdf($dto, $OrSoumisAvant, $OrSoumisAvantMax, $codeSociete);
        // dd($montantPdf);
        $quelqueaffichage = $this->quelqueAffichage($numeroOr, $codeSociete);

        // information sur les pièces à faible achat
        $pieceFaibleAchat = $this->preparationDesPiecesFaibleAchat($numeroOr, $codeSociete);

        $genererPdfOrSoumisAValidation->GenererPdf($dto, $montantPdf, $quelqueaffichage, $email, $pieceFaibleAchat, $nomAvecCheminFichier);
    }




    private function quelqueAffichage($numOr, $codeSociete)
    {
        $ditModel = new DitModel();
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();

        $numDevis = $ditModel->recupererNumdevis($numOr, $codeSociete);

        $nbSotrieMagasin = $ditOrsoumisAValidationModel->recupNbPieceMagasin($numOr, $codeSociete);
        $nbAchatLocaux = $ditOrsoumisAValidationModel->recupNbAchatLocaux($numOr, $codeSociete);
        $nbPol = $ditOrsoumisAValidationModel->recupNbPol($numOr, $codeSociete);

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

    private function montantpdf($orSoumisValidataion, array $OrSoumisAvant, array $OrSoumisAvantMax, string $codeSociete)
    {
        $recapAvantApres = $this->recuperationAvantApres($OrSoumisAvantMax, $OrSoumisAvant, $codeSociete);
        return [
            'avantApres' => $this->affectationStatut($recapAvantApres)['recapAvantApres'],
            'totalAvantApres' => $this->calculeSommeAvantApres($recapAvantApres),
            'recapOr' => $this->recapitulationOr($orSoumisValidataion),
            'totalRecapOr' => $this->calculeSommeMontant($orSoumisValidataion),
            'nombreStatutNouvEtSupp' => $this->affectationStatut($recapAvantApres)['nombreStatutNouvEtSupp']
        ];
    }

    private function datePlanning($numOr, $numItv, $codeSociete)
    {
        $ditOrsoumisAValidationModel = new DitOrSoumisAValidationModel();

        $datePlannig1 = $ditOrsoumisAValidationModel->recupDatePlanningOR1($numOr, $numItv, $codeSociete);
        $datePlannig2 = $ditOrsoumisAValidationModel->recupDatePlanningOR2($numOr, $numItv, $codeSociete);

        return empty($datePlannig1) ? $datePlannig2[0]['dateplanning2'] : $datePlannig1[0]['dateplanning1'];
    }

    private function recuperationAvantApres(array $OrSoumisAvantMax, array $OrSoumisAvant, string $codeSociete)
    {

        if (!empty($OrSoumisAvantMax)) {
            // Trouver les objets manquants par numero d'intervention dans chaque tableau
            $manquantDansOrSoumisAvantMax = $this->objetsManquantsParNumero($OrSoumisAvantMax, $OrSoumisAvant);
            $manquantDansOrSoumisAvant = $this->objetsManquantsParNumero($OrSoumisAvant, $OrSoumisAvantMax);

            // Ajouter les objets manquants dans chaque tableau
            $OrSoumisAvantMax = array_merge($OrSoumisAvantMax, $manquantDansOrSoumisAvantMax);
            $OrSoumisAvant = array_merge($OrSoumisAvant, $manquantDansOrSoumisAvant);

            // Trier les tableaux par numero d'intervention
            $this->trierTableauParNumero($OrSoumisAvantMax);
            $this->trierTableauParNumero($OrSoumisAvant);
        }


        $recapAvantApres = [];
        $count = is_array($OrSoumisAvant) ? count($OrSoumisAvant) : 0;

        for ($i = 0; $i < $count; $i++) {

            $avant = $OrSoumisAvant[$i] ?? null;
            $avantMax = $OrSoumisAvantMax[$i] ?? null;

            if (!$avant) {
                continue;
            }

            $itv = $avant->getNumeroItv();
            $libelleItv = $avant->getLibellelItv();

            $nbLigAp = $avant->getNombreLigneItv() ?? 0;
            $mttTotalAp = $avant->getMontantItv() ?? 0;

            $nbLigAv = $avantMax->getNombreLigneItv() ?? 0;
            $mttTotalAv = $avantMax->getMontantItv() ?? 0;

            $recapAvantApres[] = [
                'itv' => $itv,
                'libelleItv' => $libelleItv,
                'datePlanning' => $this->datePlanning(
                    $avant->getNumeroOR(),
                    $itv,
                    $codeSociete
                ),
                'nbLigAv' => $nbLigAv,
                'nbLigAp' => $nbLigAp,
                'mttTotalAv' => $mttTotalAv,
                'mttTotalAp' => $mttTotalAp,
            ];
        }

        return $recapAvantApres;
    }

    private function affectationStatut($recapAvantApres)
    {
        $nombreStatutNouvEtSupp = [
            'nbrNouv' => 0,
            'nbrSupp' => 0,
            'nbrModif' => 0,
            'mttModif' => 0
        ];
        //dump($recapAvantApres);
        foreach ($recapAvantApres as &$value) { // Référence les éléments pour les modifier directement
            if ($value['nbLigAv'] === $value['nbLigAp'] && $value['mttTotalAv'] === $value['mttTotalAp']) {
                $value['statut'] = '';
            } elseif ($value['nbLigAv'] !== 0 && $value['mttTotalAv'] !== 0.0 && $value['nbLigAp'] === 0 && $value['mttTotalAp'] === 0.0) {
                //dump($value);
                $value['statut'] = 'Supp';
                $nombreStatutNouvEtSupp['nbrSupp']++;
            } elseif (($value['nbLigAv'] === 0 || $value['nbLigAv'] === '') && $value['mttTotalAv'] === 0.0 || $value['mttTotalAv'] === 0) {
                $value['statut'] = 'Nouv';
                $nombreStatutNouvEtSupp['nbrNouv']++;
            } elseif (($value['nbLigAv'] !== $value['nbLigAp'] || $value['mttTotalAv'] !== $value['mttTotalAp']) && ($value['nbLigAv'] !== 0 || $value['nbLigAv'] !== '' || $value['nbLigAp'] !== 0)) {
                //dump($value);
                $value['statut'] = 'Modif';
                $nombreStatutNouvEtSupp['nbrModif']++;
                $nombreStatutNouvEtSupp['mttModif'] = $nombreStatutNouvEtSupp['mttModif'] + ($value['mttTotalAp'] - $value['mttTotalAv']);
            }
        }
        //dd($recapAvantApres);
        // Retourner le tableau modifié et les statistiques de nouveaux et supprimés
        return [
            'recapAvantApres' => $recapAvantApres,
            'nombreStatutNouvEtSupp' => $nombreStatutNouvEtSupp
        ];
    }

    private function calculeSommeAvantApres($recapAvantApres)
    {
        $totalRecepAvantApres = [
            'premierLigne' => '',
            'deuxiemeLigne' => '',
            'total' => 'TOTAL',
            'totalNbLigAv' => 0,
            'totalNbLigAp' => 0,
            'totalMttTotalAv' => 0,
            'totalMttTotalAp' => 0,
            'dernierLigne' => ''
        ];
        foreach ($recapAvantApres as  $value) {
            $totalRecepAvantApres['totalNbLigAv'] += $value['nbLigAv'] === '' ? 0 : $value['nbLigAv'];
            $totalRecepAvantApres['totalNbLigAp'] += $value['nbLigAp'];
            $totalRecepAvantApres['totalMttTotalAv'] += $value['mttTotalAv'] === '' ? 0 : $value['mttTotalAv'];
            $totalRecepAvantApres['totalMttTotalAp'] += $value['mttTotalAp'];
        }

        return $totalRecepAvantApres;
    }

    private function recapitulationOr($orSoumisValidataion)
    {
        $recapOr = [];
        foreach ($orSoumisValidataion as $orSoumis) {
            $recapOr[] = [
                'itv' => $orSoumis->getNumeroItv(),
                'mttTotal' => $orSoumis->getMontantItv(),
                'mttPieces' => $orSoumis->getMontantPiece(),
                'mttMo' => $orSoumis->getMontantMo(),
                'mttSt' => $orSoumis->getMontantAchatLocaux(),
                'mttLub' => $orSoumis->getMontantLubrifiants(),
                'mttAutres' => $orSoumis->getMontantFraisDivers(),
            ];
        }
        return $recapOr;
    }

    private function calculeSommeMontant($orSoumisValidataion)
    {
        $totalRecapOr = [
            'total' => 'TOTAL',
            'montant_itv' => 0,
            'montant_piece' => 0,
            'montant_mo' => 0,
            'montant_achats_locaux' => 0,
            'montant_lubrifiants' => 0,
            'montant_frais_divers' => 0,
        ];
        foreach ($orSoumisValidataion as $orSoumis) {
            // Faire la somme des montants et les stocker dans le tableau
            $totalRecapOr['montant_itv'] += $orSoumis->getMontantItv();
            $totalRecapOr['montant_piece'] += $orSoumis->getMontantPiece();
            $totalRecapOr['montant_mo'] += $orSoumis->getMontantMo();
            $totalRecapOr['montant_achats_locaux'] += $orSoumis->getMontantAchatLocaux();
            $totalRecapOr['montant_lubrifiants'] += $orSoumis->getMontantLubrifiants();
            $totalRecapOr['montant_frais_divers'] += $orSoumis->getMontantFraisDivers();
        }

        return $totalRecapOr;
    }

    // Fonction pour trouver les numéros d'intervention manquants
    private function objetsManquantsParNumero(array $tableauA, array $tableauB)
    {
        $manquants = [];

        foreach ($tableauB as $objetB) {
            $trouve = false;

            foreach ($tableauA as $objetA) {
                if ($objetA->estEgalParNumero($objetB)) {
                    $trouve = true;
                    break;
                }
            }

            if (!$trouve) {
                $dto = new OrSoumissionDto();
                $dto->numeroOr = $objetB->numeroOr;
                $dto->numeroItv = $objetB->numeroItv ?? 0;

                $manquants[] = $dto;
            }
        }

        return $manquants;
    }

    // Fonction pour trier les tableaux par numero d'intervention
    private function trierTableauParNumero(&$tableau)
    {
        usort($tableau, function ($a, $b) {
            return strcmp($a->getNumeroItv(), $b->getNumeroItv());
        });
    }
}
