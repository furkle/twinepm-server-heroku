<?php
namespace TwinePM\Sorters;

class LevenshteinSorter implements ISorter {
    public static function sort(
        array $results,
        string $query,
        string $sortTarget,
        string $sortDirection): array
    {
        $sorted = $results;
        $func = function ($a, $b) use ($query, $sortTarget, $sortDirection) {
            $levenshteinA = levenshtein($query, $a[$sortTarget]);
            $levenshteinB = levenshtein($query, $b[$sortTarget]);
            if ($levenshteinA < $levenshteinB) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return 1;
                } else {
                    return -1;
                }
            } else if ($levenshteinA > $levenshteinB) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return -1;
                } else {
                    return 1;
                }
            } else {
                return 0;
            }
        };

        usort($sorted, $func);
        return $sorted;
    }
}
?>