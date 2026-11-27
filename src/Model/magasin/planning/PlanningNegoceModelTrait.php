<?php

namespace App\Model\magasin\planning;

use App\Service\TableauEnStringService;

trait PlanningNegoceModelTrait
{
    private function numcommande($criteria)
    {
        if (!empty($criteria->getNumOr())) {
            $numCommande = "AND nent_numcde = '" . $criteria->getNumOr() . "' ";
        } else {
            $numCommande = "";
        }
        return $numCommande;
    }

    private function agenceDebite($criteria, string $codeAgence)
    {
        if ($codeAgence !== "-0") {
            $agenceDebite = " AND nent_succ = '$codeAgence' ";
        } elseif (!empty($criteria->getAgenceDebite())) {
            $agenceDebite = " AND nent_succ = '" . $criteria->getAgenceDebite() . "' ";
        } else {
            $agenceDebite = "";
        }

        return $agenceDebite;
    }

    private function serviceDebite($criteria)
    {
        if (!empty($criteria->getServiceDebite())) {
            $serviceDebite = " AND nent_servcrt in ('" . implode("','", $criteria->getServiceDebite()) . "')";
        } else {
            $serviceDebite = "";
        }
        return  $serviceDebite;
    }
    private function codeClient($criteria)
    {
        if (!empty($criteria->getNumParc())) {
            $vconditionNumParc = " AND nent_numcli  = '" . $criteria->getNumParc() . "'";
        } else {
            $vconditionNumParc = "";
        }
        return $vconditionNumParc;
    }
    private function commercial($criteria)
    {
        if (!empty($criteria->getCommercial())) {
            $codeCommercial = explode('-', $criteria->getCommercial())[0];
            $condCommercial = " AND TRIM(nent_codope) ='$codeCommercial'  ";
        } else {
            $condCommercial = "";
        }
        return $condCommercial;
    }
    private function refClient($criteria)
    {
        if (!empty($criteria->getRefcde())) {
            $condRefclient = "AND NENT_REFCDE like '%" . $criteria->getRefcde() . "%'  ";
        } else {
            $condRefclient = "";
        }
        return $condRefclient;
    }

    private function numeroDevis($criteria)
    {
        if (!empty($criteria->getNumeroDevis())) {
            $condNumeroDevis = "AND nent_numcde = '" . $criteria->getNumeroDevis() . "'  ";
        } else {
            $condNumeroDevis = "";
        }
        return $condNumeroDevis;
    }

    /**
     * pour le magasin ce n'est pas une OR mais une BC 
     * BC => table bc_client_soumis_neg
     */
    private function orNonValiderDW($criteria, array $numeroDevisValideBcClient)
    {
        $bcNonValide = !empty($criteria->getOrNonValiderDw()) && $criteria->getOrNonValiderDw();

        // aucun devis validé : toutes les BC sont non validées
        if (empty($numeroDevisValideBcClient)) {
            return $bcNonValide ? "" : " AND 1 = 0 ";
        }

        if ($bcNonValide) {
            $value = TableauEnStringService::notLike($numeroDevisValideBcClient, 'nent_libcde');
            // NOT LIKE sur NULL ne renvoie jamais vrai
            $orNonValiderDW = " AND  (nent_libcde IS NULL OR ($value)) ";
        } else {
            $value = TableauEnStringService::like($numeroDevisValideBcClient, 'nent_libcde');
            $orNonValiderDW = " AND  ($value) ";
        }

        return $orNonValiderDW;
    }

    private function orBackOrder($criteria)
    {
        if (!empty($criteria->getOrBackOrder()) && $criteria->getOrBackOrder()) {
            $orBackOrder = " AND nent_numcde in ('0')";
        } else {
            $orBackOrder = "";
        }

        return $orBackOrder;
    }
}
