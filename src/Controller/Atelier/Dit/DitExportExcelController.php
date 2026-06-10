<?php

namespace App\Controller\Atelier\Dit;

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
        $criteria = $this->getSessionService()->get('dit_search_criteria', []);
        // Agences Services autorisés sur le DIT
        $agenceServiceAutorises = $this->getSecurityService()->getAgenceServices(ApplicationConstant::CODE_DIT);
        // Code agence utilisateur
        $agenceIdUser = $this->getSecurityService()->getAgenceIdUser();
        $serviceIdUser = $this->getSecurityService()->getServiceIdUser();
        $codeAgenceUser = $this->getSecurityService()->getCodeAgenceUser();

        // Vérifier la permission de voir tous les données
        $multisuccursale = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_MULTI_SUCCURSALE);

        // Vérifier le permission de voir liste avec débiteur sur la page courante
        $peutVoirListeAvecDebiteur = $this->getSecurityService()->verifierPermission(SecurityService::PERMISSION_AUTH_2, "dit_liste");
        //crée une objet à partir du tableau critère reçu par la session


        $ditMapper = new DitSearchMapper();

        $dtoSearch = $ditMapper->fromArray($criteria);


        $ditListeModel = new DitListeModel($this->getSecurityService());

        $entities = $ditListeModel->DonnerAAjouterExcel($dtoSearch, $codeSociete);
        // Convertir les entités en tableau de données
        $data = $this->transformationEnTableauAvecEntet($entities);

        //creation du fichier excel
        (new ExcelService())->createSpreadsheet($data);
    }
    private function transformationEnTableauAvecEntet($entities): array
    {
        $data = [];
        $data[] = ['Statut', 'N° DIT', 'Réalisé par', 'Type Document', 'Niveau', 'Catégorie de Demande', 'N°Serie', 'N°Parc', 'date demande', 'Int/Ext', 'Emetteur', 'Débiteur',  'Objet', 'sectionAffectee', 'N° devis', 'Statut Devis', 'N°Or', 'Statut Or', 'Statut facture', 'RI', 'utilisateur']; // En-têtes des colonnes

        foreach ($entities as $entity) {
            $data[] = [
                $entity["statut"] ?? '',
                $entity["numero_dit"] ?? '',
                $entity["realise_par"] ?? '',
                $entity["type_document"] ?? '',
                $entity["niveau_urgence"] ?? '',
                $entity["categorie"] ?? '',

                // trim() enlève les espaces vides inutiles à la fin comme "NH301944            "
                isset($entity["numero_serie"]) ? trim($entity["numero_serie"]) : '',
                isset($entity["numero_parc"]) ? trim($entity["numero_parc"]) : '',

                $entity["date_demande"] ?? '',
                $entity["int_ext"] ?? '',
                $entity["emetteur"] ?? '',
                $entity["debiteur"] ?? '',
                $entity["objet"] ?? '',
                $entity["section_affectee"] ?? '',
                $entity["numero_devis"] ?? '',
                $entity["statut_devis"] ?? '',
                $entity["numero_or"] ?? '',
                $entity["statut_or"] ?? '',
                $entity["statut_facture"] ?? '',
                $entity["ri"] ?? '',
                $entity["utilisateur"] ?? ''
            ];
        }

        return $data;
    }
}
