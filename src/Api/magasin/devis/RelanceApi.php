<?php

namespace App\Api\magasin\devis;

use App\Constants\Magasin\Devis\PointageRelanceStatutConstant;
use App\Constants\Magasin\Devis\StatutBcNegConstant;
use App\Constants\Magasin\Devis\StatutDevisNegContant;
use App\Controller\Controller;
use App\Entity\magasin\bc\BcMagasin;
use App\Entity\magasin\devis\DevisMagasin;
use App\Entity\magasin\devis\PointageRelance;
use App\Form\magasin\devis\MotifStopRelanceType;
use App\Model\magasin\devis\DevisNegModel;
use App\Model\magasin\devis\ListeDevisMagasinModel;
use App\Model\magasin\devis\Pointage\PointageRelanceModel;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\HistoriqueOperationDevisMagasinService;
use App\Service\historiqueOperation\SuiviOperation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class RelanceApi extends Controller
{
    private const TYPE_OPERATION_MODIFICATION = 3;

    /**
     * @Route("/api/devis/{numeroDevis}/relances", name="api_devis_relances")
     *
     * @param integer $numeroDevis
     * @return void
     */
    // public function relance(int $numeroDevis)
    // {
    //     // Code Société de l'utilisateur
    //     $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

    //     $relances = $this->getEntityManager()->getRepository(PointageRelance::class)->findBy(['numeroDevis' => $numeroDevis, 'codeSociete' => $codeSociete], ['dateDeRelance' => 'DESC']);
    //     $response = [];
    //     foreach ($relances as $relance) {
    //         $response[] = [
    //             'numeroRelance' => $relance->getNumeroRelance(),
    //             'dateRelance' => $relance->getDateDeRelance()->format('d/m/Y'),
    //             'societe' => $relance->getSociete(),
    //             'agence' => $relance->getAgence(),
    //             'utilisateur' => $relance->getUtilisateur(),
    //             'numeroDevis' => $relance->getNumeroDevis()
    //         ];
    //     }
    //     echo json_encode($response);
    //     exit;
    // }

    /**
     * @Route("/api/devis/motif-stop-form", name="api_devis_motif_stop_form", methods={"GET"})
     */
    public function renderMotifForm()
    {
        $form = $this->getFormFactory()->create(MotifStopRelanceType::class);

        return new JsonResponse([
            'html' => $this->getTwig()->render('magasin/devis/shared/_motif_stop_modal.html.twig', [
                'form' => $form->createView(),
            ])
        ]);
    }

    /**
     * @Route("/api/stop-relance/{numeroDevis}", name="api_devis_magasin_stop_relance", methods={"GET", "POST"})
     */
    public function stopRelance(Request $request, string $numeroDevis)
    {
        if ($request->isMethod('GET')) {
            return new JsonResponse([
                'success' => false,
                'message' => "Cette route n'accepte que les requêtes POST. Vous avez envoyé une requête GET. Vérifiez s'il y a une redirection (301/302) qui transforme votre POST en GET (ex: problème de casse dans l'URL ou slash final)."
            ], 405);
        }

        try {
            // Code Société de l'utilisateur (on force 'HF' si vide pour éviter les échecs de requêtes)
            $codeSociete = $this->getSecurityService()->getCodeSocieteUser() ?? 'HF';

            $body = json_decode($request->getContent(), true);
            $motif = $body['motif'] ?? null;
            $utilisateur = $this->getUserName();
            $devisNegModel = new DevisNegModel();

            // suivi de l'étape (durée, succès / échec) enregistré avec l'historique de l'opération
            $suivi = new SuiviOperation();
            $resultatFaux = false;
            $erreurEtape = null;
            try {
                $suivi->etape('Arrêt / reprise de la relance', function () use ($devisNegModel, $numeroDevis, $motif, $utilisateur, &$resultatFaux) {
                    if (!$devisNegModel->stopRelance($numeroDevis, $motif, $utilisateur)) {
                        $resultatFaux = true;
                        throw new \RuntimeException("la mise à jour de l'arrêt / reprise de relance n'a pas abouti");
                    }
                });
                $success = true;
            } catch (EtapeOperationException $erreurEtape) {
                $success = false;
            }
            $this->historiserStopRelance($numeroDevis, $success, $suivi);

            // une exception (et non un simple retour false) garde la réponse d'erreur 500 d'origine
            if ($erreurEtape !== null && !$resultatFaux) {
                return new JsonResponse(['success' => false, 'message' => $erreurEtape->getPrevious()->getMessage()], 500);
            }

            $newStatuts = new \stdClass();
            $relanceClient = false;
            $motifStop = null;

            if ($success) {
                $statuts = $devisNegModel->getStatutRelance($numeroDevis, $codeSociete);
                if ($statuts && !empty($statuts)) {
                    $newStatuts = (object)$statuts;
                }

                // On récupère les infos du devis pour recalculer les droits d'affichage
                $pointageRelanceModel = new PointageRelanceModel();
                $devisData = $pointageRelanceModel->getRelancePourStop($numeroDevis, $codeSociete);

                if ($devisData) {
                    $hasARelancer = in_array(PointageRelanceStatutConstant::POINTAGE_RELANCE_A_RELANCER, [
                        $newStatuts->statut_relance_1 ?? null,
                        $newStatuts->statut_relance_2 ?? null,
                        $newStatuts->statut_relance_3 ?? null
                    ]);

                    $relanceClient = ($devisData['statut_dw'] === StatutDevisNegContant::ENVOYER_CLIENT
                        && $devisData['statut_bc'] === StatutBcNegConstant::EN_ATTENTE_BC
                        && $hasARelancer
                        && !(bool)$devisData['stop_progression_global']);

                    $motifStop = $devisData['motif_stop_global'];
                }
            }

            return new JsonResponse([
                'success' => $success,
                'message' => $success ? "L'opération sur le devis n°$numeroDevis a été effectuée avec succès" : "Erreur lors de l'opération",
                'statuts' => $newStatuts,
                'relanceClient' => $relanceClient,
                'motifStop' => $motifStop
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Enregistre l'historique de l'arrêt / reprise de relance (MODIFICATION) avec ses étapes, sans redirection (réponse JSON).
     * Une erreur d'historisation ne doit pas faire échouer la réponse : elle est seulement loggée.
     */
    private function historiserStopRelance(string $numeroDevis, bool $succes, SuiviOperation $suivi): void
    {
        $message = $succes
            ? "Arrêt / reprise de la relance du devis numero : $numeroDevis effectué avec succès."
            : "Echec de l'arrêt / reprise de la relance du devis numero : $numeroDevis.";

        try {
            global $container;
            /** @var HistoriqueOperationDevisMagasinService $historiqueOperation */
            $historiqueOperation = $container->get(HistoriqueOperationDevisMagasinService::class);
            $historiqueOperation->setEtapes($suivi->getEtapes());
            $historiqueOperation->enregistrer($numeroDevis, self::TYPE_OPERATION_MODIFICATION, $succes, $message);
        } catch (\Throwable $e) {
            error_log("Historisation de l'arrêt de relance du devis $numeroDevis impossible : " . $e->getMessage());
        }
    }
}
