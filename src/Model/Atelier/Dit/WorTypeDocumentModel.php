<?php


namespace App\Model\Atelier\Dit;

use App\Model\Model;

class WorTypeDocumentModel extends Model
{
    public function getDescription(): array
    {
        $statement = " SELECT  description as description 
                    FROM {$this->dbIrium}.wor_type_document 
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows;
    }

    public function getIdSelonDescription(string $description): int
    {
        $statement = " SELECT  id as id
                    FROM {$this->dbIrium}.wor_type_document 
                    where description = '$description'
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'id');

        return $rows[0] ?? 0;
    }
    public function getDescriptionById($id): string
    {
        $statement = " SELECT  description as description 
                    FROM {$this->dbIrium}.wor_type_document  
                    WHERE id ='$id'
                    ";
        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows[0] ?? "";
    }
}
