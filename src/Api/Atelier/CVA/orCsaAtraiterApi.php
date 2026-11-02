<?php

namespace App\Api\Atelier\CVA;

use App\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/api/atelier/cva")
 */
class OrCsaATraiterApi extends Controller
{
    /**
     * Envoie les OR sélectionnés pour traitement.
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

        // -----------------------------------------------------------------
        // 🚧 TRAITEMENT MÉTIER (À décommenter et adapter plus tard)
        // -----------------------------------------------------------------
        /*
        try {
            // Exemple de logique : mise à jour du statut dans demandeIntervention
            // $entityManager = $this->getDoctrine()->getManager();
            
            foreach ($orsSelectionnes as $numOr) {
                // Requête pour changer le statut_or
                // Exemple avec un Model :
                // $this->orCsaATraiterListeModel->updateStatutOr($numOr, 'ENVOYE_POUR_TRAITEMENT', $codeSociete);
                
                // Ou avec Doctrine (si demandeIntervention est une entité) :
                // $dit = $entityManager->getRepository(DemandeIntervention::class)->findOneBy(['numeroOr' => $numOr]);
                // if ($dit) {
                //     $dit->setStatutOr('ENVOYE_POUR_TRAITEMENT');
                // }
            }
            
            // $entityManager->flush();
            
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour : ' . $e->getMessage()
            ], 500);
        }
        */
        // -----------------------------------------------------------------

        // 2. Retour de succès (immédiat pour l'instant)
        return new JsonResponse([
            'success' => true,
            'ors'     => $orsSelectionnes,
            'message' => 'Les OR ont bien été transmis.'
        ]);
    }
}
