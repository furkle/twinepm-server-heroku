<?php
namespace TwinePM\Filters;

use \TwinePM\Responses;
use \TwinePM\Validators\SearchFilterSourceValidator;
class MetaphoneLevenshteinFilter implements IFilter {
    const MAX_METAPHONE_LEVENSHTEIN = 5;

    public static function filter(
        $value,
        array $context = null): Responses\IResponse
    {
        $validationResponse = SearchFilterSourceValidator::validate($value);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $query = $value["query"];
        $results = $value["results"];
        $targets = $value["targets"];

        if (in_array(static::SEARCH_GLOBAL_SELECTORS, $query)) {
            $success = new Responses\Response();
            $success->filtered = $results;
            return $success;
        }

        $func = function ($row) use ($query, $targets) {
            foreach ($targets as $value) {
                if (isset($row[$value])) {
                    $metaphoneQuery = metaphone($query);
                    $metaphoneValue = metaphone($row[$value]);
                    $metaphoneLevenshtein = levenshtein(
                        $metaphoneQuery,
                        $metaphoneValue);

                    if ($metaphoneLevenshtein <=
                        static::MAX_METAPHONE_LEVENSHTEIN)
                    {
                        return true;
                    }
                }
            }
        };

        $filtered = array_filter($results, $func);

        $success = new Responses\Response();
        $success->filtered = $filtered;
        return $success;
    }
}
?>