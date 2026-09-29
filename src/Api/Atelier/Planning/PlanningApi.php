<?php

namespace App\Api\Atelier\Planning;

use App\Controller\Controller;
use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Planning\PlanningMaterielModel;
use App\Model\Atelier\Planning\PlanningModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class PlanningApi extends Controller
{
    private PlanningModel $planningModel;
    private PlanningMaterielModel $planningMaterielModel;
    private DitModel $ditModel;

    public function __construct()
    {
        parent::__construct();
        $this->planningModel = new PlanningModel();
        $this->planningMaterielModel = new PlanningMaterielModel();
        $this->ditModel = new DitModel();
    }

    /**
     * @Route("/api/serviceDebiteurPlanning-fetch/{agentId}", name="api_serviceDebiteurPlanning_fetch")
     */
    public function serviceDebiteur(int $agentId)
    {
        if ($agentId == 10) {
            $serviceDebiteur = [];
        } else {
            $serviceDebiteur = $this->planningModel->getServiceDebiteByAgence($agentId);
        }

        header("Content-type:application/json");

        echo json_encode($serviceDebiteur);
    }

    /**
     * @Route("/api/detail-modal/{numOr}", name="api_detailModal_fetch")
     */
    public function detailModal(string $numOr)
    {
        try {
            $dto = $this->getSessionService()->get('planning_search_criteria');
            if (!$dto instanceof PlanningSearchDto)
                $dto = new PlanningSearchDto();
            $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

            $details = $this->convertirEnUtf8($this->planningMaterielModel->getDetailPieceInformix($numOr, $dto));
            // $numOr vaut "numOr-numItv" : on ne garde que le numéro d'OR pour retrouver la DIT
            $numDit = $this->ditModel->getNumDitByNumOr(explode('-', $numOr)[0], $codeSociete);

            foreach ($details as $i => $detail) {
                $details[$i]['eta_magasin'] = "";
                $details[$i]['etat_pays'] = "";

                if (!empty($detail['num_cis'])) {
                    $magasin = $this->planningModel->getEtaMagasin((string) $detail['num_cis'], (string) ($detail['ref'] ?? ''));
                    if (!empty($magasin[0])) {
                        $details[$i]['eta_magasin'] = $this->formaterDate($magasin[0]['eta_magasin'] ?? null);
                        $details[$i]['etat_pays'] = $this->formaterDate($magasin[0]['etat_pays'] ?? null);
                    }
                }

                $details[$i]['qteSolde'] = "";
                $details[$i]['qte'] = "";
                $details[$i]['Ord'] = "";
                $details[$i]['date_liv'] = "";
                $details[$i]['dateAllLIg'] = "";
                $details[$i]['qteORlig'] = "";
                $details[$i]['qtealllig'] = "";
                $details[$i]['qterlqlig'] = "";
                $details[$i]['qtelivlig'] = "";
                $details[$i]['migration'] = "";
                $details[$i]['numDit'] = $numDit;
            }

            return new JsonResponse([
                'avecOnglet' => false,
                'data' => $details,
            ]);
        } catch (\Throwable $e) {
            error_log("API detail-modal ($numOr) : " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());

            return new JsonResponse([
                'avecOnglet' => false,
                'data' => [],
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Formate une date en d/m/Y sans lever d'exception si la valeur est vide ou invalide.
     */
    private function formaterDate($date): string
    {
        if (empty($date)) {
            return "";
        }

        try {
            return (new \DateTime($date))->format('d/m/Y');
        } catch (\Exception $e) {
            $dateFr = \DateTime::createFromFormat('d/m/Y', (string) $date);
            return $dateFr ? $dateFr->format('d/m/Y') : "";
        }
    }

    /**
     * Convertit récursivement les chaînes Informix (Windows-1252) en UTF-8 pour json_encode.
     */
    private function convertirEnUtf8($element)
    {
        if (is_array($element)) {
            foreach ($element as $key => $value) {
                $element[$key] = $this->convertirEnUtf8($value);
            }
        } elseif (is_string($element) && !mb_check_encoding($element, 'UTF-8')) {
            return mb_convert_encoding($element, 'UTF-8', 'Windows-1252');
        }
        return $element;
    }

    /**
     * @Route("/api/technicien-intervenant/{numOr}/{numItv}", name="api_technicien_intervenant")
     */
    public function TechnicienIntervenant($numOr, $numItv)
    {
        $matriculeNom = $this->planningModel->getTechnicientIntervenantSkw($numOr, $numItv);

        if (empty($matriculeNom)) {
            $matriculeNom = $this->planningModel->getTechnicientIntervenantItv($numOr, $numItv);
        }

        header("Content-type:application/json");

        echo json_encode($matriculeNom);
    }

    private function regroupeParIntervention(array $details): array
    {
        $groupedDetails = [];

        foreach ($details as $detail) {
            $itvKey = $detail['num_itv']; // La valeur de 'num_itv' utilisée comme clé
            if (!isset($groupedDetails[$itvKey])) {
                $groupedDetails[$itvKey] = [];
            }
            $groupedDetails[$itvKey][] = $detail; // Ajouter l'élément au groupe correspondant
        }
        return $groupedDetails;
    }
}
