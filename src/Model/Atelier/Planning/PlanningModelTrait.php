<?php

namespace App\Model\Atelier\Planning;

use App\Dto\Atelier\Planning\PlanningSearchDto;
use App\Service\GlobalVariablesService;

trait PlanningModelTrait
{
    private function criterAnnee(PlanningSearchDto $dto)
    {
        if (!empty($dto->annee)) {
            $annee = " '" . $dto->annee . "' ";
        } else {
            $annee = null;
        }

        return $annee;
    }

    private function facture(PlanningSearchDto $dto)
    {
        switch ($dto->facture) {
            case "TOUS":
                $vStatutFacture = " AND  sitv_pos  IN ('FC','FE','CP','ST','EC')";
                break;
            case "FACTURE":
                $vStatutFacture = " AND  sitv_pos IN ('FC','FE','CP','ST')";
                break;
            case "ENCOURS":
                $vStatutFacture = " AND sitv_pos NOT IN ('FC','FE','CP','ST')";
                break;
        }

        return $vStatutFacture;
    }

    private function section(PlanningSearchDto $dto)
    {
        if (!empty($dto->section)) {
            $section = " AND sitv_typitv = '" . $dto->section . "' ";
        } else {
            $section = null;
        }
        return $section;
    }


    private function typeLigne(PlanningSearchDto $dto)
    {
        switch ($dto->typeLigne) {
            case "PIECES_MAGASIN":
                $constructeurPiecesMagasin = GlobalVariablesService::get('pieces_magasin');
                $vtypeligne = " AND slor_constp in ( $constructeurPiecesMagasin ) AND slor_typlig = 'P' AND (slor_refp not like '%-L' and slor_refp not like '%-CTRL') ";
                break;
            case "ACHAT_LOCAUX":
                $constructeurAchatLocaux = GlobalVariablesService::get('achat_locaux');
                $vtypeligne = " AND slor_constp in ( $constructeurAchatLocaux )";
                break;
            case "LUBRIFIANTS":
                $constructeurLub = GlobalVariablesService::get('lub');
                $vtypeligne = " AND slor_constp in ( $constructeurLub )  AND slor_typlig = 'P'";
                break;
            case "PNEUMATIQUES":
                $constructeurPneumatique = GlobalVariablesService::get('pneumatique');
                $vtypeligne = " AND slor_constp in ( $constructeurPneumatique ) ";
                break;
            default:
                $vtypeligne = " ";
                break;
        }

        return $vtypeligne;
    }


    // private function sumPieces($criteria){

    //   switch ($criteria->getTypeLigne()) {
    //     case "TOUTES":
    //         $vPieces = " ";
    //         break;
    //     case "PIECES_MAGASIN":
    //         $vPieces = " AND slor_constp in ('AGR','ATC','AUS','CAT','CGM','CMX','DNL','DYN','GRO','HYS','JDR','KIT','MAN','MNT','OLY','OOM','PAR','PDV','PER','PUB','REM','SHM','TBI','THO') AND slor_typlig = 'P'";
    //         break;
    //     case "ACHAT_LOCAUX":
    //         $vPieces = " AND slor_constp in ('ALI','BOI','CAR','CEN','FAT','FBU','HAB','INF','MIN','OUT','ZST')" ;
    //         break;
    //     case "LUBRIFIANTS":
    //         $vPieces = "AND slor_constp in ('LUB', 'JOV')  AND slor_typlig = 'P'";
    //         break;
    //     default:
    //         $vPieces = " ";
    // }
    // return $vPieces;

    // }

    private function planAnnee(PlanningSearchDto $dto)
    {
        $yearsDatePlanifier = " CASE WHEN 
                    YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) ) is Null 
                THEN
                    YEAR(DATE(sitv_datepla)  )
                ELSE
                    YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) )
                END ";


        $yearsDateNonPlanifier = " YEAR ( DATE(sitv_datdeb) ) ";

        switch ($dto->plan) {
            case "PLANIFIE":
                $vYearsStatutPlan = $yearsDatePlanifier;
                break;
            case "NON_PLANIFIE":
                $vYearsStatutPlan = $yearsDateNonPlanifier;
        }
        return  $vYearsStatutPlan;
    }
    private function nonplannfierSansDatePla(PlanningSearchDto $dto)
    {
        $conditionSansDatePla = " AND CASE WHEN 
                    YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) ) is Null 
                THEN
                    YEAR(DATE(sitv_datepla)  )
                ELSE
                    YEAR ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) )
                END is null";
        switch ($dto->plan) {
            case "PLANIFIE":
                $vNoplanniferStatutPlan = "";
                break;
            case "NON_PLANIFIE":
                $vNoplanniferStatutPlan = $conditionSansDatePla;
        }
        return  $vNoplanniferStatutPlan;
    }
    private function planMonth(PlanningSearchDto $dto)
    {
        $monthDatePlanifier = " CASE WHEN 
                                    MONTH ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) ) is Null 
                                THEN
                                    MONTH(DATE(sitv_datepla)  )
                                ELSE
                                    MONTH ( (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) )
                                END  ";
        $monthDateNonPlanifier =  " MONTH ( DATE(sitv_datdeb) ) ";
        switch ($dto->plan) {
            case "PLANIFIE":
                $vMonthStatutPlan = $monthDatePlanifier;
                break;
            case "NON_PLANIFIE":
                $vMonthStatutPlan = $monthDateNonPlanifier;
        }
        return  $vMonthStatutPlan;
    }
    private function dateDebutMonthPlan(PlanningSearchDto $dto)
    {

        if (!empty($dto->dateDebut)) {
            $monthDatePlanifier = " CASE WHEN 
                                     (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id )  is Null 
                                THEN
                                    DATE(sitv_datepla)  
                                ELSE
                                     (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) 
                                END  ";
            $monthDateNonPlanifier =  "  DATE(sitv_datdeb)  ";
            switch ($dto->plan) {
                case "PLANIFIE":
                    $vDateDMonthStatutPlan = " AND " . $monthDatePlanifier . " >= '" . $dto->dateDebut->format("m/d/Y") . "'";
                    break;
                case "NON_PLANIFIE":
                    $vDateDMonthStatutPlan = " AND " . $monthDateNonPlanifier . " >= '" . $dto->dateDebut->format("m/d/Y") . "'";
            }
        } else {
            $vDateDMonthStatutPlan = null;
        }
        return $vDateDMonthStatutPlan;
    }
    private function dateFinMonthPlan(PlanningSearchDto $dto)
    {

        if (!empty($dto->dateFin)) {
            $monthDatePlanifier = " CASE WHEN 
                                    (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id )  is Null 
                                THEN
                                    DATE(sitv_datepla)  
                                ELSE
                                     (SELECT DATE(Min(ska_d_start) ) FROM ska, skw WHERE ofh_id = seor_numor AND ofs_id=sitv_interv AND skw.skw_id = ska.skw_id ) 
                                END  ";
            $monthDateNonPlanifier =  " DATE(sitv_datdeb)  ";
            switch ($dto->plan) {
                case "PLANIFIE":
                    $vDateFMonthStatutPlan = " AND " . $monthDatePlanifier . " <= '" . $dto->dateFin->format("m/d/Y") . "'";
                    break;
                case "NON_PLANIFIE":
                    $vDateFMonthStatutPlan = " AND " . $monthDateNonPlanifier . " <= '" . $dto->dateFin->format("m/d/Y") . "'";
            }
        } else {
            $vDateFMonthStatutPlan = null;
        }

        return $vDateFMonthStatutPlan;
    }

    private function interneExterne(PlanningSearchDto $dto)
    {
        switch ($dto->interneExterne) {
            case "TOUS":
                $vStatutInterneExterne = "";
                break;
            case "INTERNE":
                $vStatutInterneExterne = " AND SITV_NATOP = 'CES'  and SITV_TYPEOR not in ('501','601','602','603','604','605','606','607','608','609','610','611','701','702','703','704','705','706')";
                break;
            case "EXTERNE":
                $vStatutInterneExterne = "AND seor_numcli >1 ";
                break;
        }
        return $vStatutInterneExterne;
    }
    private function agence(PlanningSearchDto $dto)
    {
        if (!empty($dto->agence)) {
            $agence = " AND SEOR_SUCC in ('" . $dto->agence . "')";
        } else {
            $agence = "";
        }
        return $agence;
    }
    private function agenceDebite(PlanningSearchDto $dto)
    {
        if (!empty($dto->agenceDebite)) {
            $agenceDebite = " AND sitv_succdeb = '" . $dto->agenceDebite . "' ";
        } else {
            $agenceDebite = ""; // AND sitv_succdeb in ('01','02','90','92','40','60','50','40','30','20')
        }
        return $agenceDebite;
    }
    private function serviceDebite(PlanningSearchDto $dto)
    {
        if (!empty($dto->serviceDebite)) {
            $serviceDebite = " AND sitv_servdeb in ('" . implode("','", $dto->serviceDebite) . "')";
        } else {
            $serviceDebite = "";
        }
        return  $serviceDebite;
    }
    private function idMat(PlanningSearchDto $dto)
    {
        if (!empty($dto->idMat)) {
            $vconditionIdMat = " AND mmat_nummat = '" . $dto->idMat . "'";
        } else {
            $vconditionIdMat = "";
        }
        return $vconditionIdMat;
    }
    private function numOr(PlanningSearchDto $dto)
    {
        if (!empty($dto->numOr)) {
            $vconditionNumOr = " AND slor_numor ='" . $dto->numOr . "'";
        } else {
            $vconditionNumOr = "";
        }
        return $vconditionNumOr;
    }
    private function numSerie(PlanningSearchDto $dto)
    {
        if (!empty($dto->numSerie)) {
            $vconditionNumSerie = " AND TRIM(mmat_numserie) = '" . $dto->numSerie . "' ";
        } else {
            $vconditionNumSerie = "";
        }
        return $vconditionNumSerie;
    }

    private function numParc(PlanningSearchDto $dto)
    {
        if (!empty($dto->numParc)) {
            $vconditionNumParc = " AND mmat_recalph = '" . $dto->numParc . "'";
        } else {
            $vconditionNumParc = "";
        }
        return $vconditionNumParc;
    }
    private function casier(PlanningSearchDto $dto)
    {
        if (!empty($dto->casier)) {
            $vconditionCasier = " AND mmat_numparc like  '%" . $dto->casier . "%'  ";
        } else {
            $vconditionCasier = "";
        }
        return $vconditionCasier;
    }
}