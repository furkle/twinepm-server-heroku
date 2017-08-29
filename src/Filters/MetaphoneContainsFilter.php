<?php
namespace TwinePM\Filters;

use \TwinePM\Responses;
use \TwinePM\Validators\SearchFilterSourceValidator;
class MetaphoneContainsFilter {
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
                $pos = strpos(strtolower($row[$value]), strtolower($query));
                if (isset($row[$value]) and $pos !== false) {
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