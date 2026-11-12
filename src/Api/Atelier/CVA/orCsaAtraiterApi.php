<?php

namespace App\Api\Atelier\CVA;


use App\Controller\Controller;
use App\Entity\admin\Agence;
use App\Entity\admin\Service;
use App\Factory\Atelier\Dit\DitFactory;
use App\Factory\Atelier\Dit\soumission\OrCsaSoumissionDtoFactory;
use App\Model\Atelier\CVA\OrCsaATraiterListeModel;
use App\Model\Atelier\CVA\OrCsaSoumissionPdfModel;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\DitOrSoumisAValidationModel;
use App\Service\atelier\dit\DitCreationService;
use App\Service\genererPdf\atelier\CVA\GenererPdfOrCsa;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/atelier/cva")
 */
class OrCsaATraiterApi extends Controller
{
    private const CODE_HISTORIQUE_ENVOI = 'ENVOI_OR_VERS_DIT';

    private DitCreationService $ditCreationService;
    private OrCsaATraiterListeModel $orCsaATraiterListeModel;
    private DitModel $ditModel;
    private HistoriqueOperationDITService $historiqueOperationDit;
    private DitOrSoumisAValidationModel $ditOrSoumisAValidationModel;
    private OrCsaSoumissionPdfModel $orCsaSoumissionPdfModel;
    private GenererPdfOrCsa $genererPdfOrCsa;
    private OrCsaSoumissionDtoFactory $orCsaSoumissionDtoFactory;

    public function __construct()
    {
        parent::__construct();

        $this->ditCreationService = new DitCreationService(
            $this->getEntityManager(),
            new DitFactory($this->getSecurityService(), $this->getEntityManager()),
            new DitModel()
        );

        $this->orCsaATraiterListeModel     = new OrCsaATraiterListeModel($this->getSecurityService());
        $this->historiqueOperationDit      = new HistoriqueOperationDITService($this->getEntityManager());
        $this->ditModel                    = new DitModel();
        $this->ditOrSoumisAValidationModel = new DitOrSoumisAValidationModel();
        $this->orCsaSoumissionPdfModel     = new OrCsaSoumissionPdfModel();
        $this->genererPdfOrCsa             = new GenererPdfOrCsa();
        $this->orCsaSoumissionDtoFactory = new OrCsaSoumissionDtoFactory();
    }

    /**
     * @Route("/or-csa/envoyer", name="api_or_csa_envoyer", methods={"POST"})
     */
    public function envoyerPourTraitement(Request $request): JsonResponse
    {
        $orsSelectionnes = json_decode($request->getContent(), true)['ors'] ?? [];

        if (empty($orsSelectionnes)) {
            return new JsonResponse(
                ['success' => false, 'message' => 'Aucun OR sélectionné.'],
                400
            );
        }

        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        $emailUser   = $this->getSecurityService()->getUserEmail();

        $resultats = array_map(
            fn(string $numOr) => $this->traitementOr($numOr, $codeSociete, $emailUser),
            $orsSelectionnes
        );

        $nbSucces = count(array_filter($resultats, static fn(array $r): bool => $r['success']));
        $nbEchecs = count($resultats) - $nbSucces;

        return $this->reponseGlobale($nbSucces, $nbEchecs, count($orsSelectionnes), $resultats);
    }

    // =========================================================================
    // Orchestration d'un OR
    // =========================================================================

    /**
     * Traite un OR de bout en bout : création DIT → MAJ seor_refdem →
     * génération PDF → suivi → historique.
     *
     * @return array{numeroOr:string, numeroDit:?string, success:bool, message:string, pdf?:array}
     */
    private function traitementOr(
        string $numOr,
        string $codeSociete,
        string $email
    ): array {
        try {
            $orData = $this->orCsaATraiterListeModel->findOrByNumero($numOr, $codeSociete);
            if (empty($orData)) {
                return $this->echec($numOr, "OR introuvable : {$numOr}");
            }

            $langueIps   = $this->orCsaSoumissionPdfModel->getLangueIps($orData['numero_client'], $codeSociete);

            $agenceService = $this->resoudreAgenceService($orData);
            if ($agenceService === null) {
                return $this->echec($numOr, sprintf(
                    'Agence/Service introuvable pour OR %s (agence=%s, service=%s)',
                    $numOr,
                    $orData['code_agence']  ?? 'null',
                    $orData['code_service'] ?? 'null'
                ));
            }

            $numeroDit = $this->ditModel->getNextNumeroDitOrForCurrentMonth();
            // $creation  = $this->ditCreationService->creerDepuisOr(
            //     $orData,
            //     $codeSociete,
            //     $agenceService,
            //     $numeroDit
            // );

            // if (empty($creation['success'])) {
            //     return $this->echec(
            //         $numOr,
            //         $creation['message'] ?? 'Échec de création de la DIT',
            //         $creation['numero'] ?? null
            //     );
            // }

            // $this->mettreAJourRefdem($numOr, $numeroDit, $codeSociete);
            $pdfInfo = $this->genererEtDeposerPdf('DIT20110014', $codeSociete, $email, $langueIps);

            // $this->enregistrerSuivi($numOr, $numeroDit, $codeSociete);
            // $this->historiser($numeroDit, $codeSociete);

            return $this->succes($numOr, $numeroDit, $pdfInfo);
        } catch (\Throwable $e) {
            return $this->echec($numOr, 'Erreur : ' . $e->getMessage());
        }
    }

    // =========================================================================
    // Étapes unitaires (une responsabilité chacune)
    // =========================================================================

    private function resoudreAgenceService(array $orData): ?array
    {
        $em      = $this->getEntityManager();
        $agence  = $em->getRepository(Agence::class)
            ->findOneBy(['codeAgence'  => $orData['code_agence']]);
        $service = $em->getRepository(Service::class)
            ->findOneBy(['codeService' => $orData['code_service']]);

        if (!$agence || !$service) {
            return null;
        }

        return ['agenceIps' => $agence, 'serviceIps' => $service];
    }

    private function mettreAJourRefdem(string $numOr, string $numeroDit, string $codeSociete): void
    {
        try {
            $this->orCsaATraiterListeModel->misAjourNumeroDitSurOr($numOr, $numeroDit, $codeSociete);
        } catch (\Throwable $e) {
            error_log("[sav_eor] MAJ seor_refdem échouée pour OR {$numOr} : " . $e->getMessage());
        }
    }

    private function enregistrerSuivi(string $numOr, string $numeroDit, string $codeSociete): void
    {
        try {
            $this->ditOrSoumisAValidationModel->enregistrerOrInitial(
                $numOr,
                $numeroDit,
                $codeSociete,
                1
            );
        } catch (\Throwable $e) {
            error_log("[ors_soumis_a_validation] OR {$numOr} : " . $e->getMessage());
        }
    }

    private function historiser(string $numeroDit, string $codeSociete): void
    {
        $this->historiqueOperationDit->enregistrer(
            self::CODE_HISTORIQUE_ENVOI,
            (int) $numeroDit,
            $codeSociete
        );
    }

    // =========================================================================
    // Génération PDF
    // =========================================================================

    /**
     * @return array{success:bool, message:string, filename?:string}
     */
    private function genererEtDeposerPdf(
        string $numeroDit,
        string $codeSociete,
        string $email,
        string $langueIps
    ): array {
        try {
            $data = $this->orCsaSoumissionPdfModel->findOrByRefDemande($numeroDit, $codeSociete);
            if ($data === null) {
                return $this->pdfEchec("OR introuvable pour DIT {$numeroDit}");
            }

            if (empty($data['numero_or'])) {
                return $this->pdfEchec("Numéro OR absent en base pour DIT {$numeroDit}");
            }

            $lignes   = $this->orCsaSoumissionPdfModel->findLignesByNumOr($data['numero_or']);
            $montants = $this->orCsaSoumissionPdfModel->calculMontants($data['numero_or']);
            $dto = $this->orCsaSoumissionDtoFactory->createFromData($data, $montants, $langueIps, $lignes);

            $filename = sprintf(
                'PROFORMA_INVOICE_%s_%s_%03d.pdf',
                $data['numero_or'],
                (new \DateTime())->format('YmdHis'),
                random_int(0, 999)
            );

            $this->genererPdfOrCsa->genererPdf($dto, $email, $filename, $numeroDit);
            $depose = $this->genererPdfOrCsa->copyToDw($filename, $numeroDit, $langueIps);

            return [
                'success'  => $depose,
                'message'  => $depose
                    ? 'PDF généré et déposé dans DocuWare.'
                    : 'PDF généré mais dépôt DocuWare échoué.',
                'filename' => $filename,
            ];
        } catch (\Throwable $e) {
            error_log("[pdf_or_csa] DIT {$numeroDit} : " . $e->getMessage());
            return $this->pdfEchec($e->getMessage());
        }
    }


    private function pdfEchec(string $raison): array
    {
        return ['success' => false, 'message' => 'PDF non généré : ' . $raison];
    }



    // =========================================================================
    // Construction des retours
    // =========================================================================

    private function succes(string $numOr, string $numeroDit, array $pdfInfo): array
    {
        return [
            'numeroOr'  => $numOr,
            'numeroDit' => $numeroDit,
            'success'   => true,
            'message'   => 'DIT créée. ' . $pdfInfo['message'],
            'pdf'       => $pdfInfo,
        ];
    }

    private function echec(string $numOr, string $message, ?string $numeroDit = null): array
    {
        return [
            'numeroOr'  => $numOr,
            'numeroDit' => $numeroDit,
            'success'   => false,
            'message'   => $message,
        ];
    }

    private function reponseGlobale(
        int $nbSucces,
        int $nbEchecs,
        int $total,
        array $resultats
    ): JsonResponse {
        $message = sprintf('%d DIT créée(s) sur %d OR sélectionné(s).', $nbSucces, $total);
        if ($nbEchecs > 0) {
            $message .= sprintf(' %d échec(s).', $nbEchecs);
        }

        return new JsonResponse([
            'success'  => $nbSucces > 0,
            'message'  => $message,
            'nbSucces' => $nbSucces,
            'nbEchecs' => $nbEchecs,
            'details'  => $resultats,
        ]);
    }
}
