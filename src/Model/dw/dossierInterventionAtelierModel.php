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

    /** 
     * Fonction pour récupérer tous les DwDemandeIntervention avec le nombre de document associé
     * @param DossierInterventionAtelierSearchDto $dto
     * @return array
     */
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

        return $this->connect->fetchResults($result, "Windows-1252, UTF-8, ASCII");
    }

    private function getTypeDoc(string $table): string
    {
        $typeDocMap = [
            "dw_demande_intervention" => "Demande d''intervention",
            "dw_ordre_de_reparation"  => "Ordre de réparation",
            "dw_bc_client"            => "Bon de Commande Client",
            "dw_devis_ate"            => "Devis",
            "dw_commande"             => "Commande",
            "dw_rapport_intervention" => "Rapport d''intervention",
            "dw_facture"              => "Facture",
        ];

        return $typeDocMap[$table] ?? '';
    }

    private function getColumn(string $table): string
    {
        $columnMap = [
            "dw_demande_intervention" => "numero_dit",
            "dw_ordre_de_reparation"  => "numero_or",
            "dw_bc_client"            => "numero_bc",
            "dw_devis_ate"            => "numero_devis",
            "dw_commande"             => "numero_cde",
            "dw_rapport_intervention" => "numero_ri",
            "dw_facture"              => "numero_fac",
        ];

        return $columnMap[$table] ?? '';
    }

    private function getQueryRef(string $numDit): string
    {
        return "SELECT dit.numero_dit, ord.numero_or
            FROM {$this->dbIrium}:Informix.dw_demande_intervention dit
            LEFT JOIN {$this->dbIrium}:Informix.dw_ordre_de_reparation ord
                ON ord.numero_dit = dit.numero_dit
            WHERE dit.numero_dit = '$numDit'";
    }

    private function getQueryDoc(string $table, string $alias, bool $withVersion = true): string
    {
        $typeDoc = $this->getTypeDoc($table);
        $column = $this->getColumn($table);
        $version = $withVersion ? "$alias.numero_version" : 0;

        return "SELECT
            TRIM('$typeDoc') AS nom_doc,
            {$alias}.{$column} AS numero_doc,
            {$alias}.date_creation,
            {$alias}.date_derniere_modification,
            {$version} AS numero_version,
            {$alias}.total_page,
            {$alias}.taille_fichier,
            {$alias}.extension_fichier,
            {$alias}.path AS chemin
        FROM {$this->dbIrium}:Informix.$table {$alias}
        ";
    }

    /** 
     * Fonction pour récupérer tous les documents liés à un DwDemandeIntervention 
     * @param string $numDit
     * @return array
     */
    public function findAllDwDocs(string $numDit): array
    {
        $statement =
            "WITH
            ref AS ({$this->getQueryRef($numDit)}),
            docs AS
            (
                {$this->getQueryDoc('dw_demande_intervention', 'dit', false)}
                WHERE dit.numero_dit = '$numDit'
            UNION ALL
                {$this->getQueryDoc('dw_ordre_de_reparation', 'ord')}
                INNER JOIN ref r
                    ON ord.numero_dit = r.numero_dit
            UNION ALL
                {$this->getQueryDoc('dw_bc_client', 'bcc')}
                INNER JOIN ref r
                    ON bcc.numero_dit = r.numero_dit
            UNION ALL
                {$this->getQueryDoc('dw_devis_ate', 'dev')}
                INNER JOIN ref r
                    ON dev.numero_dit = r.numero_dit
            UNION ALL
                {$this->getQueryDoc('dw_commande', 'cde', false)}
                INNER JOIN ref r
                    ON cde.numero_or = r.numero_or
            UNION ALL
                {$this->getQueryDoc('dw_rapport_intervention', 'ri', false)}
                INNER JOIN ref r
                    ON ri.numero_or = r.numero_or
            UNION ALL
                {$this->getQueryDoc('dw_facture', 'fac', false)}
                INNER JOIN ref r
                    ON fac.numero_or = r.numero_or
            )
            SELECT *
            FROM docs;
        ";

        $result = $this->connect->executeQuery($statement);

        return $this->connect->fetchResults($result, "Windows-1252, UTF-8, ASCII");
    }
}
