<?php

namespace App\Service\atelier\dit;

use App\Constants\admin\ApplicationConstant;
use App\Constants\atelier\dit\StatutDitConstant;
use App\Dto\Atelier\Dit\DitSearchDto;
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

    public function filtre(DitSearchDto $ditSearchdto): string
    {
        $selectWhereCondition = new SelectWhereCondition();
        $conditions = [];

        // Construction des conditions de base
        $this->addCondition($conditions, $selectWhereCondition->eq('w2_.description', $ditSearchdto->niveauUrgence));
        $this->addCondition($conditions, $selectWhereCondition->eq('s3_.description', $ditSearchdto->statut));
        $this->addCondition($conditions, $selectWhereCondition->eq('mmat_nummat', $ditSearchdto->idMateriel));
        $this->addCondition($conditions, $selectWhereCondition->eq('mmat_recalph', $ditSearchdto->numParc));
        $this->addCondition($conditions, $selectWhereCondition->eq('mmat_numserie', $ditSearchdto->numSerie));
        $this->addCondition($conditions, $selectWhereCondition->eq('w1_.description', $ditSearchdto->typeDocument));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.internet_externe', $ditSearchdto->internetExterne));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.numero_demande_dit', $ditSearchdto->numDit));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.numero_or', $ditSearchdto->numOr));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.statut_or', $ditSearchdto->statutOr));
        $this->addCondition($conditions, $selectWhereCondition->eq('c4_.libelle_categorie_ate_app', $ditSearchdto->categorie));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.utilisateur_demandeur', $ditSearchdto->utilisateur));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.section_affectee', $ditSearchdto->sectionAffectee));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.section_support_1', $ditSearchdto->sectionSupport1));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.section_support_2', $ditSearchdto->sectionSupport2));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.section_support_3', $ditSearchdto->sectionSupport3));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.numero_devis_rattache', $ditSearchdto->numDevis));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.reparation_realise', $ditSearchdto->reparationRealise));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.agence_emetteur_id', $ditSearchdto->agenceEmetteur));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.service_emetteur_id', $ditSearchdto->serviceEmetteur));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.agence_debiteur_id', $ditSearchdto->agenceDebiteur));
        $this->addCondition($conditions, $selectWhereCondition->eq('d0_.service_debiteur_id', $ditSearchdto->serviceDebiteur));
        $this->addCondition($conditions, $selectWhereCondition->between('d0_.date_demande', $ditSearchdto->dateDebut, $ditSearchdto->dateFin));
        $this->addCondition($conditions, $selectWhereCondition->like('d0_.etat_facturation', substr($ditSearchdto->etatFacture, 0, 4)));
        $this->addCondition($conditions, $selectWhereCondition->null('d0_.numero_or', $ditSearchdto->ditSansOr));

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
