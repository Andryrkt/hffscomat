<?php

namespace App\Api\Atelier\Dit;

use App\Controller\Controller;
use App\Dto\Atelier\Dit\soumission\DitRiSoumisAValidationDto;
use App\Model\dit\DitListModel;

use App\Entity\dit\DitFactureSoumisAValidation;
use App\Model\Atelier\Dit\DitListeModel;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\Soumission\DitFactureSoumisAValidationModel;
use App\Model\Atelier\Dit\Soumission\DitRiSoumisAValidationModel;
use Symfony\Component\Routing\Annotation\Route;


/**
 * @Route("/api")
 */
class ListApi extends Controller
{
    /**
     * @Route("/command-modal/{numOr}", name="api_liste_commandModal")
     *
     * @return void
     */
    public function commandModal($numOr)
    {
        if ($numOr === '') {
            $commandes = [];
        } else {
            $ditModel = new DitModel();
            $commandes = $ditModel->recupereCommandeOr($numOr);
        }

        header("Content-type:application/json");

        echo json_encode($commandes);
    }

    /**
     * @Route("/section-affectee-modal-fetch/{id}", name="api_section_affectee_modal")
     *
     * @return void
     */
    public function sectionAffecteeModal($id)
    {
        $motsASupprimer = [
            'Chef section',
            'Chef de section',
            'Responsable section',
            'Chef d\'équipe'
        ];

        $sectionSupport = (new DitListeModel($this->getSecurityService()))
            ->findSectionSupport($id);

        foreach ($sectionSupport as &$value) {
            foreach ($value as &$texte) {
                // Vérification si c'est bien une chaîne de caractères avant d'effectuer le remplacement
                if (is_string($texte)) {
                    $texte = str_replace($motsASupprimer, '', $texte);
                    $texte = trim($texte); // Supprimer les espaces en trop après remplacement
                }
            }
        }
        header("Content-type: application/json");
        echo json_encode($sectionSupport);
        exit;
    }

    /** 
     * RECUPERATION numero intervention, numero facture et statut du facture
     * @Route("/facturation-fetch/{numOr}", name="api_facturation_fetch") 
     * */
    public function facturation($numOr)
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        if (empty($numOr)) {
            header("Content-type:application/json");
            echo json_encode([]);
            return;
        }

        $ditListeModel = new DitListeModel($this->getSecurityService());

        $facture = (new DitFactureSoumisAValidationModel())->findNumItvFacStatut($numOr, $codeSociete);

        $itvNumFac = $ditListeModel->recupItvNumFac($numOr);

        $result = [];
        foreach ($itvNumFac as $value) {
            $found = false;
            foreach ($facture as $item) {
                if ($item['numeroitv'] == $value['itv']) {
                    $found = true;
                    $result[] = $item;
                    break;
                }
            }


            if (!$found) {
                $result[] = [
                    "numeroitv" => $value['itv'],
                    "numerofact" => $value['numerofac'] ? $value['numerofac'] : "-",
                    "statut" => "-"
                ];
            }
        }


        header("Content-type:application/json");
        echo json_encode($result);
    }

    /** 
     * RECUPERATION numero intervention, numero facture et statut du facture
     * @Route("/ri-fetch/{numOr}", name="api_ri_fetch") 
     * */
    public function ri($numOr)
    {
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        if (empty($numOr)) {
            header("Content-type:application/json");
            echo json_encode([]);
            return;
        }


        $ditListeModel = new DitListeModel($this->getSecurityService());

        $ri = $ditListeModel->recupItvComment($numOr);


        $ditRiSoumisValidation = new DitRiSoumisAValidationModel();

        $riSoumis = $ditRiSoumisValidation->findNumItv($numOr, $codeSociete);



        foreach ($ri as &$value) {
            $estRiSoumis = in_array($value['numeroitv'], $riSoumis);
            $value['riSoumis'] = $estRiSoumis;
        }

        unset($value); // Libère la référence

        header("Content-type:application/json");
        echo json_encode($ri);
    }

    /** 
     * 
     * @Route("/niveau-urgence-fetch/{numDit}", name="api_niveau_urgnece_fetch") 
     * */
    public function niveauUrgence($numDit)
    {
        /**
         *  A FAIRE, EN ATTENTE DU TABLEAU
         */
        $niveauUrgence = [];

        header("Content-type:application/json");
        echo json_encode($niveauUrgence);
    }
}
