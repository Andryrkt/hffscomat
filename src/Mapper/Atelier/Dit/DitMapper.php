<?php

namespace App\Mapper\Atelier\Dit;


use App\Dto\Atelier\Dit\DitDto;
use App\Entity\admin\Agence;
use App\Entity\admin\Service;
use App\Model\admin\StatutDemande\StatutDemandeModel;
use App\Model\Atelier\Dit\CategorieAteAppModel;
use App\Model\Atelier\Dit\WorNiveauUrgenceModel;
use Doctrine\ORM\EntityManagerInterface;

class DitMapper
{
    public static function DtoToArray(DitDto $dto, EntityManagerInterface $em, array $nomFichierEnregistrer): array
    {
        $worTypeDocumentModel = new WorNiveauUrgenceModel();
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
            'agence_debiteur_id' => $dto->agence->getId(),
            'service_debiteur_id' => $dto->service->getId(),
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
}
