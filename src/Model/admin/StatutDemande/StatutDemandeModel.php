<?php

namespace App\Model\admin\StatutDemande;

use App\Model\Model;

class StatutDemandeModel extends Model
{
    public function getAllDescriptionStatutDit()
    {
        $statement = " SELECT description as description
                from {$this->dbIrium}:informix.statut_demande 
                where code_application ='DIT' 
                order by id_statut_demande  asc
        ";

        $result = $this->connect->executeQuery($statement);
        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        return array_column($data, 'description');
    }
    public function getDescriptionById($id): string
    {
        $statement = " SELECT description as description
                from {$this->dbIrium}:informix.statut_demande 
                where id_statut_demande ='$id' 
               
        ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');;

        return $rows[0] ?? "";
    }
}
