<?php

namespace App\Model\magasin\Commande\Traiter;

use App\Dto\Magasin\Commande\Traiter\CommandeTraiterSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;

class CommandeTraiterModel extends Model
{
    public function recupereListeCommandeTraiter(CommandeTraiterSearchDto $dtoSearch): array
    {
        $selectWhereCondition = new SelectWhereCondition();

        $conditions = "
            {$selectWhereCondition->like('nent_numcde',$dtoSearch->numCommande)}
            {$selectWhereCondition->like('nent_libcde',$dtoSearch->numDevis)}
            {$selectWhereCondition->like('nent_numcli',$dtoSearch->codeClient)}
            {$selectWhereCondition->like('nlig_refp',$dtoSearch->referencePiece)}
            {$selectWhereCondition->like('nlig_constp',$dtoSearch->constructeur)}
            {$selectWhereCondition->between('nent_datecde',$dtoSearch->dateDebut,$dtoSearch->dateFin)}
            {$selectWhereCondition->in('nent_servcrt',$dtoSearch->service)}
            {$selectWhereCondition->eq('nent_soc',$dtoSearch->codeSociete)}
            {$selectWhereCondition->likeAny('nent_libcde',$this->commandesValides("Valid"))}

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
    {$this->dbIps}.neg_ent
    INNER JOIN neg_lig ON nlig_soc = nent_soc
        AND nlig_succ = nent_succ
        AND nlig_numcde = nent_numcde
    INNER JOIN art_bse ON abse_constp = nlig_constp 
        AND abse_refp = nlig_refp
WHERE
    nent_natop = 'DIR'
    --and nlig_qtewait > 0
    AND nlig_datealloc is NULL
    --and nlig_typlig  = 'P'

    $conditions
    ;
 ";
        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return $this->convertirEnUtf8($data);
    }

    public function commandesValides(string $statut)
    {
        $statement = " SELECT q.numero_devis FROM {$this->dbIrium}.devis_soumis_a_validation_neg q WHERE q.statut_bc LIKE '$statut%' ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column(
            $this->convertirEnUtf8($data),
            'numero_devis'
        );
    }


    public function agence(string $codeSociete)
    {
        $statement = "  SELECT DISTINCT
                            nent_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = nent_soc and asuc_num = nent_succdeb) as agence
                        FROM {$this->dbIps}.neg_ent
                        WHERE nent_succdeb||'-'||(select trim(asuc_lib) from agr_succ where asuc_numsoc = nent_soc and asuc_num = nent_succdeb) <> ''
                        AND nent_soc = '$codeSociete'
                    ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        return array_column($this->convertirEnUtf8($data), 'agence');
    }

    public function service(string $codeSociete)
    {

        // Reverted to string concatenation as executeQuery might not support parameters
        $statement = "  SELECT DISTINCT nent_servcrt as service, atab_lib as description 
                         FROM {$this->dbIps}.neg_ent
                         INNER JOIN agr_tab ON atab_code = nent_servcrt AND atab_nom = 'SER'
                        WHERE nent_soc = '$codeSociete' ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->connect->fetchResults($result);

        $dataUtf8 = $this->convertirEnUtf8($data);


        return array_map(function ($item) {
            return [
                "value" => $item["service"],
                "text"  =>  $item["service"] . "- " . $item["description"]
            ];
        }, $dataUtf8);
    }
}
