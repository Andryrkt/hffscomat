<?php

namespace App\Model\planning;

use App\Model\Model;
use App\Model\Traits\ConversionModel;

class ModalPlanningModel extends Model
{
  use ConversionModel;

  public function recupTechnicientIntervenant($numOr, $numItv)
  {
    $statement = " SELECT distinct 
        ssal_numsal AS matricule, 
        ssal_nom AS matriculeNomPrenom
        from skw
        inner join ska on ska.skw_id = skw.skw_id
        inner join sav_sal on sav_sal.ssal_numsal = ska.skr_id
        and ofs_id = '$numItv'
        where skw.ofh_id ='$numOr'
      ";

    $result = $this->connect->executeQuery($statement);

    $data = $this->connect->fetchResults($result);

    return $this->convertirEnUtf8($data);
  }

  public function recupTechnicien2($numOr, $numItv)
  {
    $statement = " SELECT
        ssal_numsal AS matricule, 
        ssal_nom AS matriculeNomPrenom 
        from sav_itv
        inner join sav_sal on sav_sal.ssal_numsal = sitv_techn
        where sitv_numor = '$numOr'
        and sitv_interv = '$numItv' 
        and ssal_numsal <> 9999
      ";

    $result = $this->connect->executeQuery($statement);

    $data = $this->connect->fetchResults($result);

    return $this->convertirEnUtf8($data);
  }
}
