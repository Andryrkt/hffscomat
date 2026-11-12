<?php

namespace App\Model\Atelier\CVA;

use App\Model\Model;

class OrCsaSoumissionPdfModel  extends Model
{
    private const TAUX_TVA = 0.15;
    /**
     * Récupère les données OR + Client pour un N° de DIT donné.
     * Requête issue de la spec (adaptée en concaténation de chaînes,
     * comme le reste du projet).
     *
     * @return array<string,mixed>|null
     */
    public function findOrByRefDemande(string $refDemande, string $codeSociete): ?array
    {
        $refEsc = str_replace("'", "''", $refDemande);
        $socEsc = str_replace("'", "''", $codeSociete);

        $statement = "
            SELECT
                c.cbse_nomcli          AS nom_client,
                c.cbse_adr1            AS adresse_client,
                c.cbse_bureauptt       AS bureau_postal,
                e.seor_numor           AS numero_or,
                e.seor_numcli          AS numero_client,
                e.seor_dateor          AS date_or,
                e.seor_lib             AS reference_client,
                e.seor_refdem          AS ref_demande,
                e.seor_servcrt         AS code_service,
                t.atab_lib             AS libelle_service,
                e.seor_natop           AS nature_operation,
                (
                    SELECT at2.ATAB_lib
                    FROM {$this->dbIps}.agr_tab at2
                    WHERE at2.ATAB_nom  = 'NOP'
                      AND at2.atab_code = e.seor_natop
                )                      AS libelle_nature_operation
            FROM {$this->dbIps}.sav_eor e
            INNER JOIN {$this->dbIps}.cli_bse c
                ON c.cbse_numcli = e.seor_numcli
            INNER JOIN {$this->dbIps}.agr_tab t
                ON t.atab_code = e.seor_servcrt
               AND t.atab_nom  = 'SER'
            WHERE e.seor_refdem = '{$refEsc}'
              AND e.seor_soc    = '{$socEsc}'
              AND e.seor_serv   = 'SAV'
        ";
        $result = $this->connect->executeQuery($statement);
        $rows   = $this->convertirEnUtf8($this->connect->fetchResults($result));

        if (empty($rows)) {
            return null;
        }

        // Normalisation des clés (DB2 renvoie parfois en MAJUSCULES)
        $normalized = [];
        foreach ($rows[0] as $key => $value) {
            $normalized[strtolower($key)] = $value;
        }

        return $normalized;
    }

    /**
     * Calcule les montants HT et TTC d'un OR.
     * (Reprend les deux requêtes SUM de la spec)
     *
     * @return array{exclVat: float, incVat: float}
     */
    public function calculMontants(string $numOr): array
    {
        $numOrEsc = str_replace("'", "''", $numOr);

        $statement = "
            SELECT
                SUM(
                    CASE
                        WHEN slor_typlig = 'P'
                            THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec) * slor_pxnreel
                        WHEN slor_typlig IN ('F','M','U','C')
                            THEN slor_qterea * slor_pxnreel
                    END
                ) AS mtt_excl_vat
            FROM {$this->dbIps}.sav_lor
            WHERE slor_numor = '{$numOrEsc}'
        ";

        $result = $this->connect->executeQuery($statement);
        $rows   = $this->connect->fetchResults($result);

        $exclVat = (float) ($rows[0]['mtt_excl_vat'] ?? $rows[0]['MTT_EXCL_VAT'] ?? 0.0);

        return [
            'exclVat' => $exclVat,
            'incVat'  => round($exclVat * (1 + self::TAUX_TVA), 2),
        ];
    }

    /**
     * Détail des lignes d'un OR pour la PROFORMA.
     *
     * @return array<int,array{quantity:string,reference:string,description:string,unitPrice:float,netAmount:float}>
     */
    public function findLignesByNumOr(string $numOr): array
    {
        $numOrEsc = str_replace("'", "''", $numOr);

        $statement = "SELECT
                    slor_desi as designation,
                    CASE
                        WHEN slor_typlig = 'P'
                            THEN (slor_qterel + slor_qterea + slor_qteres + slor_qtewait - slor_qrec)
                        WHEN slor_typlig IN ('F','M','U','C')
                            THEN slor_qterea
                        ELSE 0
                    END AS quantite,
                    slor_pxnreel as prix_unitaire
                FROM {$this->dbIps}.sav_lor
                WHERE slor_numor = '{$numOrEsc}'
                     ";

        $result = $this->connect->executeQuery($statement);
        $rows   = $this->convertirEnUtf8($this->connect->fetchResults($result));

        $lignes = [];
        foreach ($rows as $row) {
            $qte = (float) ($row['quantite'] ?? $row['QUANTITE'] ?? 0);
            $pu  = (float) ($row['prix_unitaire'] ?? $row['prix_unitaire'] ?? 0);

            $lignes[] = [
                'quantity'    => (string) $qte,
                'reference'   => (string) ($row['designation']  ?? $row['designation']  ?? ''),
                'description' => (string) ($row['designation'] ?? $row['designation'] ?? ''),
                'unitPrice'   => $pu,
                'netAmount'   => $qte * $pu,
            ];
        }

        return $lignes;
    }

    public function getLangueIps(?string $numcli, string $codeSociete): ?string
    {
        if ($numcli === null) {
            return null;
        }
        $statement = " SELECT cbse_langue as langue
                        FROM Informix.cli_bse 
                        INNER JOIN Informix.cli_soc 
                            ON cbse_numcli = csoc_numcli 
                            AND csoc_soc = '$codeSociete'
                        WHERE cbse_numcli='$numcli'
                    ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));
        $langue = $data[0]['langue'] ?? null;

        return $langue === null ? null : trim((string) $langue);
    }
}
