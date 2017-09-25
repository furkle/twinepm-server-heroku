<?php
namespace TwinePM\Sorters;

class SimilaritySorter implements ISorter {
    public static function sort(
        array $results,
        string $query,
        string $sortTarget,
        string $sortDirection): array
    {
        $sorted = $results;
        $func = function ($a, $b) use ($query, $sortTarget, $sortDirection) {
            $similarityA = null;
            similar_text($query, $a[$sortTarget], $similarityA);
            $similarityB = null;
            similar_text($query, $b[$sortTarget], $similarityB);
            if ($similarityA > $similarityB) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return 1;
                } else {
                    return -1;
                }
            } else if ($similarityA < $similarityB) {
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
        }

        usort($sorted, $func);
        return $sorted;
    }
}