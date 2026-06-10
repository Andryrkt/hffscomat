<?php

namespace App\Model\Atelier\Dit\Soumission;

use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;

class DitRiSoumisAValidationModel extends Model
{
    /**
     * Récupération du numero OR 
     * -------------------------------
     * selon la numéro DIT donnée et la code société, 
     * on récupère le numéro OR dans la base de donnée sav_eor
     *
     * @param string $numDit
     * @param string $codeSociete
     * @return string|null
     */
    public function recupNumeroOr(string $numDit, string $codeSociete): ?string
    {
        $statement = " SELECT FIRST 1 
                seor_numor as numOr
                from {$this->dbIps}:Informix.sav_eor
                where seor_refdem like '%$numDit%'
                AND seor_serv = 'SAV'
                AND seor_soc = '$codeSociete'
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data[0]['numor'] ?? null;
    }

    /**
     * Recupération des interventions déjà soumis 
     * ---------------------------------------------
     * selon le numéro OR et la code de société ,
     * on peut récupérer les numero ITV dans la table ri_soumis_a_validation
     *
     * @param ?string $numOr
     * @param string $codeSociete
     * @return array
     */
    public function findItvDejaSoumis(?string $numOr, string $codeSociete): array
    {
        if (!$numOr) return [];

        $statement = "SELECT DISTINCT numeroitv AS numeroItv
            FROM {$this->dbIrium}:Informix.ri_soumis_a_validation
            WHERE numero_oR = '$numOr' AND code_societe = '$codeSociete'
            ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));
        return array_column($data, 'numeroItv');
    }

    /**
     * Récupération des numéro d'intervention à afficher
     * ----------------------------------------
     * selon le numero, les intervention déjà soumis et le code société donnée,
     * on récupère les numéro d'intervention qui n'est pas encore soumis
     * et le commentaire dans la table sav_itv 
     *
     * @param ?string $numOr
     * @param array $itvDejaSoumis
     * @param string $codeSociete
     * @return array
     */
    public function recupInterventionOr(?string $numOr, array $itvDejaSoumis, string $codeSociete): array
    {
        if (!$numOr) return [];

        $selectWhereCondition = new SelectWhereCondition();
        $condition = " {$selectWhereCondition->ni('sitv_interv',$itvDejaSoumis)}";

        $statement = "SELECT 
                    sitv_interv as numeroItv, 
                    trim(sitv_comment) as commentaire
            from {$this->dbIps}:Informix.sav_itv
            where sitv_numor = '$numOr' 
            and sitv_soc = '$codeSociete'
            $condition
            group by 1,2
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data;
    }
}
