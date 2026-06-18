<?php

namespace App\Service\atelier\dit\soumission\Facture;

use App\Controller\Traits\PdfConversionTrait;
use App\Dto\atelier\dit\soumission\DitFactureSoumisAValidationDto;
use App\Mapper\Atelier\Dit\Soumission\DItFactureSoumisAValidationMapper;
use App\Model\Atelier\Dit\Soumission\DitFactureSoumisAValidationModel;
use App\Service\fichier\FileUploaderService;
use App\Service\FusionPdf;
use App\Service\genererPdf\dit\facture\GenererPdfFactureAValidation;
use App\Service\security\SecurityService;
use Symfony\Component\Form\FormInterface;

class TraitementDeFichierService
{
    use PdfConversionTrait;

    private GenererPdfFactureAValidation $genererPdfFacture;
    private FusionPdf $fusionPdf;
    private SecurityService $securityService;

    public function __construct(SecurityService $securityService)
    {
        $this->securityService = $securityService;
        $this->genererPdfFacture = new GenererPdfFactureAValidation();
        $this->fusionPdf = new FusionPdf();
    }

    public function traitmenetDeFichier(DitFactureSoumisAValidationDto $dto, FormInterface $form)
    {
        /** CREATION PDF */
        $pathPageDeGarde = $this->enregistrerPdf($dto);
        $pathFichiers = $this->enregistrerFichiers($form, $dto);

        if ($dto->interneExterne === 'INTERNE') {
            $fileUploaderService = new FileUploaderService($_ENV['BASE_PATH_FICHIER'] . '/vfac/');
            $ficherAfusioner = $fileUploaderService->insertFileAtPosition($pathFichiers, $pathPageDeGarde, 0);
            $fichierConvertie = $this->ConvertirLesPdf($ficherAfusioner);
            $this->fusionPdf->mergePdfs($fichierConvertie, $pathPageDeGarde);
            $this->genererPdfFacture->copyToDwFactureSoumis($dto->numeroSoumission, $dto->numeroFact);
        } else {
            $this->genererPdfFacture->copyToDwFacture($dto->numeroSoumission, $dto->numeroFact);
            $this->genererPdfFacture->copyToDwFactureFichier($dto->numeroSoumission, $dto->numeroFact, $pathFichiers); //d'après le demande de Antsa le 22/08/2025
        }
    }

    private function enregistrerPdf(DitFactureSoumisAValidationDto $dto)
    {
        $ditFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();

        $orSoumisFact = $ditFactureSoumisAValidationModel->recupOrSoumisValidation($dto->numeroOr, $dto->numeroFact, $dto->codeSociete);
        $statut = $this->affectationStatutFac($dto, $ditFactureSoumisAValidationModel);
        $montantPdf = $this->montantpdf($dto, $statut, $orSoumisFact);
        $estFactureConformAOr = $this->estFactureConformAOr($dto);

        return $this->genererPdfFacture->GenererPdfFactureSoumisAValidation($dto, $montantPdf, $this->securityService->getDataService()->getUserMail(), $estFactureConformAOr);
    }


    private function affectationStatutFac(DitFactureSoumisAValidationDto $dto, DitFactureSoumisAValidationModel $ditFactureSoumiAValidationModel): array
    {

        $statutFac = [];
        $nombreStatutControle = [
            'nbrNonValideFacture' => 0,
            'nbrServDebDitDiffServDebFac' => 0,
            'nbrMttValideDiffMttFac' => 0,
        ];

        foreach ($dto->infoFac as $value) {

            $agServFac = (!empty($dto->agServDebDit)) ? ($value['agencedebiteur'] . '-' . $value['servicedebiteur']) : '';

            $nombreItv = $ditFactureSoumiAValidationModel->recupNbrItvDansOR($dto->numeroOr, $dto->codeSociete);
            $statutOrsSoumisValidation = $ditFactureSoumiAValidationModel->recupStatutOr($value['numeroor'], (int)$value['numeroitv'], $dto->codeSociete);

            $conditionDifferenceServDeb =  $dto->agServDebDit !== $agServFac;
            $conditionDifferenceMontant = abs($value['montant'] - $value['montantfactureitv']) > 0.01; // Comparaison avec tolérance
            $conditionPasSoumissionOr = $nombreItv === 0;
            $conditionExiteMotRefuse = strpos($statutOrsSoumisValidation, 'refusée') !== false;
            $conditionStatutDiffValide = $statutOrsSoumisValidation !== 'Validé' && $statutOrsSoumisValidation !== 'Livré';
            $conditionStatutValide = $statutOrsSoumisValidation === 'Validé' || $statutOrsSoumisValidation === 'Livré';

            if ($conditionDifferenceServDeb) {
                $statutFac[] = 'Serv deb DIT # Serv deb FAC';
                $nombreStatutControle['nbrServDebDitDiffServDebFac']++;
            } elseif ($conditionPasSoumissionOr) {
                $statutFac[] = 'INTERVENTION NON SOUMISE A VALIDATION'; // pas de soumission or
                $nombreStatutControle['nbrNonValideFacture']++;
            } elseif ($conditionExiteMotRefuse) {
                $statutFac[] = 'INTERVENTION REFUSEE';
                $nombreStatutControle['nbrNonValideFacture']++;
            } elseif ($conditionStatutDiffValide) {
                $statutFac[] = 'INTERVENTION NON VALIDEE';
                $nombreStatutControle['nbrNonValideFacture']++;
            } elseif ($conditionStatutValide) {
                if ($conditionDifferenceMontant) {
                    if ($dto->migration == 1) {
                        $statutFac[] = 'DIT migrée';
                    } else {
                        $statutFac[] = 'Mtt validé # Mtt facturé';
                    }
                    $nombreStatutControle['nbrMttValideDiffMttFac']++;
                } else {
                    $statutFac[] = 'OK';
                }
            } else {
                $statutFac[] = 'OK';
            }
        }

        return [
            'statutFac' => $statutFac,
            'nombreStatutControle' => $nombreStatutControle
        ];
    }

    private function montantpdf(DitFactureSoumisAValidationDto $dto, array $statut, array $orSoumisFact)
    {

        return [
            'infoItvFac' => $this->infoItvFac($dto, $statut['statutFac']),
            'totalItvFac' => $this->calculeSommeItvFacture($dto),
            'recapOr' => $this->recapitulationOr($orSoumisFact),
            'totalRecapOr' => $this->calculeSommeMontant($orSoumisFact),
            'controleAFaire' => $statut['nombreStatutControle']
        ];
    }

    private function infoItvFac(DitFactureSoumisAValidationDto $dto, array $statut)
    {
        $infoItvFac = [];
        foreach (DItFactureSoumisAValidationMapper::mapFacture($dto) as $value) {
            $infoItvFac[] = [
                'itv' => $value->numeroItv,
                'libelleItv' => $value->libelleItv,
                'statutItv' => $value->statutItv,
                'mttItv' => (float)$value->mttItv,
                'mttFac' => $value->montantFactureItv,
                'AgServDebDit' => empty($value->agServDebDit) ? '-' : $value->agServDebDit,
                'AgServDebFac' => $value->agenceDebiteur . '-' . $value->serviceDebiteur,
                'controleAFaire' => $value->statut
            ];
        }

        for ($i = 0; $i < count($infoItvFac); $i++) {
            $infoItvFac[$i]['statut'] = $statut[$i];
        }

        return $infoItvFac;
    }

    private function calculeSommeItvFacture(DitFactureSoumisAValidationDto $dto)
    {
        $totalItvFacture = [
            'premierLigne' => '',
            'total' => 'TOTAL',
            'statur' => '',
            'totalMttItv' => 0,
            'totalMttFac' => 0,
            'AgServDebDit' => '',
            'AgServDebFac' => '',
            'controleAFaire' => ''
        ];
        foreach (DItFactureSoumisAValidationMapper::mapFacture($dto) as  $value) {

            $totalItvFacture['totalMttItv'] += $value->mttItv;
            $totalItvFacture['totalMttFac'] += $value->montantFactureItv;
        }

        return $totalItvFacture;
    }

    private function recapitulationOr(array $orSoumisFact)
    {
        $recapOr = [];
        foreach ($orSoumisFact as $orSoumis) {
            $recapOr[] = [
                'itv' => $orSoumis['numero_itv'],
                'mttTotal' => $orSoumis['montant_itv'],
                'mttPieces' => $orSoumis['montant_piece'],
                'mttMo' => $orSoumis['montant_mo'],
                'mttSt' => $orSoumis['montant_achats_locaux'],
                'mttLub' => $orSoumis['montant_lubrifiants'],
                'mttAutres' => $orSoumis['montant_divers'],
            ];
        }
        return $recapOr;
    }

    private function calculeSommeMontant(array $orSoumisFact)
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
        foreach ($orSoumisFact as $orSoumis) {
            // Faire la somme des montants et les stocker dans le tableau
            $totalRecapOr['montant_itv'] += $orSoumis['montant_itv'];
            $totalRecapOr['montant_piece'] += $orSoumis['montant_piece'];
            $totalRecapOr['montant_mo'] += $orSoumis['montant_mo'];
            $totalRecapOr['montant_achats_locaux'] += $orSoumis['montant_achats_locaux'];
            $totalRecapOr['montant_lubrifiants'] += $orSoumis['montant_lubrifiants'];
            $totalRecapOr['montant_frais_divers'] += $orSoumis['montant_divers'];
        }

        return $totalRecapOr;
    }

    private function estFactureConformAOr(DitFactureSoumisAValidationDto $dto): string
    {
        $montantItvOr = $this->calculerMontantItvOr($dto);
        $montantFacture = $this->calculerMontantFacture($dto);

        $estFactureDifférentDeOr = $montantFacture != $montantItvOr;

        if ($estFactureDifférentDeOr || ($montantFacture == 0.0 && $montantItvOr == 0.0)) {
            $montantFactureOr = 'NON';
        } else {
            $montantFactureOr = 'OUI';
        }

        return $montantFactureOr;
    }

    private function calculerMontantFacture(DitFactureSoumisAValidationDto $dto): float
    {
        $montantFacture = 0;
        foreach (DItFactureSoumisAValidationMapper::mapFacture($dto) as $value) {
            $montantFacture += $value->montantFactureItv;
        }

        return $montantFacture;
    }

    private function calculerMontantItvOr(DitFactureSoumisAValidationDto $dto): float
    {

        $montantItvOr = 0;
        foreach ($this->filtrerOrSelonLesIntervetnionFac($dto) as $value) {
            $montantItvOr += $value->getMontantItv();
        }

        return $montantItvOr;
    }

    private function filtrerOrSelonLesIntervetnionFac(DitFactureSoumisAValidationDto $dto): array
    {
        $ditFactureSoumisAValidationModel = new DitFactureSoumisAValidationModel();
        $OrSoumis = $ditFactureSoumisAValidationModel->recupInfoOrSelonNumeroOr($dto->numeroOr, $dto->codeSociete);

        $orSoumisValidationRepositoryFiltre = [];
        foreach (DItFactureSoumisAValidationMapper::mapOR($OrSoumis) as $value) {
            foreach (DItFactureSoumisAValidationMapper::mapFacture($dto) as $valueFacture) {
                if ($value->numeroItv == $valueFacture->numeroItv) {
                    $orSoumisValidationRepositoryFiltre[] = $value;
                }
            }
        }

        return $orSoumisValidationRepositoryFiltre;
    }

    public function enregistrerFichiers(FormInterface $form, DitFactureSoumisAValidationDto $dto): array
    {
        $prefix = $dto->interneExterne == 'INTERNE' ? 'factureValidation' : 'facture_client';

        $options = [
            'prefixFichier' => $prefix,
            'numeroDoc' => $dto->numeroFact,
            'numeroVersion' => $dto->numeroSoumission,
        ];

        $fileUploaderService = new FileUploaderService($_ENV['BASE_PATH_FICHIER'] . '/vfac/');
        return $fileUploaderService->getPathFiles($form, $options);
    }
}
