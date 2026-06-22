<?php

namespace App\Model\Atelier\Dit;

use App\Model\Model;


class WorNiveauUrgenceModel extends Model
{
    /**
     * Recupère tous les description du niveau d'urgence
     *
     * @return array
     */
    public function getDescription(): array
    {
        $statement = " SELECT  description as description 
                    FROM {$this->dbIrium}:Informix.wor_niveau_urgence
                    ORDER BY description DESC
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows;
    }
    
    /**
     * Recupère le description delon l'ID
     *
     * @param integer $id
     * @return string
     */
    public function getDescriptionById(int $id): string
    {
        $statement = " SELECT  description as description
                    FROM {$this->dbIrium}:Informix.wor_niveau_urgence
                    WHERE id  = '$id'
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows[0] ?? "";
    }

    /**
     * Recupère le niveau d'urgence 'P2'
     *
     * @return string|null
     */
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

    /**
     * Recupère l'ID selon la description
     *
     * @param string $description
     * @return integer
     */
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
