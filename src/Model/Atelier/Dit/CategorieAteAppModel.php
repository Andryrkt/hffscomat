<?php


namespace App\Model\Atelier\Dit;

use App\Model\Model;

class CategorieAteAppModel extends Model
{
    public function getDescription(): array
    {
        $statement = " SELECT  libelle_categorie_ate_app as description 
                    FROM {$this->dbIrium}:Informix.categorie_ate_app 
                    ";

        $result = $this->connect->executeQuery($statement);
        $rows = array_column($this->connect->fetchResults($result), 'description');

        return $rows;
    }
}
