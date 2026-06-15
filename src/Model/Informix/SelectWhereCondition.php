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

    public function in(string $column, ?array $values): string
    {
        $values = $values ? implode(',', $values) : null;
        if (empty($values)) return '';
        return "AND $column in ('$values')";
    }

    public function ni(string $column, array $values): string
    {
        $values = $values ? implode(',', $values) : null;
        if (empty($values)) return '';
        return "AND $column NOT IN ('$values')";
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

        if ($d1) $condition .= "AND $column >= datetime($d1) year to day";
        if ($d2) $condition .= "AND $column <= datetime($d2) year to day";

        return $condition;
    }
}
