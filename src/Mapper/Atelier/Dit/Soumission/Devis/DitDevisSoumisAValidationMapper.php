<?php

namespace App\Mapper\Atelier\Dit\Soumission\Devis;

use App\Constants\atelier\dit\soumission\Devis\ConstantStatutDevis;
use App\Dto\Atelier\Dit\soumission\Devis\DitDevisSoumisAValidationDto;

class DitDevisSoumisAValidationMapper
{
    public static function map(DitDevisSoumisAValidationDto $dto)
    {
        return array_map(function ($item) use ($dto) {
            $devisDto = new DitDevisSoumisAValidationDto();
            $devisDto->numeroVersion = $dto->numeroVersion;
            $devisDto->dateHeureSoumission = $dto->dateHeureSoumission;
            $devisDto->numeroDevis = $dto->numeroDevis;
            $devisDto->numeroDit = $dto->numeroDit;
            $devisDto->numeroItv = $item['numero_itv'];
            $devisDto->nombreLigneItv = $item['nombre_ligne'];
            $devisDto->montantItv = $item['montant_itv'];
            $devisDto->montantPiece = $item['montant_piece'];
            $devisDto->montantMo = $item['montant_mo'];
            $devisDto->montantAchatLocaux = $item['montant_achats_locaux'];
            $devisDto->montantFraisDivers = $item['montant_divers'];
            $devisDto->montantLubrifiants = $item['montant_lubrifiants'];
            $devisDto->libellelItv = $item['libelle_itv'];
            $devisDto->natureOperation = $item['nature_operation'];
            $devisDto->montantForfait = $item['montant_forfait'];
            $devisDto->nomClient = $dto->infoDit['libelle_client'];
            $devisDto->numeroClient = $dto->infoDit['numero_client'];
            $devisDto->objetDit = $dto->infoDit['objet_demande'];
            $devisDto->devisVenteOuForfait = $dto->estCeVente ? 'DEVIS VENTE' : 'DEVIS FORFAIT';
            $devisDto->devise = $item['devise'];
            $devisDto->type = $dto->type;
            $devisDto->codeSociete = $dto->codeSociete;
            $devisDto->montantVente = $item['montant_vente'];
            $devisDto->nombreLignePiece = $dto->nbPieceSortieMagasin;

            return $devisDto;
        }, $dto->infoDevisIps);
    }


    public static function enregistreDevis(DitDevisSoumisAValidationDto $dto)
    {
        return array_map(function ($item) use ($dto) {
            return [
                'numerodit' => $dto->numeroDit,
                'numerodevis' => $dto->numeroDevis,
                'numeroitv' => $item['numero_itv'],
                'nombreligneitv' => $item['nombre_ligne'],
                'montantitv' => $item['montant_itv'],
                'numeroversion' => $dto->numeroVersion,
                'montantpiece' => $item['montant_piece'],
                'montantmo' => $item['montant_mo'],
                'montantachatlocaux' => $item['montant_achats_locaux'],
                'montantfraisdivers' => $item['montant_divers'],
                'montantlubrifiants' => $item['montant_lubrifiants'],
                'libellelitv' => $item['libelle_itv'],
                'statut' => $dto->type = 'VP' ? ConstantStatutDevis::PRIX_A_CONFIRMER : ConstantStatutDevis::A_VALIDER_ATELIER,
                'dateheuresoumission' => $dto->dateHeureSoumission,
                'montantforfait' => $item['montant_forfait'],
                'natureoperation' => $item['nature_operation'],
                'devisventeouforfait' => $dto->estCeVente ? 'DEVIS VENTE' : 'DEVIS FORFAIT',
                'devise' => $item['devise'],
                'montantvente' => $item['montant_vente'],
                'num_migr' => null,
                'montantrevient' => null,
                'margerevient' => null,
                'type' => $dto->type,
                'nombrelignepiece' => $dto->nbPieceSortieMagasin,
                'tache_validateur' => $dto->tacheValidateur,
                'observation' => null,
                'code_societe' => $dto->codeSociete
            ];
        }, $dto->infoDevisIps);
    }

    public static function updateDit(DitDevisSoumisAValidationDto $dto)
    {
        return [
            'numero_devis_rattache' => $dto->numeroDevis,
            'statut_devis' => $dto->type = 'VP' ? ConstantStatutDevis::PRIX_A_CONFIRMER : ConstantStatutDevis::A_VALIDER_ATELIER,
        ];
    }
}
