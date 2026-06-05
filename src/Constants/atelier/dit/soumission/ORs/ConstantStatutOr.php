<?php

namespace App\Constants\atelier\dit\soumission\ORs;

class ConstantStatutOr
{

    public const STATUT_VIDE                       = '';
    public const STATUT_A_RESOUMETTRE_A_VALIDATION = 'A resoumettre à validation';
    public const STATUT_A_VALIDER_CA               = 'A valider chef atelier';
    public const STATUT_A_VALIDER_CLIENT           = 'A valider client interne';
    public const STATUT_A_VALIDER_DT               = 'A valider directeur technique';
    public const STATUT_MODIF_DEMANDE_PAR_CA       = 'Modification demandée par CA';
    public const STATUT_MODIF_DEMANDE_PAR_CLIENT   = 'Modification demandée par client';
    public const STATUT_REFUSE_CA                  = 'Refusé chef atelier';
    public const STATUT_REFUSE_CLIENT              = 'Refusé client interne';
    public const STATUT_REFUSE_DT                  = 'Refusé DT';
    public const STATUT_SOUMIS_A_VALIDATION        = 'Soumis à validation';
    public const STATUT_VALIDE                     = 'Validé';
}
