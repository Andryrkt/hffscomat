<?php

namespace App\Mapper\Atelier\Dit;

use App\Dto\Atelier\Dit\DitDto;
use DateTime;


class DitListeMapper
{
    public function map(array $data): array
    {
        return array_map(function ($item) {

            $dto = new DitDto();
            $dto->statutDemande = $item['statut'];
            $dto->numeroDemandeIntervention = $item['numero_dit'];
            $dto->reparationRealise = $item['realise_par'];
            $dto->typeDocument = $item['type_document'];
            $dto->worNiveauUrgence = $item['niveau_urgence'];
            $dto->categorieDemande = $item['categorie'];
            $dto->numSerie = $item['numero_serie'];
            $dto->numParc = $item['numero_parc'];
            $dto->dateDemande = $item['date_demande'];
            $dto->internetExterne = $item['int_ext'];
            $dto->agenceServiceEmetteur = $item['emetteur'];
            $dto->agenceServiceDebiteur = $item['debiteur'];
            $dto->objetDemande = $item['objet'];
            $dto->sectionAffectee = $item['section_affectee'];
            $dto->numeroDevisRattacher = $item['numero_devis'];
            $dto->statutDevis = $item['statut_devis'];
            $dto->numeroOr = $item['numero_or'];
            $dto->statutOr = $item['statut_or'];
            $dto->montantOr = $item['montantitv'];
            $dto->dateSoumissionOr = $item['datesoumission'];
            $dto->etatFacturation = $item['statut_facture'];
            $dto->ri = $item['ri'];
            $dto->utilisateurDemandeur = $item['utilisateur'];

            return $dto;
        }, $data);
    }
}
