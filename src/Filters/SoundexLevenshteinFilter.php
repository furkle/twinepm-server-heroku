<?php
namespace TwinePM\Filters;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Validators\SearchFilterSourceValidator;
class SoundexLevenshteinFilter implements ISearchFilter {
    const MAX_SOUNDEX_LEVENSHTEIN = 5;

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
            return $results;
        }

        $func = function ($row) use ($query, $targets) {
            foreach ($targets as $value) {
                if (isset($row[$value])) {
                    $soundexQuery = soundex($value["query"]);
                    $soundexValue = soundex($row[$value]);
                    $soundexLevenshtein = levenshtein(
                        $soundexQuery,
                        $soundexValue);

                    $max = static::MAX_SOUNDEX_LEVENSHTEIN;
                    return $soundexLevenshtein < $max;
                }
            }
        }

        $filtered = array_filter($results, $func);

        $success = new Responses\Response();
        $success->filtered = $filtered;
        return $success;
    }
}
?>