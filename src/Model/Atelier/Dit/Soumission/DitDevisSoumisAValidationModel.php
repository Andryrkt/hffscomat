<?php

namespace App\Model\Atelier\Dit\Soumission;

use App\Model\Model;

class DitDevisSoumisAValidationModel extends Model
{
    public function findStatutDevis(string $numDit, string $codeSociete)
    {
        $statement = "SELECT FIRST 1 d.statut AS statut
                        FROM {$this->dbIrium}:Informix.devis_soumis_a_validation d
                        WHERE d.numerodit = '$numDit'
                        AND d.code_societe = '$codeSociete'
                        ORDER BY d.numeroversion DESC
            ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return $data;
    }
}
