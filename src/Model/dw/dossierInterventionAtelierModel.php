<?php

namespace App\Model\dw;

use App\Controller\Traits\ConversionTrait;
use App\Dto\Atelier\Dit\DossierDit\DossierInterventionAtelierSearchDto;
use App\Model\Informix\SelectWhereCondition;
use App\Model\Model;

class dossierInterventionAtelierModel extends Model
{
    use ConversionTrait;
    private SelectWhereCondition $selectCond;

    public function __construct()
    {
        parent::__construct();
        $this->selectCond = new SelectWhereCondition();
    }

    private function getCountQueryWithDit(string $table, ?string $colonne = null): string
    {
        $colonnes = $colonne ? "numero_dit, $colonne" : "numero_dit";
        return "SELECT $colonnes, COUNT(*) as n from {$this->dbIrium}:Informix.$table GROUP BY $colonnes";
    }

    private function getCountQueryWithOr(string $table): string
    {
        return "SELECT numero_or, COUNT(*) as n from {$this->dbIrium}:Informix.$table GROUP BY numero_or";
    }

    public function findAllDwDit(DossierInterventionAtelierSearchDto $dto): array
    {
        $conditions = "
            {$this->selectCond->like('dit.numero_dit',$dto->numDit)}
            {$this->selectCond->like('cnt_or.numero_or',$dto->numOr)}
            {$this->selectCond->like('cnt_dd.numero_devis',$dto->numDev)}
            {$this->selectCond->like('dit.designation_materiel',$dto->designation)}
            {$this->selectCond->like('dit.id_materiel',$dto->idMateriel)}
            {$this->selectCond->like('dit.numero_parc',$dto->numParc)}
            {$this->selectCond->like('dit.numero_serie',$dto->numSerie)}
            {$this->selectCond->like('dit.type_reparation',$dto->typeIntervention)}
            {$this->selectCond->between('dit.date_creation',$dto->dateDebut,$dto->dateFin)}
        ";

        $statement = "WITH
                cnt_or  AS ({$this->getCountQueryWithDit('dw_ordre_de_reparation', 'numero_or')}),
                cnt_dd  AS ({$this->getCountQueryWithDit('dw_devis_ate', 'numero_devis')}),
                cnt_bcc AS ({$this->getCountQueryWithDit('dw_bc_client')}),
                cnt_cde AS ({$this->getCountQueryWithOr('dw_commande')}),
                cnt_ri  AS ({$this->getCountQueryWithOr('dw_rapport_intervention')}),
                cnt_fac AS ({$this->getCountQueryWithOr('dw_facture')})
            SELECT
                dit.numero_dit,
                cnt_or.numero_or,
                cnt_dd.numero_devis,
                dit.numero_parc,
                dit.numero_serie,
                dit.id_materiel,
                dit.date_creation,
                dit.designation_materiel,
                dit.type_reparation,
                1
                + COALESCE(cnt_or.n, 0)
                + COALESCE(cnt_dd.n, 0)
                + COALESCE(cnt_bcc.n, 0)
                + COALESCE(cnt_cde.n, 0)
                + COALESCE(cnt_ri.n, 0)
                + COALESCE(cnt_fac.n, 0)
                AS nb_docs
            FROM {$this->dbIrium}:Informix.dw_demande_intervention dit
                LEFT JOIN cnt_or  ON cnt_or.numero_dit  = dit.numero_dit
                LEFT JOIN cnt_dd  ON cnt_dd.numero_dit  = dit.numero_dit
                LEFT JOIN cnt_bcc ON cnt_bcc.numero_dit = dit.numero_dit
                LEFT JOIN cnt_cde ON cnt_cde.numero_or  = cnt_or.numero_or
                LEFT JOIN cnt_ri  ON cnt_ri.numero_or   = cnt_or.numero_or
                LEFT JOIN cnt_fac ON cnt_fac.numero_or  = cnt_or.numero_or
            WHERE 1=1
            $conditions
            ORDER BY dit.date_creation DESC
            ;";

        $result = $this->connect->executeQuery($statement);

        return $this->connect->fetchResults($result);
    }

    public function findDwDit($numDit)
    {
        $sql = " SELECT 
        -- DEMANDE D'INTERVENTION
        dit.numero_dit AS numero_doc,
        dit.date_creation AS date_creation,
        dit.date_derniere_modification AS date_modification,
        dit.extension_fichier As extension_fichier,
        dit.total_page AS total_page,
        dit.taille_fichier AS taille_fichier,
        dit.path AS chemin

        FROM DW_Demande_Intervention dit
        WHERE dit.numero_dit = '" . $numDit . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }

        return $this->ConvertirEnUtf_8($tab);
    }

    public function findDwOr($numDit)
    {
        $sql = " SELECT 
        --ORDRE DE REPARATION
        ord.numero_or AS numero_doc,
        ord.date_creation AS date_creation,
        ord.date_derniere_modification AS date_modification,
        ord.extension_fichier As extension_fichier,
        ord.total_page AS total_page,
        ord.taille_fichier AS taille_fichier,
        ord.path AS chemin,
        ord.numero_version AS numero_version,
        ord.statut_or AS statut_or

        FROM DW_Ordre_De_Reparation ord
        WHERE ord.numero_dit = '" . $numDit . "'
        ORDER BY ord.numero_version ASC
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findDwFac($numOr)
    {
        $sql = " SELECT 
        --FACTURE
        fac.numero_fac AS numero_doc,
        fac.date_creation AS date_creation,
        fac.date_derniere_modification AS date_modification,
        fac.extension_fichier As extension_fichier,
        fac.total_page AS total_page,
        fac.taille_fichier AS taille_fichier,
        fac.path AS chemin

        FROM DW_Facture fac
        WHERE fac.numero_or = '" . $numOr . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findDwRi($numOr)
    {
        $sql = " SELECT 
            --RAPORT D'INTERVENTION
            ri.numero_ri AS numero_doc,
            ri.date_creation AS date_creation,
            ri.date_derniere_modification AS date_modification,
            ri.extension_fichier As extension_fichier,
            ri.total_page AS total_page,
            ri.taille_fichier AS taille_fichier,
            ri.path AS chemin

            FROM DW_Rapport_Intervention ri
            WHERE ri.numero_or = '" . $numOr . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findDwCde($numOr)
    {
        $sql = " SELECT 
            --COMMANDE
            cde.numero_cde AS numero_doc,
            cde.date_creation AS date_creation,
            cde.date_derniere_modification AS date_modification,
            cde.extension_fichier As extension_fichier,
            cde.total_page AS total_page,
            cde.taille_fichier AS taille_fichier,
            cde.path AS chemin

            FROM DW_Commande cde
            WHERE cde.numero_or = '" . $numOr . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findDwBc($numDit)
    {
        $sql = " SELECT 
            --BON DE COMMANDE CLIENT 
            bcc.numero_bc AS numero_doc,
            bcc.date_creation AS date_creation,
            bcc.date_derniere_modification AS date_modification,
            bcc.extension_fichier As extension_fichier,
            bcc.total_page AS total_page,
            bcc.taille_fichier AS taille_fichier,
            bcc.path AS chemin

            FROM DW_BC_Client bcc
            WHERE bcc.numero_dit = '" . $numDit . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findDwDev($numDit)
    {
        $sql = " SELECT 
            --DEVIS 
            dev.numero_devis AS numero_doc,
            dev.date_creation AS date_creation,
            dev.date_derniere_modification AS date_modification,
            dev.extension_fichier As extension_fichier,
            dev.total_page AS total_page,
            dev.taille_fichier AS taille_fichier,
            dev.path AS chemin

            FROM DW_Devis dev
            WHERE dev.numero_dit = '" . $numDit . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }


    public function findCheminDit($numDoc)
    {
        $sql = " SELECT DISTINCT 
        dit.path AS chemin

        FROM DW_Demande_Intervention dit
        WHERE dit.numero_dit = '" . $numDoc . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }

        return $this->ConvertirEnUtf_8($tab);
    }

    public function findCheminOr($numDoc, $numVersion)
    {
        $sql = " SELECT DISTINCT 
        ord.path AS chemin

        FROM DW_Ordre_De_Reparation ord
        WHERE ord.numero_or = '" . $numDoc . "'
        AND ord.numero_version = '" . $numVersion . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findCheminFac($numDoc)
    {
        $sql = " SELECT DISTINCT 
        --FACTURE
        fac.path AS chemin

        FROM DW_Facture fac
        WHERE fac.numero_fac = '" . $numDoc . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findCheminRi($numDoc)
    {
        $sql = " SELECT DISTINCT 
            --RAPORT D'INTERVENTION
            ri.path AS chemin

            FROM DW_Rapport_Intervention ri
            WHERE ri.numero_ri = '" . $numDoc . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }

    public function findCheminCde($numDoc)
    {
        $sql = " SELECT DISTINCT 
            --COMMANDE
            cde.path AS chemin

            FROM DW_Commande cde
            WHERE cde.numero_cde = '" . $numDoc . "'
        ";

        $exec = $this->connexion->query($sql);
        $tab = [];
        while ($result = odbc_fetch_array($exec)) {
            $tab[] = $result;
        }
        return $this->ConvertirEnUtf_8($tab);
    }
}
