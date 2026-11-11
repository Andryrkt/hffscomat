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
}
