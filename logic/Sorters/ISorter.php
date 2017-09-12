<?php
namespace TwinePM\Sorters;

interface ISorter {
    public static function sort(
        array $results,
        string $query,
        string $sortTarget,
        string $sortDirection): array;
}
?> 