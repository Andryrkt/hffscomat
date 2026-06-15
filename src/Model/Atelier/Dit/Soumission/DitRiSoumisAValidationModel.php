<?php

namespace App\Model\Atelier\Dit\Soumission;

use App\Model\Informix\InsertQueryBuilder;
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


    /**
     * Récupération du numero soumission et incrementation de cette numéro obtenu
     * ---------------------------------------------------------------------------
     * selon le numero OR et code société donnée on recupère le numéro de soumission 
     * dans la table ri_soumis_a_validation puis on l'increment
     *
     * @param string $numOr
     * @param string $codeSociete
     * @return integer
     */
    public function recupNumeroSoumission(string $numOr, string $codeSociete): int
    {
        $statement = "SELECT COALESCE(MAX(numero_soumission)+1, 1) AS numSoumissionEncours
                FROM {$this->dbIrium}:Informix.ri_soumis_a_validation
                WHERE numero_or = '$numOr' and code_societe = '$codeSociete'";

        $result = $this->connect->executeQuery($statement);

        $data = array_column($this->convertirEnUtf8($this->connect->fetchResults($result)), 'numSoumissionEncours');

        return $data[0] ?? 1;
    }

    /**
     * Methode pour enregistrer les données du formulaire soumission RI
     *  dans la base de donnée ri_soumis_a_validation
     *
     * @return void
     */
    public function enregistrementRi(array $datas): void
    {
        $this->connect->connect();
        try {
            foreach ($datas as $donnees) {
                $builder = new InsertQueryBuilder("{$this->dbIrium}:Informix.ri_soumis_a_validation");
                $builder->setData($donnees);
                $result = $builder->build();
                $this->connect->executeQuery($result['sql'], $result['params']);
            }
        } finally {
            $this->connect->close();
        }
    }

    /**
     * Recupère tous les numéro d'intervention pour l'OR
     * ---------------------------------------------------
     * 
     *
     * @param string $numOr
     * @param string $codeSociete
     * @return array
     */
    public function recupToutNumeroItv(string $numOr, string $codeSociete): array
    {
        $statement = "SELECT numeroItv 
        from {$this->dbIrium}:Informix.ors_soumis_a_validation
        where numeroOR = '$numOr'
        and code_societe = '$codeSociete'
        and numeroVersion in (
                select max(numeroVersion) 
                from {$this->dbIrium}:Informix.ors_soumis_a_validation 
                where numeroOR = '$numOr' and code_societe = '$codeSociete'
                )
        ";
        $result = $this->connect->executeQuery($statement);

        $data = array_column($this->convertirEnUtf8($this->connect->fetchResults($result)), 'numeroItv');

        return $data;
    }

    /**
     * Recupère la derniere numero de soumission RI par numOr et code societe
     * ---------------------------------------------------
     * 
     *
     * @param string $numOr
     * @param string $codeSociete
     */
    public function findNumeroVersionMax(string $numOr, string $codeSociete): int
    {
        $statement = "
        SELECT numero_soumission AS numero_version_max
        FROM {$this->dbIrium}:Informix.ri_soumis_a_validation
        WHERE numero_or = '$numOr'
          AND code_societe = '$codeSociete'
    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return (int)($data[0]['numero_version_max'] ?? 0);
    }


    public function findNumItv($numOr, $codeSociete)
    {

        $numeroVersionMax = $this->findNumeroVersionMax($numOr, $codeSociete);

        // Étape 2 : Utiliser le numeroVersionMax pour récupérer le numero d'intervention
        $statement = "
        SELECT numeroItv AS numeroItv
        FROM {$this->dbIrium}:Informix.ri_soumis_a_validation
        WHERE numero_or = '$numOr' 
        AND numero_soumission = '$numeroVersionMax' 
          AND code_societe = '$codeSociete'
    ";
        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return (array)($data[0]['numeroItv'] ?? 0);
    }
}
