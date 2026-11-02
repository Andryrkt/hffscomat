<?php

namespace App\Service\Atelier\CVA;

use App\Constants\admin\ApplicationConstant;
use App\Constants\atelier\dit\StatutDitConstant;
use App\Dto\Atelier\CVA\OrCsaATraiterSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Service\security\SecurityService;

class FiltreService
{
    private SecurityService $securityService;


    public function __construct(
        SecurityService $securityService
    ) {
        $this->securityService = $securityService;
    }

    public function filtre(OrCsaATraiterSearchDto $orCsaATraiterSearchDto): string
    {
        $selectWhereCondition = new SelectWhereCondition();
        $conditions = [];


        // Si aucune condition, ajouter la condition par défaut (qui s'applique s'il n'y a pas de filtre ajouter par l'utilisateur)
        if (empty($conditions)) {
            $conditionOrNonRefuse = $selectWhereCondition->nlike('statut_or', 'Refus', ['position' => 'starts', 'tableAlias' => 'd0_', 'not' => true]);
            $conditions[] = " AND d0_.statut_or is NULL OR (d0_.statut_or is NOT NULL $conditionOrNonRefuse)";
            $conditions[] =  ' OR d0_.numero_or IS NULL';
            $conditions[] = $selectWhereCondition->in('d0_.id_statut_demande', StatutDitConstant::DEFAULT_STATUT_ID);
        }

        // Filtrer les conditions vides et retourner le résultat
        $conditions = array_filter($conditions, function ($condition) {
            return !empty(trim($condition));
        });

        return implode(' ', $conditions);
    }

    /**
     * Ajoute une condition si elle n'est pas vide
     */
    private function addCondition(array &$conditions, string $condition): void
    {
        if (!empty(trim($condition))) {
            $conditions[] = $condition;
        }
    }

    public function conditionAgenceService(): string
    {
        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->securityService->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);
        if (!$multisuccursale) {
            $agenceIdUser = $this->securityService->getAgenceIdUser();
            $serviceIdUser = $this->securityService->getServiceIdUser();
            $agenceServiceAutorises = $this->securityService->getAgenceServices(ApplicationConstant::CODE_DIT);
            $peutVoirListeAvecDebiteur = $this->securityService->verifierPermission(SecurityService::PERMISSION_AUTH_2);

            $conditions = [];

            // 1- Conditions utilisateur courant (émetteur)
            $conditions[] = "(d0_.agence_emetteur_id = {$agenceIdUser} AND d0_.service_emetteur_id = {$serviceIdUser})";

            // 2- Conditions utilisateur courant (débiteur)
            $conditions[] = "(d0_.agence_debiteur_id = {$agenceIdUser} AND d0_.service_debiteur_id = {$serviceIdUser})";

            // 3- Agences/Services autorisés (émetteur)
            if (!empty($agenceServiceAutorises)) {
                $conditionsEmetteur = [];
                $conditionsDebiteur = [];

                foreach ($agenceServiceAutorises as $agServAut) {
                    // Conditions pour émetteur
                    $conditionsEmetteur[] = "(d0_.agence_emetteur_id = {$agServAut['agence_id']} AND d0_.service_emetteur_id = {$agServAut['service_id']})";

                    // Conditions pour débiteur (si permission)
                    if ($peutVoirListeAvecDebiteur) {
                        $conditionsDebiteur[] = "(d0_.agence_debiteur_id = {$agServAut['agence_id']} AND d0_.service_debiteur_id = {$agServAut['service_id']})";
                    }
                }

                // Ajouter les conditions émetteur si elles existent
                if (!empty($conditionsEmetteur)) {
                    $conditions[] = '(' . implode(' OR ', $conditionsEmetteur) . ')';
                }

                // Ajouter les conditions débiteur si elles existent
                if (!empty($conditionsDebiteur)) {
                    $conditions[] = '(' . implode(' OR ', $conditionsDebiteur) . ')';
                }
            }

            // Retourner toutes les conditions avec AND
            return ' AND (' . implode(' OR ', $conditions) . ')';
        }

        return '';
    }
}
