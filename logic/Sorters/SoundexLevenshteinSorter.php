<?php
namespace TwinePM\Sorters;

class SoundexLevenshteinSorter implements ISorter {
    function sort(
        array $results,
        string $query,
        string $sortTarget,
        string $sortDirection): array
    {
        $copy = $results;
        $func = function ($a, $b) use ($query, $sortTarget, $sortDirection) {
            $soundexA = soundex($a[$sortTarget]);
            $soundexLevenshteinA = levenshtein(soundex($query), $soundexA);
            $soundexB = soundex($b[$sortTarget]);
            $soundexLevenshteinB = levenshtein(soundex($query), $soundexB);
            if ($soundexLevenshteinA < $soundexLevenshteinB) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return 1;
                } else {
                    return -1;
                }
            } else if ($soundexLevenshteinA > $soundexLevenshteinB) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return -1;
                } else {
                    return 1;
                }
            }
        };

        usort($copy, $func);
        return $copy;
    }
}