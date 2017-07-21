<?php
function getSearchDefaults() {
    $defaults = array(
        "type" => array(
            "packages",
            "users"
        ),

        "filterTargets" => array(
            "id",
            "name",
            "description",
            "homepage",
            "keywords"
        ),

        "filterStyles" => array(
            "exact",
            "contains",
            "metaphone/contains",
            "similarity",
            "levenshtein",
            "soundex/levenshtein",
            "metaphone/levenshtein"
        ),

        "sortTargets" => array(
            "id",
            "name",
            "description",
            "keywords",
            "homepage"
        ),

        "sortDirections" => array(
            "asc",
            "ascending",
            "desc",
            "descending"
        ),
    );

    return $defaults;
}
?>
