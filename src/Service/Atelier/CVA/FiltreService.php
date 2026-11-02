<?php

namespace App\Service\Atelier\CVA;

use App\Constants\admin\ApplicationConstant;
use App\Dto\Atelier\CVA\OrCsaATraiterSearchDto;
use App\Service\security\SecurityService;

class FiltreService
{
    private SecurityService $securityService;
    private int $delaiAllocation;
    private int $toleranceFenetre;

    public function __construct(
        SecurityService $securityService,
        int $delaiAllocation = 90,
        int $toleranceFenetre = 10
    ) {
        $this->securityService  = $securityService;
        $this->delaiAllocation  = $delaiAllocation;
        $this->toleranceFenetre = $toleranceFenetre;
    }

    /**
     * Construit le fragment SQL des filtres de l'écran OR CSA à traiter.
     *
     * @return string  chaîne commençant par " AND …" (ou vide si aucun filtre)
     */
    public function filtre(OrCsaATraiterSearchDto $dto): string
    {
        $conditions = [];

        $d   = $this->delaiAllocation;
        $min = $this->delaiAllocation - $this->toleranceFenetre; // 80
        $max = $this->delaiAllocation + $this->toleranceFenetre; // 100

        // 1) Fenêtre d'allocation
        switch ($dto->fenetreAllocation) {
            case 'RETARD':
                $conditions[] = " AND (seor_delai - TODAY) < {$d}";
                break;

            case 'AUTOUR_J90':
                $conditions[] = " AND (seor_delai - TODAY) BETWEEN {$min} AND {$max}";
                break;

            case 'A_VENIR':
                $conditions[] = " AND (seor_delai - TODAY) > {$max}";
                break;

            case 'TOUS':
            default:
                // aucun filtre
                break;
        }

        // 2) Lead time du
        if ($dto->leadTimeDu !== null) {
            $date = $dto->leadTimeDu->format('d/m/Y');
            $conditions[] = " AND seor_delai >= TO_DATE('{$date}', '%d/%m/%Y')";
        }

        // 3) Lead time au
        if ($dto->leadTimeAu !== null) {
            $date = $dto->leadTimeAu->format('d/m/Y');
            $conditions[] = " AND seor_delai <= TO_DATE('{$date}', '%d/%m/%Y')";
        }

        // 4) Client (N° ou libellé) — recherche partielle insensible à la casse
        if ($dto->client !== null && trim($dto->client) !== '') {
            $esc = $this->esc(strtoupper(trim($dto->client)));
            $conditions[] = " AND ("
                . "UPPER(seor_numcli) LIKE '%{$esc}%'"
                . " OR UPPER(seor_nomcli) LIKE '%{$esc}%'"
                . ")";
        }

        // 5) Référence client
        if ($dto->referenceClient !== null && trim($dto->referenceClient) !== '') {
            $esc = $this->esc(strtoupper(trim($dto->referenceClient)));
            $conditions[] = " AND UPPER(seor_lib) LIKE '%{$esc}%'";
        }

        // 6) N° OR
        if ($dto->numOr !== null && trim($dto->numOr) !== '') {
            $esc = $this->esc(trim($dto->numOr));
            $conditions[] = " AND seor_numor = '{$esc}'";
        }

        // 7) Type OR
        if ($dto->typeOr !== null && trim($dto->typeOr) !== '') {
            $esc = $this->esc(trim($dto->typeOr));
            $conditions[] = " AND seor_typeor = '{$esc}'";
        }

        return implode('', $conditions);
    }

    /**
     * Échappe une valeur textuelle pour insertion SQL.
     * (Pas de requête paramétrée disponible côté Model,
     *  donc on protège au minimum l'apostrophe.)
     */
    private function esc(string $value): string
    {
        return str_replace("'", "''", $value);
    }

    /**
     * Condition multi-succursale.
     *
     * ⚠️ Cette méthode reste spécifique au DIT (colonnes d0_.agence_emetteur_id,
     * d0_.service_emetteur_id…). Elle n'est pas utilisée par le Model OR CSA
     * qui passe une chaîne vide en attendant une adaptation aux tables sav_eor/sav_lor.
     * Conservée ici pour ne pas casser les éventuels appelants DIT.
     */
    public function conditionAgenceService(): string
    {
        $multisuccursale = $this->securityService->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        if ($multisuccursale) {
            return '';
        }

        $agenceIdUser       = $this->securityService->getAgenceIdUser();
        $serviceIdUser      = $this->securityService->getServiceIdUser();
        $agenceServiceAutorises = $this->securityService->getAgenceServices(ApplicationConstant::CODE_DIT);
        $peutVoirListeAvecDebiteur = $this->securityService->verifierPermission(SecurityService::PERMISSION_AUTH_2);

        $conditions = [];

        $conditions[] = "(d0_.agence_emetteur_id = {$agenceIdUser} AND d0_.service_emetteur_id = {$serviceIdUser})";
        $conditions[] = "(d0_.agence_debiteur_id = {$agenceIdUser} AND d0_.service_debiteur_id = {$serviceIdUser})";

        if (!empty($agenceServiceAutorises)) {
            $conditionsEmetteur = [];
            $conditionsDebiteur = [];

            foreach ($agenceServiceAutorises as $agServAut) {
                $conditionsEmetteur[] = "(d0_.agence_emetteur_id = {$agServAut['agence_id']} AND d0_.service_emetteur_id = {$agServAut['service_id']})";

                if ($peutVoirListeAvecDebiteur) {
                    $conditionsDebiteur[] = "(d0_.agence_debiteur_id = {$agServAut['agence_id']} AND d0_.service_debiteur_id = {$agServAut['service_id']})";
                }
            }

            if (!empty($conditionsEmetteur)) {
                $conditions[] = '(' . implode(' OR ', $conditionsEmetteur) . ')';
            }
            if (!empty($conditionsDebiteur)) {
                $conditions[] = '(' . implode(' OR ', $conditionsDebiteur) . ')';
            }
        }

        return ' AND (' . implode(' OR ', $conditions) . ')';
    }
}
