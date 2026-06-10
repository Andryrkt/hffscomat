<?php

namespace App\Controller\Atelier\Dit;

use App\Controller\Controller;
use App\Form\dit\DitDetailType;
use App\Mapper\Atelier\Dit\DitMapper;
use App\Model\admin\StatutDemande\StatutDemandeModel;
use App\Model\Atelier\Dit\CategorieAteAppModel;
use App\Model\Atelier\Dit\DitModel;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Model\Atelier\Dit\WorTypeDocumentModel;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/atelier/demande-intervention")
 */
class DitDetailController extends Controller
{
    /**
     * @Route("/detail/{numDit<\w+>}", name="dit_detail")
     */
    public function detailDit(string $numDit, Request $request)
    {
        $autoriser = $this->estAdmin();


        $codeSociete = $this->getSecurityService()->getCodeSocieteUser();

        $ditModel = new DitModel();
        $ditInformations = $ditModel->recupInformationsDit($numDit, $codeSociete);
        $ditDto = DitMapper::transformToDto($ditInformations);

        $worNiveauUrgenceModel = new WorNiveauUrgenceModel();
        $descriptionNiveauUrgence = $worNiveauUrgenceModel->getDescriptionById($ditInformations["id_niveau_urgence"]);
        $ditDto->worNiveauUrgence = $descriptionNiveauUrgence;


        $worTypeDocumentModel = new WorTypeDocumentModel();
        $descriptionTypeDocument = $worTypeDocumentModel->getDescriptionById($ditInformations["type_document"]);
        $ditDto->typeDocument = $descriptionTypeDocument;

        $statutDemandeModel = new StatutDemandeModel();
        $descriptionStatutDemande = $statutDemandeModel->getDescriptionById($ditInformations["id_statut_demande"]);
        $ditDto->statutDemande = $descriptionStatutDemande;

        $categorieAteAppModel = new CategorieAteAppModel();
        $libelleCategorie = $categorieAteAppModel->getDescriptionById($ditInformations["categorie_demande"]);

        $ditDto->categorieDemande = $libelleCategorie;





        $data = $ditModel->findAll($ditDto->idMateriel, $ditDto->numParc, $ditDto->numSerie);
        $dataMapped = DitMapper::toArrayDitDetail($ditDto, $data);


        $commandes = $ditModel->RecupereCommandeOr($ditDto->numeroOr);

        // $this->logUserVisit('dit_validationDit', [
        //     'id'     => $id,
        //     'numDit' => $numDit,
        // ]); // historisation du page visité par l'utilisateur  
        return  $this->render('atelier/dit/validation.html.twig', [
            'dit' => $dataMapped,
            'autoriser' => $autoriser,
            'commandes' => $commandes
        ]);
    }
}
