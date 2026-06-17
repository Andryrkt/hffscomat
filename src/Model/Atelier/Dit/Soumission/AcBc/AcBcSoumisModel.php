<?php

namespace App\Model\Atelier\Dit\Soumission\AcBc;

use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Factory\Atelier\Dit\soumission\AcBc\AccuseReceptionFactory;
use App\Model\Model;

class AcBcSoumisModel extends Model
{

    /** 
     * Méthode pour retourner les infos sur le Devis associé au DIT avec $numDit
     * 
     * @param string $numDit      numéro du DIT
     * @param string $codeSociete code société
     * 
     * @return ?AccuseReceptionDto
     */
    public function findInfoDevis(string $numDit, string $codeSociete): ?AccuseReceptionDto
    {
        $statement = "WITH
                dsav AS ({$this->getQueryDevisSoumisValide($numDit,$codeSociete)}),
                itv AS ({$this->getQueryNbrIntervention($codeSociete, 'dsav')}),
                cst AS ({$this->getQueryConstRefFirstDevis($codeSociete, 'dsav')}),
                first_itv AS (
                    SELECT FIRST 1
                        montantItv
                    FROM dsav
                    ORDER BY numeroItv ASC
                ),
                all_itv AS (
                    SELECT SUM(montantItv) AS total_montant
                    FROM dsav
                )
                SELECT FIRST 1
                    dsav.numeroDit AS numero_dit,
                    dsav.numeroDevis AS numero_devis,
                    dsav.statut AS statut_devis,
                    dsav.dateheuresoumission AS date_soumission,
                    dsav.devise AS devise,
                    dsav.internet_externe AS interne_externe,
                    dsav.numero_client as numero_client,
                    CASE
                        WHEN cst.nom = 'ZDI-FORFAIT' AND itv.nb > 0
                        THEN first_itv.montantItv
                        ELSE all_itv.total_montant
                    END AS montant
                FROM dsav
                CROSS JOIN itv
                CROSS JOIN cst
                CROSS JOIN first_itv
                CROSS JOIN all_itv";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchScalarResults($result));

        return (new AccuseReceptionFactory)->hydrate($data);
    }

    private function getQueryDevisSoumisValide(string $numDit, string $codeSociete): string
    {
        return "SELECT 
                    d.numeroDit,
                    d.numeroDevis,
                    d.montantItv,
                    d.statut,
                    d.dateheuresoumission,
                    d.numeroItv,
                    d.numeroVersion,
                    d.devise,
                    dit.internet_externe,
                    dit.numero_client
                FROM {$this->dbIrium}:Informix.devis_soumis_a_validation d
                JOIN {$this->dbIrium}:Informix.demande_intervention dit
                    ON dit.numero_demande_dit = d.numeroDit
                    AND dit.code_societe = d.code_societe
                JOIN (
                    SELECT 
                        numeroDit,
                        code_societe,
                        MAX(numeroVersion) AS maxVersion
                    FROM {$this->dbIrium}:Informix.devis_soumis_a_validation
                    WHERE numeroDit = '$numDit'
                    AND code_societe = '$codeSociete'
                    GROUP BY numeroDit, code_societe
                ) m
                    ON m.numeroDit = d.numeroDit
                    AND m.code_societe = d.code_societe
                    AND m.maxVersion = d.numeroVersion
                WHERE d.statut LIKE 'Valid%' AND d.statut LIKE '%atelier'";
    }

    private function getQueryNbrIntervention(string $codeSociete, string $aliasDevisSoumisValide): string
    {
        return "SELECT 
                    COUNT(DISTINCT l.slor_nogrp) AS nb
                FROM {$this->dbIps}:Informix.sav_lor l
                JOIN (
                    SELECT DISTINCT numeroDevis
                    FROM $aliasDevisSoumisValide
                ) d
                ON d.numeroDevis = l.slor_numor
                WHERE l.slor_nogrp <> 100
                AND l.slor_soc = '$codeSociete'";
    }

    private function getQueryConstRefFirstDevis(string $codeSociete, string $aliasDevisSoumisValide): string
    {
        return "SELECT FIRST 1
                    TRIM(l.slor_constp||'-'||l.slor_refp) AS nom
                FROM {$this->dbIps}:Informix.sav_lor l
                JOIN (
                    SELECT DISTINCT numeroDevis
                    FROM $aliasDevisSoumisValide
                ) d
                ON d.numeroDevis = l.slor_numor
                WHERE l.slor_nogrp = 100
                AND l.slor_soc = '$codeSociete'
                ORDER BY l.slor_nolign";
    }
}
