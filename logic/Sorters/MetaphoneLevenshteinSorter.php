<?php
namespace TwinePM\Sorters;

class MetaphoneLevenshteinSorter implements ISorter {
    public static function sort(
        array $results,
        string $query,
        string $sortTarget,
        string $sortDirection): array
    {
        $sorted = $results;
        $func = function ($a, $b) use ($query, $sortTarget, $sortDirection) {
            $metaphoneA = metaphone($a[$sortTarget]);
            $metaphoneLevenshteinA = levenshtein(
                metaphone($query),
                $metaphoneA);

            $metaphoneB = metaphone($b[$sortTarget]);
            $metaphoneLevenshteinB = levenshtein(
                metaphone($query),
                $metaphoneB);
            if ($metaphoneLevenshteinA < $metaphoneLevenshteinB) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return 1;
                } else {
                    return -1;
                }
            } else if ($metaphoneLevenshteinA > $metaphoneLevenshteinB) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return -1;
                } else {
                    return 1;
                }
            }
        };

        usort($sorted, $func);
        return $sorted;
    }
}