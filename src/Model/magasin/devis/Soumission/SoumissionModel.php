<?php

namespace App\Model\magasin\devis\Soumission;

use App\Dto\Magasin\Devis\Soumission\SoumissionDto;
use App\Mapper\Magasin\Devis\Soumission\SoumissionMapper;
use App\Model\Informix\InsertQueryBuilder;
use App\Model\Model;
use App\Service\GlobalVariablesService;

class SoumissionModel extends Model
{
    /**
     * Compter le nombre de constructeur CAT
     * si 100% TOUT CAT sion TOUT N'EST PAS CAT
     *
     * @param string $numeroDevis
     */
    public function getConstructeur(string $numeroDevis)
    {
        $cstMagasin = GlobalVariablesService::get('pieces_magasin');
        $statement = " SELECT 
                CASE 
                    WHEN COUNT(*) = 0 THEN 'AUCUNE CONSTRUCTEUR'
                    WHEN COUNT(CASE WHEN nlig_constp = 'CAT' THEN 1 END) = COUNT(*) THEN 'TOUT CAT'
                    ELSE 'TOUS NEST PAS CAT'
                END as resultat
            FROM {$this->dbIps}.neg_lig 
            WHERE nlig_numcde = '$numeroDevis' 
            --AND nlig_constp NOT LIKE 'Nmc%'
            --AND nlig_constp IN ($cstMagasin)
            and nlig_codg = 'ST'
    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'resultat')[0];
    }

    /**
     * Récupère le situation de pièce
     * 
     * cette méthode utilise la table neg_lig pour récupérer le constructeur de la pièce magasin
     * 
     * @param string $numeroDevis Le numéro de devis à vérifier
     * @return string Le constructeur de la pièce magasin
     */
    public function constructeurPieceMagasin(string $numeroDevis, string $codeSociete)
    {
        $constructeurMagasinSansCat = GlobalVariablesService::get('pieceMagasinSansCat');
        $constructeurPneumatique = GlobalVariablesService::get('pneumatique');
        $statement = "SELECT 
                    CASE
                    -- si CAT et autre constructeur magasin
                        WHEN COUNT(CASE WHEN nlig_constp = 'CAT' THEN 1 END) > 0
                        AND COUNT(CASE WHEN nlig_constp  IN ($constructeurMagasinSansCat) THEN 1 END) > 0
                        THEN TRIM('CP')
                    -- si  CAT
                        WHEN COUNT(CASE WHEN nlig_constp  = 'CAT' THEN 1 END) > 0
                        AND COUNT(CASE WHEN nlig_constp  IN ($constructeurMagasinSansCat) THEN 1 END) = 0
                        THEN TRIM('C')
                    -- si ni CAT ni autre constructeur magasin
                        WHEN COUNT(CASE WHEN nlig_constp  = 'CAT' THEN 1 END) = 0
                        AND COUNT(CASE WHEN nlig_constp  IN ($constructeurMagasinSansCat) THEN 1 END) = 0
                        THEN TRIM('N')
                    -- si autre constructeur magasin
                        WHEN COUNT(CASE WHEN nlig_constp  = 'CAT' THEN 1 END) = 0
                        AND COUNT(CASE WHEN nlig_constp IN ($constructeurMagasinSansCat) THEN 1 END) > 0
                        THEN TRIM('P')
                    -- si constructeur pneumatique
                        WHEN COUNT(CASE WHEN nlig_constp IN($constructeurPneumatique) THEN 1 END) > 0
                        THEN TRIM('O')
                    -- si CAT , autre constructeur magasin et constructeur pneumatique
                        WHEN COUNT(CASE WHEN nlig_constp = 'CAT' THEN 1 END) > 0
                        AND COUNT(CASE WHEN nlig_constp  IN ($constructeurMagasinSansCat) THEN 1 END) > 0
                        AND COUNT(CASE WHEN nlig_constp IN($constructeurPneumatique) THEN 1 END) > 0
                        THEN TRIM('CPO')
                    -- si CAT et constructeur pneumatique
                        WHEN COUNT(CASE WHEN nlig_constp  = 'CAT' THEN 1 END) > 0
                        AND COUNT(CASE WHEN nlig_constp  IN ($constructeurMagasinSansCat) THEN 1 END) = 0
                        AND COUNT(CASE WHEN nlig_constp IN($constructeurPneumatique) THEN 1 END) > 0
                        THEN TRIM('HF')
                    -- si autre constructeur magasin et constructeur pneumatique
                        WHEN COUNT(CASE WHEN nlig_constp  = 'CAT' THEN 1 END) = 0
                        AND COUNT(CASE WHEN nlig_constp IN ($constructeurMagasinSansCat) THEN 1 END) > 0
                        AND COUNT(CASE WHEN nlig_constp IN($constructeurPneumatique) THEN 1 END) > 0
                        THEN TRIM('PO')
                    -- si ni CAT ni autre constructeur magasin ni constructeur pneumatique
                        WHEN COUNT(CASE WHEN nlig_constp  = 'CAT' THEN 1 END) = 0
                        AND COUNT(CASE WHEN nlig_constp  IN ($constructeurMagasinSansCat) THEN 1 END) = 0
                        AND COUNT(CASE WHEN nlig_constp IN($constructeurPneumatique) THEN 1 END) = 0
                        THEN TRIM('NO')
                    -- sinon
                        ELSE 'N'
                    END AS retour

                    from {$this->dbIps}.neg_lig 
                    where nlig_soc='$codeSociete' 
                    and nlig_natop='DEV'
                    and nlig_constp <> 'Nmc' 
                    and nlig_numcde = '$numeroDevis'
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'retour')[0];
    }

    public function getNumeroVersion(string $numeroDevis)
    {
        $statement = "SELECT FIRST 1 MAX(numero_version) as version FROM {$this->dbIrium}.devis_soumis_a_validation_neg dneg WHERE dneg.numero_devis = '$numeroDevis'";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'version')[0];
    }

    /**
     * Récupère les informations du devis IPS
     * 
     * cette méthode utilise la table neg_lig et neg_ent pour récupérer les informations du devis IPS
     * 
     * @param string $numeroDevis Le numéro de devis
     * @param string $codeSociete Le code société de l'utilisateur
     * @return array Les informations du devis IPS
     */
    public function getInfoDevis(string $numeroDevis, string $codeSociete)
    {
        $this->connect->connect();

        try {
            $statement = "SELECT 
                        nent_devise as devise
                        ,nent_cdeht as montant_devis
                        ,SUM(nlig_nolign) as somme_numero_lignes 
                    from {$this->dbIps}.neg_lig 
                    left JOIN {$this->dbIps}.neg_ent on nent_numcde = nlig_numcde 
                    where nlig_soc='$codeSociete' 
                    and nlig_natop='DEV' 
                    and nlig_constp <> 'Nmc'
                    and nlig_numcde = '$numeroDevis'
                    group by nent_devise, nent_cdeht
            ";

            $result = $this->connect->executeQuery($statement);
            $rows = $this->connect->fetchScalarResults($result);

            return $rows;
        } finally {
            $this->connect->close();
        }
    }

    /**
     * Methode pour récupération des informations du devis 
     * déjà enregistrer dans devis_soumis_a_validation_neg
     *
     * @param string $numeroDevis
     * @param string $codeSociete
     * @return array
     */
    public function getInfoDevisForValidate(string $numeroDevis, string $codeSociete): array
    {
        $this->connect->connect();

        try {
            $statement = "SELECT 
                        dneg.numero_devis as numero_devis
                        ,dneg.statut_dw as statut
                        ,dneg.montant_devis as montant_devis
                        ,dneg.somme_numero_lignes as somme_numero_lignes

                    from {$this->dbIrium}.devis_soumis_a_validation_neg dneg
                    where dneg.code_societe = '$codeSociete'
                    and dneg.numero_devis = '$numeroDevis'
                    order by dneg.numero_version desc
                    limit 1
            ";

            $result = $this->connect->executeQuery($statement);
            $rows = $this->connect->fetchScalarResults($result);

            return $rows;
        } finally {
            $this->connect->close();
        }
    }
    /**
     * Methode pour enregistrer les données du formulaire Verification prix
     *  dans la base de donnée
     *
     * @param SoumissionDto $dto
     * @param string $nomFichier
     * @param string $nomFichierExcel
     * @return void
     */
    public function enregistrerSoumission(SoumissionDto $dto, string $nomFichier, string $nomFichierExcel): void
    {
        // Convertir le DTO en tableau associatif pour l'insertion
        $donnees = SoumissionMapper::toArrayVerificationPrix($dto, $nomFichier, $nomFichierExcel);

        // Construire la requête d'insertion et l'exécuter
        $builder = new InsertQueryBuilder("{$this->dbIrium}.devis_soumis_a_validation_neg");
        $builder->setData($donnees);
        $result = $builder->build();

        // Exécuter la requête d'insertion
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            $this->connect->executeQuery($result['sql'], $result['params']);
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }
    /**
     * Methode pour enregistrer les données du formulaire Validation de devis
     *  dans la base de donnée
     *
     * @param SoumissionDto $dto
     * @param string $nomFichier
     * @param string $nomFichierExcel
     * @return void
     */
    public function enregistrerSoumissionValidationDevis(SoumissionDto $dto, string $nomFichier): void
    {
        // Convertir le DTO en tableau associatif pour l'insertion
        $donnees = SoumissionMapper::toArrayValidationDevis($dto, $nomFichier);

        // Construire la requête d'insertion et l'exécuter
        $builder = new InsertQueryBuilder("{$this->dbIrium}.devis_soumis_a_validation_neg");
        $builder->setData($donnees);
        $result = $builder->build();

        // Exécuter la requête d'insertion
        // S'assurer que la connexion est ouverte
        $this->connect->connect();
        try {
            $this->connect->executeQuery($result['sql'], $result['params']);
        } finally {
            // ne fermez ici que si vous êtes sûr que c'est la dernière opération
            $this->connect->close();
        }
    }

    public function tableauDeMarge(string $codeSociete, string $numeroCde)
    {
        $statement = "SELECT
                numero_cde                                               As numero_cde,
                TRIM(categorie_constp)                                  As constructeur,
                TRIM(disponibilite)                                     As disponibilite,
                --SUM(astp_stock)                                                AS nb_ref,
                COUNT(*)                                                AS nb_ref,
                SUM(nlig_pmp)                                           AS somme_pmp,
                SUM(nlig_pxvteht)                                       AS somme_pxvteht,
                SUM(nlig_pxvteht - nlig_pxnreel)                   AS somme_remise,
                SUM(nlig_pxnreel)               AS somme_pxvte_remise,
                SUM(nlig_pxnreel - nlig_pmp)  AS somme_marge_brute,
                ROUND(CASE
                    WHEN SUM(nlig_pxnreel) = 0 THEN NULL
                    ELSE SUM(nlig_pxnreel - nlig_pmp)
                        / SUM(nlig_pxnreel) * 100
                END)                                                      AS pct_marge_brute,
                ROUND(MAX(CASE
                    WHEN nlig_pxnreel <> 0
                    THEN (nlig_pxnreel - nlig_pmp) / (nlig_pxnreel) * 100
                END))                                                     AS pct_mb_max,
                ROUND(MIN(CASE
                    WHEN nlig_pxnreel <> 0
                    THEN (nlig_pxnreel - nlig_pmp) / (nlig_pxnreel) * 100
                END))                                                      AS pct_mb_min
            FROM (
                SELECT
                    l.nlig_numcde AS numero_cde,
                    CASE
                        WHEN l.nlig_constp = 'MFN' THEN 'MFN'
                        WHEN l.nlig_constp = 'CAT' THEN 'CAT'
                        ELSE 'AUTRE'
                    END AS categorie_constp,
                    CASE WHEN s.astp_stock > 0 THEN 'DISPONIBLE' ELSE 'NON_DISPONIBLE' END AS disponibilite,
                    l.nlig_pmp,
                    l.nlig_pxvteht,
                    l.nlig_pxnreel
                    --l.nlig_remise
                    --s.astp_stock AS astp_stock 
                FROM neg_lig l
                INNER JOIN art_stp s
                    ON s.astp_constp = l.nlig_constp
                    AND s.astp_refp = l.nlig_refp
                    AND s.astp_soc = l.nlig_soc
                INNER JOIN art_bse b
                    ON b.abse_constp = l.nlig_constp
                    AND b.abse_refp = l.nlig_refp
                    AND b.abse_codg = 'ST'
                WHERE l.nlig_numcde = '$numeroCde'
                AND l.nlig_soc = '$codeSociete'
            ) t
            GROUP BY numero_cde, categorie_constp, disponibilite
            ORDER BY categorie_constp, disponibilite DESC
       ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data;
    }

    public function tableauDeMargeAvecReference(
        string $codeSociete,
        string $numeroCde,
        string $references,
        string $codeSuccursale = '1'
    ) {
        $statement = "WITH stats_max AS (
                SELECT FIRST 1
                    MAX(nlig_pxnreel - nlig_pmp) AS max_mb,
                    CASE
                        WHEN nlig_pxnreel = 0 THEN 0
                        ELSE ROUND(((nlig_pxnreel - nlig_pmp) / nlig_pxnreel) * 100, 2)
                    END AS max_mb_p
                FROM Informix.neg_lig nlig
                INNER JOIN Informix.neg_ent on nent_soc = nlig_soc and nent_succ = nlig_succ and nent_numcde = nlig_numcde
                WHERE nlig_refp = '$references'
                    AND nlig_soc = '$codeSociete'
                    AND nlig_succ = '$codeSuccursale'
                    AND nent_posf in ('CP','FC','PF','TF')
                GROUP BY 2
                ORDER BY MAX(nlig_pxnreel - nlig_pmp) DESC
            ),
            stats_min AS (
                SELECT FIRST 1
                    MIN(nlig_pxnreel - nlig_pmp) AS min_mb,
                    CASE
                        WHEN nlig_pxnreel = 0 THEN 0
                        ELSE ROUND(((nlig_pxnreel - nlig_pmp) / nlig_pxnreel) * 100, 2)
                    END AS min_mb_p
                FROM Informix.neg_lig nlig
                INNER JOIN Informix.neg_ent on nent_soc = nlig_soc and nent_succ = nlig_succ and nent_numcde = nlig_numcde
                WHERE nlig_refp = '$references'
                    AND nlig_soc = '$codeSociete'
                    AND nlig_succ = '$codeSuccursale'
                    AND nent_posf in ('CP','FC','PF','TF')
                GROUP BY 2
                ORDER BY MIN(nlig_pxnreel - nlig_pmp) ASC
            )
            SELECT
                nlig_constp AS constructeur,
                -- Stock
                ROUND(CASE WHEN astp_stock IS NULL THEN 0 ELSE astp_stock END) AS nb_ref,
                TRIM(nlig_refp) AS reference,
                TRIM(nlig_desi) AS designation,
                ROUND(nlig_qtecde) AS quantite_demander,

                -- Prix et remises
                ROUND(nlig_pmp, 2) AS pmp,
                nlig_pxvteht AS pv_brut,
                (nlig_pxvteht - nlig_pxnreel) AS mt_remise,
                nlig_pxnreel AS pv_net_remise,

                -- Marge brute
                ROUND(nlig_pxnreel - ROUND(nlig_pmp, 2), 2) AS mb,

                -- Marge brute en pourcentage
                CASE
                    WHEN nlig_pxnreel = 0 THEN 0
                    ELSE ROUND(((nlig_pxnreel - ROUND(nlig_pmp, 2)) / nlig_pxnreel) * 100, 2)
                END AS mb_p,

                -- Maximum MB (issu de la ligne réelle correspondante)
                COALESCE(stats_max.max_mb, 0) AS max_mb,
                COALESCE(stats_max.max_mb_p, 0) AS max_mb_p,

                -- Minimum MB (issu de la ligne réelle correspondante)
                COALESCE(stats_min.min_mb, 0) AS min_mb,
                COALESCE(stats_min.min_mb_p, 0) AS min_mb_p,

                -- Famille
                abse_fams1 || '-' || atab_lib as famille

            FROM Informix.neg_lig
            INNER JOIN Informix.art_stp
                ON astp_refp = nlig_refp
                AND astp_soc = nlig_soc
                AND astp_succ = nlig_succ
                AND astp_constp = nlig_constp
            INNER JOIN Informix.art_bse on abse_refp = nlig_refp
            INNER JOIN Informix.agr_tab on atab_nom = 'STA' and atab_code = abse_fams1
            CROSS JOIN stats_max
            CROSS JOIN stats_min
            WHERE nlig_numcde = '$numeroCde'
                AND nlig_succ = '$codeSuccursale'
                AND nlig_soc = '$codeSociete'
                AND nlig_refp = '$references';
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data;
    }

    public function getInfoDeviSansJointure(string $numeroDevis, string $codeSociete)
    {
        $statement = "SELECT nlig_refp as ref, 
                            nlig_succ as code_agence,
                            nlig_soc as code_societe,
                            nlig_numcde as numero_devis
            FROM informix.neg_lig
            WHERE nlig_numcde = '$numeroDevis'
                and nlig_soc = '$codeSociete'
                and nlig_codg = 'ST'
                and nlig_natop = 'DEV'
                and nlig_constp <> 'Nmc'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data;
    }
}
