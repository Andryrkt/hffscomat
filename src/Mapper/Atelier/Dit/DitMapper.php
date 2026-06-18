<?php

namespace App\Mapper\Atelier\Dit;


use App\Dto\Atelier\Dit\DitDto;
use App\Entity\admin\Agence;
use App\Entity\admin\Service;
use App\Model\admin\StatutDemande\StatutDemandeModel;
use App\Model\Atelier\Dit\CategorieAteAppModel;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use App\Model\Atelier\Dit\WorTypeDocumentModel;
use Doctrine\ORM\EntityManagerInterface;

class DitMapper
{
    public static function DtoToArray(DitDto $dto, EntityManagerInterface $em, array $nomFichierEnregistrer): array
    {
        $worTypeDocumentModel = new WorTypeDocumentModel();
        $worTypeNiveaUrgenceModel = new WorNiveauUrgenceModel();
        $categorieAteAppModel = new CategorieAteAppModel();
        $statutDemandeModel = new StatutDemandeModel();

        return [
            'numero_demande_dit' => $dto->numeroDemandeIntervention,
            'type_document' => $worTypeDocumentModel->getIdSelonDescription($dto->typeDocument),
            'code_societe' => $dto->codeSociete,
            'type_reparation' => $dto->typeReparation,
            'reparation_realise' => $dto->reparationRealise,
            'categorie_demande' => $categorieAteAppModel->getIdSelonLibelle($dto->categorieDemande),
            'internet_externe' => $dto->internetExterne,
            'agence_service_debiteur' => $dto->agenceServiceDebiteur,
            'agence_service_emmeteur' => $dto->agenceServiceEmetteur,
            'nom_client' => $dto->nomClient,
            'date_prevue_travaux' => $dto->datePrevueTravaux,
            'demande_devis' => $dto->demandeDevis,
            'id_niveau_urgence' => $worTypeNiveaUrgenceModel->getIdSelonDescription($dto->worNiveauUrgence),
            'avis_recouvrement' => $dto->avisRecouvrement,
            'client_sous_contrat' => $dto->clientSousContrat,
            'objet_demande' => $dto->objetDemande,
            'detail_demande' => $dto->detailDemande,
            'livraison_partiel' => $dto->livraisonPartiel,
            'id_materiel' => $dto->idMateriel,
            'mail_demandeur' => $dto->mailDemandeur,
            'date_demande' => $dto->dateDemande,
            'heure_demande' => $dto->heureDemande,
            'piece_joint1' => $nomFichierEnregistrer[0] ?? null,
            'piece_joint2' => $nomFichierEnregistrer[1] ?? null,
            'piece_joint' => $nomFichierEnregistrer[2] ?? null,
            'utilisateur_demandeur' => $dto->utilisateurDemandeur,
            'id_statut_demande' => $statutDemandeModel->getIdSelonDescription($dto->statutDemande),
            'numero_client' => $dto->numeroClient,
            'libelle_client' => $dto->nomClient,
            'km_machine' => $dto->km,
            'heure_machine' => $dto->heure,
            'agence_emetteur_id' => $em->getRepository(Agence::class)->findOneBy(['codeAgence' => trim(explode(' ', $dto->agenceEmetteur)[0])])->getId(),
            'service_emetteur_id' => $em->getRepository(Service::class)->findOneBy(['codeService' => trim(explode(' ', $dto->serviceEmetteur)[0])])->getId(),
            'agence_debiteur_id' => $dto->agence !== null ? $dto->agence->getId() : null,
            'service_debiteur_id' => $dto->service !== null ? $dto->service->getId() : null,
            'mail_client' => $dto->mailClient,
        ];
    }

    public static function updateDit(bool $reponse)
    {
        return [
            'pdf_deposer_dw' => $reponse,
            'date_depot_pdf_dw' => date('Y-m-d H:i:s')
        ];
    }

    public static function transformToDto(array $ditInformations, EntityManagerInterface $em): ?DitDto
    {

        $worTypeNiveaUrgenceModel = new WorNiveauUrgenceModel();

        $dto = new DitDto();
        $dto->numeroDemandeIntervention = $ditInformations['numero_demande_dit'];
        $dto->typeDocument              = $ditInformations['type_document'] ?? null;
        $dto->statutDemande             = $ditInformations['id_statut_demande'] ?? null;
        $dto->codeSociete    = $ditInformations["code_societe"] ?? null;
        $dto->typeReparation    = $ditInformations["type_reparation"] ?? null;
        $dto->reparationRealise         = $ditInformations['reparation_realise'] ?? null;
        $dto->categorieDemande          = $ditInformations['categorie_demande'] ?? null;

        $typeInternet = $ditInformations['internet_externe'] ?? null;
        if ($typeInternet === 'I') {
            $dto->internetExterne = 'INTERNE';
        } elseif ($typeInternet === 'E') {
            $dto->internetExterne = 'EXTERNE';
        } else {
            $dto->internetExterne = $typeInternet;
        }

        $dto->agenceServiceEmetteur     = $ditInformations['agence_service_emmeteur'] ?? null;
        $dto->agenceServiceDebiteur     = $ditInformations['agence_service_debiteur'] ?? null;

        $dto->nomClient     = $ditInformations['nomClient'] ?? null;
        $dto->numeroTel     = $ditInformations['numero_telephone'] ?? null;
        $dto->dateSoumissionOr     = $ditInformations['date_or'] ?? null;
        $dto->heureDemande     = $ditInformations['heure_or'] ?? null;
        $dto->datePrevueTravaux               = !empty($ditInformations['date_prevue_travaux']) ? new \DateTime($ditInformations['date_prevue_travaux']) : null;
        $dto->demandeDevis = $ditInformations['demande_devis'] ?? null;
        $dto->worNiveauUrgence = $ditInformations['niveau_urgence'] ?? null;
        $dto->avisRecouvrement = $ditInformations['avis_recouvrement'] ?? null;
        $dto->clientSousContrat = $ditInformations['client_sous_contrat'] ?? null;
        $dto->objetDemande = $ditInformations['objet_demande'] ?? null;
        $dto->detailDemande = $ditInformations['detail_demande'] ?? null;
        $dto->livraisonPartiel = $ditInformations['livraison_partiel'] ?? null;
        $dto->idMateriel = $ditInformations['id_materiel'] ?? null;
        $dto->mailDemandeur = $ditInformations['mail_demandeur'] ?? null;
        $dto->dateDemande = $ditInformations['date_demande'];
        $dto->heureDemande =  $ditInformations['heure_demande'] ?? null;
        // $dto->dateCloture = !empty($ditInformations['date_cloture']) ? new \DateTime($ditInformations['date_cloture']) : null;
        // $dto->heureCloture =  $ditInformations['heure_cloture'] ?? null;
        $dto->pieceJoint01 =  $ditInformations['piece_joint1'] ?? null;
        $dto->pieceJoint02 =  $ditInformations['piece_joint2'] ?? null;
        $dto->pieceJoint03 =  $ditInformations['piece_joint'] ?? null;
        $dto->utilisateurDemandeur =  $ditInformations['utilisateur_demandeur'] ?? null;
        // $dto->observations =  $ditInformations['observations'] ?? null;
        // $dto->dateValidation =  $ditInformations['date_validation'] ?? null;
        // $dto->heure_validation =  $ditInformations['heure_validation'] ?? null;
        $dto->numeroClient =  $ditInformations['numero_client'] ?? null;
        // $dto->libeleClient =  $ditInformations['numero_client'] ?? null;
        // $dto->dateFinSouhaiter= $ditInformations['date_fin_souhaite'] 
        $dto->numeroOr =  $ditInformations['numero_or'] ?? null;
        // $dto->observation_direction_technique =  $ditInformations['observation_direction_technique'] ?? null;
        // $dto->observation_devis =  $ditInformations['observation_devis'] ?? null;
        $dto->numeroDevisRattacher =  $ditInformations['numero_devis_rattache'] ?? null;
        // $dto->date_soumission_devis =  $ditInformations['date_soumission_devis'] ?? null;
        $dto->statutDevis =  $ditInformations['statut_devis'] ?? null;
        // $dto->date_validation_devis =  $ditInformations['date_validation_devis'] ?? null;
        // $dto->id_service_intervenant =  $ditInformations['id_service_intervenant'] ?? null;
        // $dto->date_devis_fin_probable =  $ditInformations['date_devis_fin_probable'] ?? null;
        // $dto->date_fin_estimation_travaux =  $ditInformations['date_fin_estimation_travaux'] ?? null;
        // $dto->codeSection =  $ditInformations['code_section'] ?? null;
        // $dto->masAte =  $ditInformations['mas_ate'] ?? null;
        // $dto->codeAte =  $ditInformations['code_ate'] ?? null;
        // $dto->secteur =  $ditInformations['secteur'] ?? null;
        // $dto->utilisateur_intervenant =  $ditInformations['utilisateur_intervenant'] ?? null;
        // $dto->km_machine =  $ditInformations['km_machine'] ?? null;
        // $dto->heure_machine =  $ditInformations['heure_machine'] ?? null;
        // $dto->date_devis_rattache =  $ditInformations['date_devis_rattache'] ?? null;
        $dto->sectionAffectee =  $ditInformations['section_affectee'] ?? null;
        $dto->statutOr =  $ditInformations['statut_or'] ?? null;
        // $dto->statutCommande =  $ditInformations['statut_commande'] ?? null;
        // $dto->dateValidationOr =  $ditInformations['date_validation_or'] ?? null;


        $dto->agenceEmetteur =  $em->getRepository(Agence::class)->find($ditInformations["agence_emetteur_id"])->getCodeAgence() . ' ' .  $em->getRepository(Agence::class)->find($ditInformations["agence_emetteur_id"])->getLibelleAgence() ?? null;
        $dto->serviceEmetteur =  $em->getRepository(Service::class)->find($ditInformations["service_emetteur_id"])->getCodeService() . ' ' .  $em->getRepository(Service::class)->find($ditInformations["service_emetteur_id"])->getLibelleService() ?? null;

        // $dto->agence_debiteur_id =  $ditInformations['agence_debiteur_id'] ?? null;
        // $dto->service_debiteur_id =  $ditInformations['service_debiteur_id'] ?? null;
        // $dto->section_support_1 =  $ditInformations['section_support_1'] ?? null;
        // $dto->section_support_2 =  $ditInformations['section_support_2'] ?? null;
        // $dto->section_support_3 =  $ditInformations['section_support_3'] ?? null;
        // $dto->migration =  $ditInformations['migration'] ?? null;
        $dto->etatFacturation =  $ditInformations['etat_facturation'] ?? null;
        $dto->ri =  $ditInformations['ri'] ?? null;
        $dto->mailClient =  $ditInformations['mail_client'] ?? null;
        // $dto->numMigr =  $ditInformations['num_migr'] ?? null;
        $dto->estAnnulable =  $ditInformations['a_annuler'] ?? null;
        // $dto->dateAnnulation =  $ditInformations['date_annulation'] ?? null;
        // $dto->numero_demande_dit_avoir =  $ditInformations['numero_demande_dit_avoir'] ?? null;
        // $dto->numero_demande_dit_refacturation =  $ditInformations['numero_demande_dit_refacturation'] ?? null;
        $dto->estDitAvoir =  $ditInformations['dit_avoir'] ?? null;
        $dto->estDitRefacturation =  $ditInformations['dit_refacturation'] ?? null;
        $dto->estAtePolTana =  $ditInformations['ate_pol_tana'] ?? null;
        // $dto->pdf_deposer_dw =  $ditInformations['pdf_deposer_dw'] ?? null;
        // $dto->date_depot_pdf_dw =  $ditInformations['date_depot_pdf_dw'] ?? null;

        //Agence et service debiteurs
        $dto->agence = $em->getRepository(Agence::class)->find($ditInformations["agence_debiteur_id"]);
        $dto->service = $em->getRepository(Service::class)->find($ditInformations["service_debiteur_id"]);

        $dto->numParc = $ditInformations['numero_parc'] ?? null;
        $dto->numSerie = $ditInformations['numero_serie'] ?? null;

        $dto->worNiveauUrgence =  $worTypeNiveaUrgenceModel->getDescriptionById($ditInformations['id_niveau_urgence']);

        return $dto;
    }

    public static function toArrayDitDetail(DitDto $ditDto, array $materielData): array
    {
        $mat = $materielData[0] ?? [];

        $prixAchat       = (float) ($mat['prix_achat'] ?? 0);
        $amortissement   = (float) ($mat['amortissement'] ?? 0);
        $chiffreAffaires = (float) ($mat['chiffreaffaires'] ?? 0);
        $chargeEntretien = (float) ($mat['chargeentretien'] ?? 0);
        $chargeLocative  = (float) ($mat['chargelocative'] ?? 0);

        return [
            'numeroDemandeDit'     => $ditDto->numeroDemandeIntervention,
            'typeDocument'         => $ditDto->typeDocument,
            'statutDemande'        => $ditDto->statutDemande,
            'codeSociete'          => $ditDto->codeSociete,
            'typeReparation'       => $ditDto->typeReparation,
            'reparationRealise'    => $ditDto->reparationRealise,
            'categorieDemande'     => $ditDto->categorieDemande,
            'internetExterne'      => $ditDto->internetExterne,
            'agenceServiceEmetteur' => $ditDto->agenceServiceEmetteur,
            'agenceServiceDebiteur' => $ditDto->agenceServiceDebiteur,
            'nomClient'            => $ditDto->nomClient,
            'numeroTel'            => $ditDto->numeroTel,
            'dateSoumissionOr'     => $ditDto->dateSoumissionOr,
            'heureDemande'         => $ditDto->heureDemande,
            'datePrevueTravaux'    => $ditDto->datePrevueTravaux,
            'demandeDevis'         => $ditDto->demandeDevis,
            'idNiveauUrgence'     => $ditDto->worNiveauUrgence,
            'avisRecouvrement'     => $ditDto->avisRecouvrement,
            'clientSousContrat'    => $ditDto->clientSousContrat,
            'objetDemande'         => $ditDto->objetDemande,
            'detailDemande'        => $ditDto->detailDemande,
            'livraisonPartiel'     => $ditDto->livraisonPartiel,
            'mailDemandeur'        => $ditDto->mailDemandeur,
            'dateDemande'          => $ditDto->dateDemande,
            'pieceJoint01'         => $ditDto->pieceJoint01,
            'pieceJoint02'         => $ditDto->pieceJoint02,
            'pieceJoint03'         => $ditDto->pieceJoint03,
            'utilisateurDemandeur' => $ditDto->utilisateurDemandeur,
            'numeroClient'         => $ditDto->numeroClient,
            'numeroOr'             => $ditDto->numeroOr,
            'numeroDevisRattacher' => $ditDto->numeroDevisRattacher,
            'statutDevis'          => $ditDto->statutDevis,
            'sectionAffectee'      => $ditDto->sectionAffectee,
            'statutOr'             => $ditDto->statutOr,
            'agenceEmetteur'       => $ditDto->agenceEmetteur,
            'serviceEmetteur'      => $ditDto->serviceEmetteur,
            'etatFacturation'      => $ditDto->etatFacturation,
            'ri'                   => $ditDto->ri,
            'mailClient'           => $ditDto->mailClient,
            'estDitAvoir'          => $ditDto->estDitAvoir,
            'estDitRefacturation'  => $ditDto->estDitRefacturation,
            'estAtePolTana'        => $ditDto->estAtePolTana,
            'numSerie'             => $ditDto->numSerie,
            'numParc'              => $ditDto->numParc,
            'constructeur'         => $mat['constructeur'] ?? null,
            'modele'               => $mat['modele'] ?? null,
            'designation'          => $mat['designation'] ?? null,
            'casier'               => $mat['casier_emetteur'] ?? null,
            'idMateriel'           => $mat['num_matricule'] ?? null,
            'coutAcquisition'      => $prixAchat,
            'amortissement'        => $amortissement,
            'chiffreAffaire'       => $chiffreAffaires,
            'chargeEntretient'     => $chargeEntretien,
            'chargeLocative'       => $chargeLocative,
            'resultatExploitation' => $chiffreAffaires - ($chargeEntretien + $chargeLocative),
            'valeurNetComptable'   => $prixAchat - $amortissement,
            'km'                   => $mat['km'] ?? null,
            'heure'                => $mat['heure'] ?? null,
        ];
    }

    public function toExcelArray(array $dtis): array
    {
        $data = [];

        foreach ($dtis as $dti) {
            $data[] = [
                'numeroDit' => $dti->getNumeroDit(),
                'statut' => $dti->getStatut(),
                'date' => $dti->getDateCreation(),
            ];
        }

        return $data;
    }

    public static function toArrayUpdateDitForAnnuler()
    {
        return [
            'id_statut_demande' => 52,
            'a_annuler' => true,
            'date_annulation' => (new \DateTime())
        ];
    }
}
