<?php

namespace App\Model\magasin\Ors\Livrer;

use App\Model\Model;
use App\Model\Informix\SelectWhereCondition;
use App\Constants\Magasin\MagasinOrConstant;
use App\Dto\Magasin\Ors\Livrer\OrLivrerSearchDto;
use App\Dto\Magasin\Ors\Livrer\MaterielALivrerDto;
use App\Factory\magasin\Ors\Livrer\MaterielALivrerFactory;

class OrLivrerModel extends Model
{
    /**
     * Retourne la liste des lignes de matériel des OR validés à livrer, selon les critères de recherche
     *
     * @param OrLivrerSearchDto $dtoSearch critères de recherche
     *
     * @return array<MaterielALivrerDto>
     */
    public function recupereListeMaterielValider(OrLivrerSearchDto $dtoSearch): array
    {
        $selectCond = new SelectWhereCondition();

        $conditions = "
            {$selectCond->like('lor.slor_desi',$dtoSearch->designation)}
            {$selectCond->like('eor.seor_refdem',$dtoSearch->numDit)}
            {$selectCond->eq('lor.slor_numor',$dtoSearch->numOr)}
            {$selectCond->like('lor.slor_refp',$dtoSearch->referencePiece)}
            {$selectCond->between('lor.slor_datec',$dtoSearch->dateDebut,$dtoSearch->dateFin)}
            {$selectCond->eq('urg.description',$dtoSearch->niveauUrgence)}
            {$selectCond->eq('lor.slor_succdeb', trim(explode('-',$dtoSearch->agence)[0]))}
            {$selectCond->eq('lor.slor_servdeb', trim(explode('-',$dtoSearch->service)[0]))}
            {$selectCond->eq('lor.slor_succdeb', trim(explode('-',$dtoSearch->agenceUser)[0]))}
        ";

        if ($dtoSearch->orCompletude !== MagasinOrConstant::TOUS) $conditions .= $selectCond->eq('sit.situation', $dtoSearch->orCompletude);

        $statement = "--sql
        WITH
            valid_or AS ({$this->getQueryOrValide()}),
            const_st AS ({$this->getQueryConstructeurST()})
        SELECT
            TRIM(eor.seor_refdem) AS reference_dit,
            eor.seor_numor AS numero_or,
            COALESCE(pln.date_planning_ska, DATE(itv.sitv_datepla)) AS date_planning,
            urg.description AS niveau_urgence,
            eor.seor_dateor AS date_creation,
            eor.seor_succ AS agence_crediteur,
            eor.seor_servcrt AS service_crediteur,
            itv.sitv_succdeb AS agence_debiteur,
            itv.sitv_servdeb AS service_debiteur,
            itv.sitv_interv AS numero_intervention,
            lor.slor_nolign AS numero_ligne,
            lor.slor_constp AS constructeur,
            TRIM(lor.slor_refp) AS reference_piece,
            TRIM(lor.slor_desi) AS designation,
            SUM(
                CASE
                    WHEN lor.slor_typlig = 'P' THEN (lor.slor_qterel + lor.slor_qterea + lor.slor_qteres + lor.slor_qtewait - lor.slor_qrec)
                    WHEN lor.slor_typlig IN ('F','M','U','C') THEN lor.slor_qterea
                END
            ) AS quantite_demandee,
            SUM(lor.slor_qteres) AS quantite_a_livrer,
            SUM(lor.slor_qterea) AS quantite_livree,
            TRIM(tab.atab_lib) AS nom_prenom,
            TRIM(sit.situation) AS situation,
            eor.seor_usr AS id_user,
            TRIM(usr.ausr_nom) AS nom_utilisateur,
            mat.mmat_nummat AS id_materiel,
            TRIM(mat.mmat_numserie) AS numero_serie,
            TRIM(mat.mmat_recalph) AS numero_parc ,
            TRIM(mat.mmat_marqmat) AS marque,
            TRIM(mat.mmat_numparc) AS casier,
            mat.mmat_numcdec AS numero_commande
        FROM {$this->dbIps}.sav_lor AS lor
        INNER JOIN {$this->dbIps}.sav_eor AS eor ON eor.seor_numor = lor.slor_numor AND eor.seor_soc = lor.slor_soc AND eor.seor_succ = lor.slor_succ
        INNER JOIN {$this->dbIps}.mat_mat AS mat ON mat.mmat_nummat = eor.seor_nummat
        INNER JOIN {$this->dbIps}.agr_usr AS usr ON usr.ausr_num = eor.seor_usr
        INNER JOIN {$this->dbIps}.agr_tab AS tab ON tab.atab_nom = 'OPE' AND tab.atab_code = usr.ausr_ope
        INNER JOIN {$this->dbIps}.sav_itv AS itv
            ON itv.sitv_soc = lor.slor_soc
            AND itv.sitv_succ = lor.slor_succ
            AND itv.sitv_numor = lor.slor_numor
            AND itv.sitv_interv = TRUNC(lor.slor_nogrp/100)
            AND itv.sitv_numor || '-' || itv.sitv_interv IN (SELECT numero_or_itv FROM valid_or)
        INNER JOIN (
            SELECT 
                lorQte.slor_numor AS numero_or, 
                SUM(lorQte.slor_qteres) AS total_qteres
            FROM {$this->dbIps}.sav_lor lorQte
            WHERE lorQte.slor_numor IN (SELECT numero_or FROM valid_or)
                AND lorQte.slor_constp IN (SELECT abse_constp FROM const_st)
                AND lorQte.slor_refp NOT LIKE '%-L' AND lorQte.slor_refp NOT LIKE '%-CTRL'
            GROUP BY lorQte.slor_numor
        ) AS qte ON qte.numero_or = lor.slor_numor AND qte.total_qteres > 0
        INNER JOIN (
            SELECT
                lorSit.slor_numor AS numero_or,
                TRUNC(lorSit.slor_nogrp/100) AS num_groupe,
                CASE
                    WHEN SUM(lorSit.slor_qteres) > 0 
                        AND SUM(
                            CASE
                                WHEN lorSit.slor_typlig = 'P' THEN (lorSit.slor_qterel + lorSit.slor_qterea + lorSit.slor_qteres + lorSit.slor_qtewait - lorSit.slor_qrec)
                                WHEN lorSit.slor_typlig IN ('F','M','U','C') THEN lorSit.slor_qterea
                            END
                        ) = SUM(lorSit.slor_qteres + lorSit.slor_qterea)
                        THEN '" . MagasinOrConstant::COMPLET . "'  --  somme_qte_dispo > 0 AND somme_qte_dem = (somme_qte_dispo + somme_qte_livree)
                    WHEN SUM(lorSit.slor_qteres) > 0 
                        AND SUM(
                            CASE
                                WHEN lorSit.slor_typlig = 'P' THEN (lorSit.slor_qterel + lorSit.slor_qterea + lorSit.slor_qteres + lorSit.slor_qtewait - lorSit.slor_qrec)
                                WHEN lorSit.slor_typlig IN ('F','M','U','C') THEN lorSit.slor_qterea
                            END
                        ) > SUM(lorSit.slor_qteres + lorSit.slor_qterea)
                        THEN '" . MagasinOrConstant::INCOMPLET . "' -- somme_qte_dispo > 0 AND somme_qte_dem > (somme_qte_dispo + somme_qte_livree)
                END AS situation
            FROM {$this->dbIps}.sav_lor lorSit
            WHERE lorSit.slor_numor IN (SELECT numero_or FROM valid_or)
                AND lorSit.slor_constp IN (SELECT abse_constp FROM const_st)
                AND lorSit.slor_refp NOT LIKE '%-L' AND lorSit.slor_refp NOT LIKE '%-CTRL'
            GROUP BY lorSit.slor_numor, TRUNC(lorSit.slor_nogrp/100)
        ) AS sit ON sit.numero_or = lor.slor_numor AND sit.num_groupe = TRUNC(lor.slor_nogrp/100)
        LEFT JOIN (
            SELECT
                skw.ofh_id AS num_or_planning,
                skw.ofs_id AS num_interv_planning,
                DATE(MIN(ska.ska_d_start)) AS date_planning_ska
            FROM {$this->dbIps}.ska ska
            INNER JOIN {$this->dbIps}.skw skw ON skw.skw_id = ska.skw_id
            WHERE skw.ofh_id IN (SELECT numero_or FROM valid_or)
            GROUP BY skw.ofh_id, skw.ofs_id
        ) AS pln ON pln.num_or_planning = itv.sitv_numor AND pln.num_interv_planning = itv.sitv_interv
        LEFT JOIN {$this->dbIrium}.demande_intervention di ON di.numero_or = eor.seor_numor
        LEFT JOIN {$this->dbIrium}.wor_niveau_urgence urg ON urg.id = di.id_niveau_urgence
        WHERE eor.seor_typeor NOT IN('950', '501')
            AND sit.situation IS NOT NULL
            AND lor.slor_constp IN (SELECT abse_constp FROM const_st)
            AND (lor.slor_refp NOT LIKE '%-L' AND lor.slor_refp NOT LIKE '%-CTRL')
            $conditions
        GROUP BY 1,2,3,4,5,6,7,8,9,10,11,12,13,14,18,19,20,21,22,23,24,25,26,27
        ORDER BY eor.seor_numor ASC, itv.sitv_interv ASC, lor.slor_nolign ASC;
        ";

        $result = $this->connect->executeQuery($statement);

        $data = $this->convertirEnUtf8($this->connect->fetchResults($result));

        $factory = new MaterielALivrerFactory();

        return array_map(fn(array $ligne) => $factory->hydrate($ligne), $data);
    }

    private function getQueryOrValide(): string
    {
        return "SELECT DISTINCT
                    osv.numeroor AS numero_or,
                    osv.numeroor || '-' || osv.numeroitv AS numero_or_itv
                FROM {$this->dbIrium}.ors_soumis_a_validation osv
                WHERE osv.statut LIKE 'Valid%'
                    AND osv.numeroversion = (
                        SELECT MAX(osv2.numeroversion)
                        FROM {$this->dbIrium}.ors_soumis_a_validation osv2
                        WHERE osv2.id = osv.id
                    )";
    }

    private function getQueryConstructeurST(): string
    {
        return "SELECT DISTINCT abse_constp
                FROM {$this->dbIps}.art_bse
                WHERE abse_codg = 'ST'";
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
