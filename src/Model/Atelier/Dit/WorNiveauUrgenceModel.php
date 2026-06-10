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
    public function getDescriptionById($id): string
    {
        $statement = " SELECT  description as description
                    FROM {$this->dbIrium}:Informix.wor_niveau_urgence
                    WHERE id  = '$id'
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows[0] ?? "";
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
}
