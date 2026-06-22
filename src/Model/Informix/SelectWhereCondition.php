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
        if (!$value) return '';
        return "AND $column LIKE '%$value%'";
    }

    public function nlike(string $column, ?string $value): string
    {
        $value = $value ? trim($value) : null;
        if (!$value) return '';
        return "AND $column NOT LIKE '%$value%'";
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
        return "AND ($column IS NULL  OR $column <> '' ";
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
