<?php

namespace App\Api\Atelier\CVA;

use App\Controller\Controller;
use App\Factory\Atelier\Dit\DitFactory;
use App\Model\Atelier\CVA\OrCsaATraiterListeModel;
use App\Model\Atelier\Dit\DitModel;
use App\Service\atelier\dit\DitCreationService;
use App\Service\historiqueOperation\Atelier\Dit\HistoriqueOperationDITService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/atelier/cva")
 */
class OrCsaATraiterApi extends Controller
{
    /** @var DitCreationService */
    private $ditCreationService;

    private $orCsaATraiterListeModel;

    /** @var DitModel */
    private $ditModel;

    /** @var HistoriqueOperationDITService */
    private $historiqueOperationDit;

    public function __construct()
    {
        parent::__construct();

        $this->ditCreationService = new DitCreationService(
            $this->getEntityManager(),
            new DitFactory(
                $this->getSecurityService(),
                $this->getEntityManager()
            ),
            new DitModel()
        );

        $this->orCsaATraiterListeModel   = new OrCsaATraiterListeModel($this->getSecurityService());
        $this->historiqueOperationDit    = new HistoriqueOperationDITService($this->getEntityManager());
        $this->ditModel                = new DitModel();
    }

    /**
     * Envoie les OR sélectionnés pour traitement → crée une DIT par OR.
     *
     * @Route("/or-csa/envoyer", name="api_or_csa_envoyer", methods={"POST"})
     */
    public function envoyerPourTraitement(Request $request): JsonResponse
    {
        // 1. Récupération des données JSON envoyées par le JS (fetch)
        $data = json_decode($request->getContent(), true);
        $orsSelectionnes = $data['ors'] ?? [];

        // Sécurité : si aucun OR n'est envoyé
        if (empty($orsSelectionnes)) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Aucun OR sélectionné.'
            ], 400);
        }

        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
        $resultats = [];
        $nbSucces  = 0;
        $nbEchecs  = 0;
        foreach ($orsSelectionnes as $numOr) {
            try {
                // 1. Récupération des données complètes de l'OR
                $orData = $this->orCsaATraiterListeModel->findOrByNumero($numOr, $codeSociete);
                if (empty($orData)) {
                    $nbEchecs++;
                    $resultats[] = [
                        'numeroOr' => $numOr,
                        'success'  => false,
                        'message'  => "OR introuvable : {$numOr}",
                    ];
                    continue;
                }
                // ✅ Récupération des entités Agence/Service à partir des codes IPS
                $em      = $this->getEntityManager();
                $agence  = $em->getRepository(\App\Entity\admin\Agence::class)
                    ->findOneBy([
                        'codeAgence'  => $orData['code_agence'],
                    ]);
                $service = $em->getRepository(\App\Entity\admin\Service::class)
                    ->findOneBy([
                        'codeService' => $orData['code_service'],
                    ]);

                if (!$agence || !$service) {
                    $nbEchecs++;
                    $resultats[] = [
                        'numeroOr' => $numOr,
                        'success'  => false,
                        'message'  => sprintf(
                            'Agence/Service introuvable pour OR %s (agence=%s, service=%s)',
                            $numOr,
                            $orData['code_agence']  ?? 'null',
                            $orData['code_service'] ?? 'null'
                        ),
                    ];
                    continue;
                }

                // ✅ $agenceService avec les clés attendues par la factory
                $agenceService = [
                    'agenceIps'  => $agence,
                    'serviceIps' => $service,
                ];
                $numeroDit = $this->ditModel->getNextNumeroDitOrForCurrentMonth();

                $resultat = $this->ditCreationService->creerDepuisOr(
                    $orData,
                    $codeSociete,
                    $agenceService,
                    $numeroDit
                );


                // 4. Historique si succès
                if (!empty($resultat['success'])) {
                    $this->historiqueOperationDit->enregistrer(
                        'ENVOI_OR_VERS_DIT',
                        (int) ($resultat['numero'] ?? $numOr),
                        $codeSociete
                    );
                    $nbSucces++;
                } else {
                    $nbEchecs++;
                }

                $resultats[] = [
                    'numeroOr'  => $numOr,
                    'numeroDit' => $resultat['numero']  ?? null,
                    'success'   => $resultat['success'] ?? false,
                    'message'   => $resultat['message'] ?? '',
                    'debug'     => $orData,
                ];
            } catch (\Throwable $e) {
                $nbEchecs++;
                $resultats[] = [
                    'numeroOr' => $numOr,
                    'success'  => false,
                    'message'  => 'Erreur : ' . $e->getMessage(),

                ];
            }
        }

        $message = sprintf(
            '%d DIT créée(s) sur %d OR sélectionné(s).',
            $nbSucces,
            count($orsSelectionnes)
        );
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
