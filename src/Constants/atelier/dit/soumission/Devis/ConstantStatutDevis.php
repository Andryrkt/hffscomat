<?php

namespace App\Constants\atelier\dit\soumission\Devis;


class ConstantStatutDevis
{
    public const PRIX_REFUSE_MAGASIN = "Prix refusé magasin";
    public const PRIX_A_CONFIRMER = "Prix à confirmer";
    public const A_VALIDER_ATELIER = "A valider atelier";
    public const DEMANDE_REFUSEE_PER_LE_PM = "Demande refusée par le PM";

    public const STATUT_A_PASSER_AU_VARIFICATION_PRIX = [
        self::PRIX_A_CONFIRMER,
        self::PRIX_REFUSE_MAGASIN,
        self::DEMANDE_REFUSEE_PER_LE_PM
    ];
}
