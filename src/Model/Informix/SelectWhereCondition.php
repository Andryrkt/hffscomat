<?php

namespace App\Model\Informix;

class SelectWhereCondition
{
    public function eq(string $column, ?string $value): string
    {
        $value = $value ? trim($value) : null;
        if (!$value) return '';

        return "AND $column = '$value'";
    }

    public function ne(string $column, ?string $value): string
    {
        $value = $value ? trim($value) : null;
        if (!$value) return '';
        return "AND $column <> '$value'";
    }

    public function in(string $column, ?array $values, bool $withAnd = true): string
    {
        if (empty($values)) return '';
        return $this->createInConditionWithTemp($column, $values);
    }

    public function ni(string $column, array $values): string
    {
        if (empty($values)) return '';
        return $this->createInConditionWithTemp($column, $values, true);
    }

    public function like(string $column, ?string $value): string
    {
        $value = $value ? trim($value) : null;

        if (!$value) {
            return '';
        }

        $numericColumns = [
            'nent_numcli',
            'nent_numcde',

        ];

        if (in_array($column, $numericColumns, true)) {
            return "AND CAST($column AS CHAR(20)) LIKE '%$value%'";
        }

        return "AND $column LIKE '%$value%'";
    }

    /**
     * cette methode permet de faire le filtre like et not like selon l'option qu'on donne
     * 
     * Exemple d'utilisation:
     * // Contient "john"
     * $sql = $this->nlike('username', 'john');
     *
     * // Commence par "admin"
     * $sql = $this->nlike('username', 'admin', ['position' => 'starts']);
     * 
     * // Termine par ".fr"
     * $sql = $this->nlike('email', '.fr', ['position' => 'ends']);
     * 
     * // Recherche exacte
     * $sql = $this->nlike('code', 'ABC123', ['position' => 'exact']);
     * 
     * // Case sensitive
     * $sql = $this->nlike('password', 'Secret', [
     *     'position' => 'contains',
     *     'caseSensitive' => true
     * ]);
     * 
     * // NOT LIKE
     * $sql = $this->nlike('status', 'deleted', ['not' => true]);
     * 
     * // Avec table alias
     * $sql = $this->nlike('name', 'martin', [
     *     'tableAlias' => 'users',
     *     'position' => 'starts'
     * ]);
     * 
     * // Combinaison
     * $sql = $this->nlike('email', 'gmail', [
     *     'position' => 'ends',
     *     'not' => true,
     *     'caseSensitive' => false
     * ]);
     */
    public function nlike(string $column, ?string $value, array $options = []): string
    {
        $defaults = [
            'position' => 'contains',
            'caseSensitive' => false,
            'tableAlias' => '',
            'not' => false,
            'escape' => true
        ];

        $options = array_merge($defaults, $options);

        // Valider la position
        $validPositions = ['contains', 'starts', 'ends', 'exact'];
        if (!in_array($options['position'], $validPositions)) {
            $options['position'] = 'contains';
        }

        $value = $value ? trim($value) : null;
        if ($value === null || $value === '') {
            return '';
        }

        // Échapper la valeur
        $escapedValue = $options['escape'] ? addslashes($value) : $value;

        // Construire le nom de la colonne
        $columnName = $options['tableAlias']
            ? "{$options['tableAlias']}.$column"
            : "$column";

        // Construire l'opérateur
        $operator = $options['caseSensitive'] ? 'LIKE BINARY' : 'LIKE';
        if ($options['not']) {
            $operator = "NOT $operator";
        }

        // Construire le motif
        switch ($options['position']) {
            case 'starts':
                $pattern = "'$escapedValue%'";
                break;
            case 'ends':
                $pattern = "'%$escapedValue'";
                break;
            case 'exact':
                $pattern = "'$escapedValue'";
                break;
            case 'contains':
            default:
                $pattern = "'%$escapedValue%'";
                break;
        }

        return " AND $columnName $operator $pattern";
    }

    /**
     * Calcul de la condition BETWEEN pour les dates
     *
     * @param string $column
     * @param \DateTimeImmutable|\DateTime|null $d1
     * @param \DateTimeImmutable|\DateTime|null $d2
     * @return string
     */
    public function between(string $column, $d1 = null, $d2 = null): string
    {
        $condition = "";
        $d1 = $d1 ? trim($d1->format('Y-m-d')) : null;
        $d2 = $d2 ? trim($d2->format('Y-m-d')) : null;

        if ($d1) $condition .= "AND $column >= datetime($d1) year to day ";
        if ($d2) $condition .= "AND $column <= datetime($d2) year to day ";

        return $condition;
    }

    public function null(string $column, bool $value = false): string
    {
        if (!$value) return '';
        return "AND ($column IS NULL  OR $column <> '' )";
    }

    private function createInConditionWithTemp(string $column, array $values, bool $isNotIn = false): string
    {
        if (empty($values)) {
            return $isNotIn ? "" : " AND 1=0";
        }

        if (count($values) > 500) {
            $in = $isNotIn ? "NOT IN" : "IN";
            $escaped = array_map([$this, 'escapeString'], $values);
            $list = "'" . implode("','", $escaped) . "'";
            return "AND $column $in ($list)";
        }

        $list = "'" . implode("','", array_map([$this, 'escapeString'], $values)) . "'";
        return $isNotIn
            ? "AND $column NOT IN ($list)"
            : "AND $column IN ($list)";
    }

    private function escapeString(string $str): string
    {
        return str_replace("'", "''", trim($str));
    }
}
