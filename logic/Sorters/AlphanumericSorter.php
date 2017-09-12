<?php
namespace TwinePM\Sorters;

class AlphanumericSorter implements ISorter {
    public static function sort(
        array $results,
        string $query,
        string $sortTarget,
        string $sortDirection): array
    {
        $sorted = $results;
        usort($copy, function ($a, $b) use ($sortTarget, $sortDirection) {
            $comp = strcasecmp($a[$sortTarget], $b[$sortTarget]);
            if ($comp > 0) {
                if ($sortDirection === "asc" or
                    $sortDirection === "ascending")
                {
                    return 1;
                } else {
                    return -1;
                }
            } else if ($comp < 0) {
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
        });

        return $sorted;
    }
}
?>