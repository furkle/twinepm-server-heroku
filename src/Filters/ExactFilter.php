<?php
namespace TwinePM\Filters;

use \TwinePM\Responses;
use \TwinePM\Validators\SearchFilterSourceValidator;
class ExactFilter implements IFilter {
    public static function filter(
        $value,
        array $context = null): Responses\IResponse
    {
        $source = $value;
        $validationResponse = SearchFilterSourceValidator::validate($source);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $query = $source["query"];
        $results = $source["results"];
        $targets = $source["targets"];

        if (in_array(static::SEARCH_GLOBAL_SELECTORS, $query)) {
            $success = new Responses\Response();
            $success->filtered = $results;
            return $success;
        }

        $func = function ($row) use ($query, $targets) {
            foreach ($targets as $value) {
                $matches = strtolower($row[$value]) === strtolower($query);
                if (isset($row[$value]) and $matches) {
                    return true;
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