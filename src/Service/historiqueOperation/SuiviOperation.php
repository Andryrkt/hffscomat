<?php

namespace App\Service\historiqueOperation;

use App\Entity\admin\historisation\documentOperation\HistoriqueOperationEtape;

/**
 * Suivi étape par étape d'une opération (soumission, validation...).
 *
 * Chaque étape est chronométrée et son statut (succès / échec + message d'erreur)
 * est conservé, pour être enregistré avec l'historique de l'opération
 * via HistoriqueOperationService::setEtapes().
 */
class SuiviOperation
{
    /** @var array<int, array{etape: string, statut: string, dureeMs: int, message: ?string}> */
    private array $etapes = [];

    /**
     * Exécute une étape et enregistre son résultat
     *
     * @param string   $nom        nom lisible de l'étape (ex: "Copie DocuWare")
     * @param callable $traitement traitement de l'étape, sa valeur de retour est renvoyée
     *
     * @return mixed
     *
     * @throws EtapeOperationException si le traitement lève une erreur
     */
    public function etape(string $nom, callable $traitement)
    {
        $debut = microtime(true);

        try {
            $resultat = $traitement();
        } catch (\Throwable $e) {
            $this->ajouterEtape($nom, HistoriqueOperationEtape::STATUT_ECHEC, $debut, $e->getMessage());
            error_log("Échec de l'étape « $nom » : " . $e->getMessage() . " (" . $e->getFile() . ":" . $e->getLine() . ")");
            throw new EtapeOperationException($nom, $e);
        }

        $this->ajouterEtape($nom, HistoriqueOperationEtape::STATUT_SUCCES, $debut);

        return $resultat;
    }

    public function getEtapes(): array
    {
        return $this->etapes;
    }

    private function ajouterEtape(string $nom, string $statut, float $debut, ?string $message = null): void
    {
        $this->etapes[] = [
            'etape'   => $nom,
            'statut'  => $statut,
            'dureeMs' => (int) round((microtime(true) - $debut) * 1000),
            'message' => $message,
        ];
    }
}
