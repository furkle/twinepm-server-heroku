<?php
namespace TwinePM\Searches;

interface ISearch {
    const TYPES = [
        "packages",
        "users",
    ];

    const FILTER_TARGETS = [
        "id",
        "name",
        "description",
        "homepage",
        "keywords",
    ];

    const FILTER_STYLES = [
        "exact",
        "contains",
        "metaphone/contains",
        "similarity",
        "levenshtein",
        "soundex/levenshtein",
        "metaphone/levenshtein"
    ];

    const SORT_TARGETS = [
        "id",
        "name",
        "description",
        "keywords",
        "homepage"
    ];

    const SORT_DIRECTIONS = [
        "asc",
        "ascending",
        "desc",
        "descending"
    ];

    const SUBTYPES = [
        "macros",
        "scripts",
        "styles",
        "passagethemes",
        "storythemes",
    ];

    public function query(
        string $queryString,
        string $includePackages): IResponse;

    public function isError(): bool;
    public function getError(): ?ErrorResponse;
}
?>