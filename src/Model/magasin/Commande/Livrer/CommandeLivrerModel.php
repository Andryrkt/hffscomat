<?php

namespace App\Model\magasin\Commande\Livrer;

use App\Dto\Magasin\Commande\Livrer\CommandeLivrerSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;

class CommandeLivrerModel extends Model
{
    public function recupereListeCommandeLivrer(CommandeLivrerSearchDto $dtoSearch): array
    {
        dd($dtoSearch->service);
        $selectWhereCondition = new SelectWhereCondition();
        $conditions = "
            {$selectWhereCondition->like('nent_numcde',$dtoSearch->numCommande)}
            {$selectWhereCondition->like('nent_libcde',$dtoSearch->numDevis)}
            {$selectWhereCondition->like('nent_soc',$dtoSearch->codeClient)}
            {$selectWhereCondition->in('nent_servcrt',$dtoSearch->service)}
            {$selectWhereCondition->like('nlig_refp',$dtoSearch->referencePiece)}
            {$selectWhereCondition->like('nlig_constp',$dtoSearch->constructeur)}
            {$selectWhereCondition->between('nent_datecde',$dtoSearch->dateDebut,$dtoSearch->dateFin)}
        ";

        $statement = " SELECT
    nent_numcde as commande,
    nent_libcde as libelle,
    nent_datecde as date,
    nent_datexp as datefin,
    nent_datexp - nent_datecde as nbr_jour_dispo,
    nent_servcrt as service,
    nent_numcli || ' - ' || nent_nomcli as client,
    nlig_nolign as ligne,
    nlig_constp as constructeur,
    nlig_refp as referencePiece,
    nlig_desi as designation,
    nlig_qtewait as quantite,
    nlig_qtedisp as quantiteDispo
FROM
    neg_ent
    inner join neg_lig on nlig_soc = nent_soc
    and nlig_succ = nent_succ
    and nlig_numcde = nent_numcde
WHERE
    nent_natop = 'DIR'
    and nlig_qtewait > 0
    and nlig_datealloc is NULL

    $conditions
    ;
 ";
        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function agence(string $codeSociete)
    {
        $statement = "  SELECT DISTINCT
                            slor_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) as agence
                        FROM {$this->dbIps}.sav_lor
                        WHERE slor_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) <> ''
                        AND slor_soc = '$codeSociete'
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'agence');
    }

    public function service(?string $agence)
    {
        if ($agence === null) {
            return []; // Si aucune agence, retourner un tableau vide
        }

        // Reverted to string concatenation as executeQuery might not support parameters
        $statement = " SELECT DISTINCT
                            slor_servdeb||'-'||(select trim(atab_lib) from agr_tab where atab_nom = 'SER' and atab_code = slor_servdeb) as service
                        FROM sav_lor
                        WHERE slor_servdeb||'-'||(select trim(atab_lib) from agr_tab where atab_nom = 'SER' and atab_code = slor_servdeb) <> ''
                        AND slor_soc = 'HF'
                        AND slor_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) = '$agence'
            ";


        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        $dataUtf8 = $this->convertirEnUtf8($data);


        return array_map(function ($item) {
            return [
                "value" => $item['service'],
                "text"  => $item['service']
            ];
        }, $dataUtf8);
    }

    public function agenceUser(string $codeAgence, string $codeSociete)
    {
        $statement = "  SELECT DISTINCT
                            slor_succdeb||'-'||(select trim(asuc_lib) from informix.agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) as agence
                        FROM {$this->dbIps}.sav_lor
                        WHERE slor_succdeb||'-'||(select trim(asuc_lib) from informix.agr_succ where asuc_numsoc = slor_soc and asuc_num = slor_succdeb) <> ''
                        AND slor_soc = '$codeSociete'
                    ";

        if ($codeAgence <> "''") {
            $statement .= " AND slor_succdeb IN ($codeAgence) ";
        }

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'agence');
    }
}
