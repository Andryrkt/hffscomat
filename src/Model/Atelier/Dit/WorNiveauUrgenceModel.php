<?php

namespace App\Model\Atelier\Dit;

use App\Model\Model;


class WorNiveauUrgenceModel extends Model
{
    public function getDescription(): array
    {
        $statement = " SELECT  description as description 
                    FROM {$this->dbIrium}:Informix.wor_niveau_urgence 
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows;
    }

    public function getP2Description(): ?string
    {
        $statement = " SELECT  description as description
                    FROM {$this->dbIrium}:Informix.wor_niveau_urgence
                    WHERE description  = 'P2'
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows[0] ?? null;
    }

    public function getIdSelonDescription(string $description): int
    {
        $statement = " SELECT  id as id
                    FROM {$this->dbIrium}:Informix.wor_niveau_urgence
                    WHERE description  = '$description'
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'id');

        return $rows[0] ?? 0;
    }
}
