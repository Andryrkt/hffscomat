<?php

namespace App\Factory\magasin\Ors\Livrer;

use App\Dto\Magasin\Ors\Livrer\MaterielALivrerDto;

class MaterielALivrerFactory
{
    /**
     * Retourne le Dto correspondant à une ligne de matériel d'un OR validé à livrer
     *
     * @param array{reference_dit:string,numero_or:string,date_planning:?string,niveau_urgence:?string,date_creation:?string,agence_crediteur:string,service_crediteur:string,agence_debiteur:string,service_debiteur:string,numero_intervention:string,numero_ligne:string,constructeur:string,reference_piece:string,designation:string,quantite_demandee:string,quantite_a_livrer:string,quantite_livree:string,nom_prenom:string,situation:string,id_user:string,nom_utilisateur:string,id_materiel:string,numero_serie:string,numero_parc:string,marque:string,casier:string,numero_commande:string} $data ligne brute issue de la requête
     *
     * @return ?MaterielALivrerDto
     */
    public function hydrate(array $data): ?MaterielALivrerDto
    {
        if (empty($data)) return null;

        $dto = new MaterielALivrerDto;

        $dto->referenceDit       = $data['reference_dit'];
        $dto->numeroOr           = $data['numero_or'];
        $dto->datePlanning       = !empty($data['date_planning']) ? new \DateTime($data['date_planning']) : null;
        $dto->niveauUrgence      = $data['niveau_urgence'];
        $dto->dateCreation       = !empty($data['date_creation']) ? new \DateTime($data['date_creation']) : null;
        $dto->agenceCrediteur    = $data['agence_crediteur'];
        $dto->serviceCrediteur   = $data['service_crediteur'];
        $dto->agenceDebiteur     = $data['agence_debiteur'];
        $dto->serviceDebiteur    = $data['service_debiteur'];
        $dto->numeroIntervention = (int) $data['numero_intervention'];
        $dto->numeroLigne        = (int) $data['numero_ligne'];
        $dto->constructeur       = $data['constructeur'];
        $dto->referencePiece     = $data['reference_piece'];
        $dto->designation        = $data['designation'];
        $dto->quantiteDemandee   = (int) $data['quantite_demandee'];
        $dto->quantiteALivrer    = (int) $data['quantite_a_livrer'];
        $dto->quantiteLivree     = (int) $data['quantite_livree'];
        $dto->nomPrenom          = $data['nom_prenom'];
        $dto->situation          = $data['situation'];
        $dto->idUser             = $data['id_user'];
        $dto->nomUtilisateur     = $data['nom_utilisateur'];
        $dto->idMateriel         = $data['id_materiel'];
        $dto->numeroSerie        = $data['numero_serie'];
        $dto->numeroParc         = $data['numero_parc'];
        $dto->marque             = $data['marque'];
        $dto->casier             = $data['casier'];
        $dto->numeroCommande     = $data['numero_commande'];

        return $dto;
    }
}
