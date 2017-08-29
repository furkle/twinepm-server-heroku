<?php
namespace TwinePM\Filters;

use \TwinePM\Responses;
use \TwinePM\Validators\SearchFilterSourceValidator;
class SimilarityFilter implements IFilter {
    const MIN_SIMILARITY = 45;

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

        if (in_array(self::SEARCH_GLOBAL_SELECTORS, $query)) {
            $success = new Responses\Response();
            $success->filtered = $results;
            return $results;
        }

        $func = function ($row) use ($query, $targets) {
            foreach ($targets as $value) {
                if (isset($row[$value])) {
                    $percent = null;
                    similar_text($query, $row[$value], $percent);
                    return $percent >= static::MIN_SIMILARITY;
                }
            }

            return false;
        };

        $filtered = array_filter($results, $func);

        $success = new Responses\Response();
        $success->filtered = $filtered;
        return $success;
    }
}
?>