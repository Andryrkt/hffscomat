<?php

namespace App\Model\Atelier\Dit\Soumission\AcBc;

use App\Dto\Atelier\Dit\soumission\AcBc\AccuseReceptionDto;
use App\Dto\Atelier\Dit\soumission\AcBc\BcSoumisDto;
use App\Factory\Atelier\Dit\soumission\AcBc\AccuseReceptionFactory;
use App\Model\Informix\InsertQueryBuilder;
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
                    '$codeSociete' as code_societe,
                    dsav.version_bcs,
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
                    dit.numero_client,
                    COALESCE(b.version, 0) AS version_bcs
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
                LEFT JOIN (
                    SELECT 
                        numerodit,
                        code_societe,
                        MAX(numeroversion) AS version
                    FROM {$this->dbIrium}:Informix.bc_soumis
                    GROUP BY numerodit, code_societe
                ) b
                    ON b.numerodit = d.numeroDit
                    AND b.code_societe = d.code_societe
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

    /** 
     * Méthode pour retourner le numéro de version maximum du BC
     * 
     * @param string $numeroBc    numéro du BC
     * @param string $codeSociete code société
     * 
     * @return int
     */
    public function findNumeroVersionMaxBcSoumis(string $numeroBc, string $codeSociete): int
    {
        $statement = "SELECT FIRST 1 MAX(numeroversion) as version 
        FROM {$this->dbIrium}:Informix.bc_soumis b 
        WHERE b.numerobc = '$numeroBc' AND b.code_societe = '$codeSociete'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchScalarResults($result);

        return $this->convertirEnUtf8($data['version'] ?? 0);
    }

    /** 
     * Enregistrer le BC soumis
     * 
     * @param BcSoumisDto $bcSoumisDto
     * 
     * @return void
     */
    public function enregistrerBcSoumis(BcSoumisDto $bcSoumisDto): void
    {
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            // Construire la requête d'insertion et l'exécuter
            $builder = new InsertQueryBuilder("{$this->dbIrium}:Informix.bc_soumis");
            $builder->setData([
                'numerodit'           => $bcSoumisDto->numeroDit,
                'numerodevis'         => $bcSoumisDto->numeroDevis,
                'numerobc'            => $bcSoumisDto->numeroBc,
                'numeroversion'       => $bcSoumisDto->numeroVersion,
                'datebc'              => $bcSoumisDto->dateBc->format('Y-m-d'),
                'datedevis'           => $bcSoumisDto->dateDevis->format('Y-m-d'),
                'montantdevis'        => $bcSoumisDto->montantDevis,
                'dateheuresoumission' => $bcSoumisDto->dateSoumissionBc->format('Y-m-d H:i:s'),
                'nomfichier'          => $bcSoumisDto->nomFichier,
                'statut'              => $bcSoumisDto->statut,
                'code_societe'        => $bcSoumisDto->codeSociete,
            ]);
            $result = $builder->build();

            $this->connect->executeQuery($result['sql'], $result['params']);
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }
}
