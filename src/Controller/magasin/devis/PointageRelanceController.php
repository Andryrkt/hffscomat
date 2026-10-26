<?php

namespace App\Controller\magasin\devis;


use App\Controller\Controller;
use App\Dto\Magasin\Devis\PointageRelanceDto;
use App\Entity\magasin\devis\DevisMagasin;
use App\Entity\magasin\devis\PointageRelance;
use App\Factory\magasin\devis\PointageRelanceFactory;
use App\Form\magasin\devis\PointageRelanceType;
use App\Model\magasin\devis\Pointage\PointageRelanceModel;
use App\Service\autres\AutoIncDecService;
use App\Service\historiqueOperation\EtapeOperationException;
use App\Service\historiqueOperation\HistoriqueOperationDevisMagasinService;
use App\Service\historiqueOperation\suivi\SuiviOperationService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/magasin/dematerialisation")
 */
class PointageRelanceController extends Controller
{
    private const TYPE_OPERATION_CREATION = 5;

    /**
     * Affiche le formulaire de pointage relance dans un modal (appel AJAX)
     * @Route("/pointage-relance-form/{numeroDevis}", name="api_devis_magasin_relance_client_form")
     */
    public function pointageRelanceForm(?string $numeroDevis = null): Response
    {
        $dto = (new PointageRelanceFactory)->create($numeroDevis);
        $form = $this->getFormFactory()->createNamed('', PointageRelanceType::class, $dto, [
            'action' => $this->getUrlGenerator()->generate('api_devis_magasin_relance_client_submit')
        ]);

        return $this->render('magasin/devis/pointage_relance/_form.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * Traite la soumission du formulaire de pointage relance
     * @Route("/pointage-relance-submit", name="api_devis_magasin_relance_client_submit", methods={"POST"})
     */
    public function submitPointageRelanceForm(Request $request): Response
    {
        $dto = new PointageRelanceDto();
        $form = $this->getFormFactory()->createNamed('', PointageRelanceType::class, $dto);

        // handleRequest est pour les données de formulaire standard (x-www-form-urlencoded)
        // Pour les requêtes JSON, nous devons décoder le contenu et le soumettre au formulaire.
        $data = json_decode($request->getContent(), true);

        // Si json_decode échoue ou si les données sont vides, renvoyer une erreur
        if ($data === null) {
            return $this->jsonResponse(['success' => false, 'message' => 'Invalid JSON payload.'], 400);
        }

        $form->submit($data);

        if ($form->isSubmitted() && $form->isValid()) {
            // Code Société de l'utilisateur
            $codeSociete = $this->getSecurityService()->getCodeSocieteUser();
            $numeroDevis = (string) $data['numeroDevis'];

            // suivi de chaque étape (durée, succès / échec) enregistré avec l'historique de l'opération
            $suivi = SuiviOperationService::demarrer(SuiviOperationService::TYPE_DEVIS_NEG_POINTAGE_RELANCE, (string) $numeroDevis);

            try {
                $pointageRelanceModel = new PointageRelanceModel();

                $pointageRelanceEntity = $suivi->etape('Enregistrement base de données', function () use ($data, $codeSociete, $pointageRelanceModel) {
                    $pointageRelanceEntity = (new PointageRelanceFactory())->map($data, $this->getUserName(), $this->numeroRelance($data['numeroDevis'], $codeSociete), $codeSociete);
                    $pointageRelanceModel->enregistrerPointageRelance($pointageRelanceEntity);
                    return $pointageRelanceEntity;
                });

                // Mettre à jour le statut de relance du devis
                $suivi->etape('Mise à jour du devis', function () use ($pointageRelanceModel, $pointageRelanceEntity, $codeSociete) {
                    $numeroVersionDevis = $pointageRelanceModel->getNumeroVersionDevis($pointageRelanceEntity->getNumeroDevis(), $codeSociete);
                    $pointageRelanceModel->updateDevis($pointageRelanceEntity, $numeroVersionDevis);
                });
            } catch (EtapeOperationException $e) {
                $message = "Echec du pointage relance du devis numero : $numeroDevis à l'étape « " . $e->getEtape() . " ». Veuillez contacter l'administrateur.";
                $this->historiser($numeroDevis, false, $message);

                return $this->jsonResponse(['success' => false, 'message' => $message], 500);
            }

            $this->historiser($numeroDevis, true, "Pointage relance du devis numero : $numeroDevis enregistré avec succès.");

            return $this->jsonResponse(['success' => true, 'message' => 'Formulaire soumis avec succès.']);
        }

        // Si le formulaire n'est pas valide, renvoyer les erreurs.
        return $this->jsonResponse(['success' => false, 'message' => 'Erreurs de validation.', 'errors' => (string) $form->getErrors(true, false)], 400);
    }

    /**
     * Enregistre l'historique de l'opération (CREATION) avec ses étapes, sans redirection (réponse JSON).
     * Une erreur d'historisation ne doit pas faire échouer la réponse : elle est seulement loggée.
     */
    private function historiser(string $numeroDevis, bool $succes, string $message): void
    {
        try {
            global $container;
            /** @var HistoriqueOperationDevisMagasinService $historiqueOperation */
            $historiqueOperation = $container->get(HistoriqueOperationDevisMagasinService::class);
            $historiqueOperation->enregistrer($numeroDevis, self::TYPE_OPERATION_CREATION, $succes, $message);
        } catch (\Throwable $e) {
            error_log("Historisation du pointage relance du devis $numeroDevis impossible : " . $e->getMessage());
        }
    }

    /**
     * Génére le numéro de relance
     *
     * @param int $numeroDevis
     * @param string $codeSociete
     * @return int
     */
    private function numeroRelance(int $numeroDevis, string $codeSociete): int
    {
        $pointageRelanceModel = new PointageRelanceModel();
        $numeroRelanceMax = $pointageRelanceModel->getDernierNumeroRelance($numeroDevis, $codeSociete);
        return AutoIncDecService::autoIncrement($numeroRelanceMax);
    }
}
