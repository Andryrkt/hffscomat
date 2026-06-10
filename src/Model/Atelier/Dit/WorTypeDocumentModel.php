<?php


namespace App\Model\Atelier\Dit;

use App\Model\Model;

class WorTypeDocumentModel extends Model
{
    public function getDescription(): array
    {
        $statement = " SELECT  description as description 
                    FROM {$this->dbIrium}:Informix.wor_type_document 
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows;
    }
    public function getDescriptionById($id): string
    {
        $statement = " SELECT  description as description 
                    FROM {$this->dbIrium}:Informix.wor_type_document  
                    WHERE id ='$id'
                    ";
        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows[0] ?? "";
    }
}
