<?php

namespace App\Controller\Atelier\Dit;

ini_set('memory_limit', '512M');

use App\Constants\admin\ApplicationConstant;
use App\Controller\Controller;
use App\Dto\Atelier\Dit\DitSearchDto;
use App\Mapper\Atelier\Dit\DitSearchMapper;
use App\Model\Atelier\Dit\DitListeModel;
use App\Service\ExcelService;
use App\Service\security\SecurityService;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitExportExcelController extends Controller
{


    /**
     * @Route("/export-excel", name="export_excel")
     */
    public function exportExcel()
    {
        // Code Société de l'utilisateur
        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        //recupères les critère dans la session 
        $criteria = $this->getSessionService()->get('criteria_for_excel_dit_liste', []);
        // Agences Services autorisés sur le DIT
        $agenceServiceAutorises = $this->getSecurityService()->getAgenceServices(ApplicationConstant::CODE_DIT);
        // Code agence utilisateur
        $agenceIdUser = $this->getSecurityService()->getAgenceIdUser();
        $serviceIdUser = $this->getSecurityService()->getServiceIdUser();
        $codeAgenceUser = $this->getSecurityService()->getCodeAgenceUser();

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        // Vérifier le permission de voir liste avec débiteur sur la page courante
        $peutVoirListeAvecDebiteur = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_AUTH_2, "dit_index");
        //crée une objet à partir du tableau critère reçu par la session


        $ditMapper = new DitSearchMapper();

        $dtoSearch = $ditMapper->fromArray($criteria);

        $ditListeModel = new DitListeModel($this->getSecurityService());

        $ditDatas = $ditListeModel->DonnerAAjouterExcel($dtoSearch, $codeSociete);

        // Convertir les entités en tableau de données
        $data = $this->transformationEnTableauAvecEntet($ditDatas);

        //creation du fichier excel
        (new ExcelService())->createSpreadsheet($data);
    }
    private function transformationEnTableauAvecEntet($ditDatas): array
    {
        $data = [];
        $data[] = ['Statut', 'N° DIT', 'Réalisé par', 'Type Document', 'Niveau', 'Catégorie de Demande', 'N°Serie', 'N°Parc', 'Date demande', 'Int/Ext', 'Emetteur', 'Débiteur',  'Objet', 'sectionAffectee', 'N° devis', 'Statut Devis', 'N°Or', 'Statut Or', 'Statut facture', 'RI', 'Nbre Pj', 'Utilisateur', 'Marque', 'Casier']; // En-têtes des colonnes
        foreach ($ditDatas as $dit) {
            $data[] = [
                $dit["statut"] ?? '',
                $dit["numero_dit"] ?? '',
                $dit["realise_par"] ?? '',
                $dit["type_document"] ?? '',
                $dit["niveau_urgence"] ?? '',
                $dit["categorie"] ?? '',

                // trim() enlève les espaces vides inutiles à la fin comme "NH301944            "
                isset($dit["numero_serie"]) ? trim($dit["numero_serie"]) : '',
                isset($dit["numero_parc"]) ? trim($dit["numero_parc"]) : '',

                $dit["date_demande"] ?? '',
                $dit["int_ext"] ?? '',
                $dit["emetteur"] ?? '',
                $dit["debiteur"] ?? '',
                $dit["objet"] ?? '',
                $dit["section_affectee"] ?? '',
                $dit["numero_devis"] ?? '',
                $dit["statut_devis"] ?? '',
                $dit["numero_or"] ?? '',
                $dit["statut_or"] ?? '',
                $dit["statut_facture"] ?? '',
                $dit["ri"] ?? '',
                $dit["nbrpj"] ?? '',
                $dit["utilisateur"] ?? '',
                $dit["marque"] ?? '',
                $dit["casier"] ?? '',
            ];
        }

        return $data;
    }
}
