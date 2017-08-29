<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \PDO;
class SearchFilterSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        $value = $source;

        $query = isset($source["query"]) ? $source["query"] : null;
        if ($query === null) {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_QUERY_MISSING;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (gettype($query) !== "string") {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_QUERY_INVALID;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $results = isset($source["results"]) ? $source["results"] : null;
        if ($query === null) {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_RESULTS_MISSING;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (gettype($targets) !== "array") {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_RESULTS_INVALID;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$targets) {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_RESULTS_EMPTY;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $targets = isset($source["targets"]) ? $source["targets"] : null;
        if ($targets === null) {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_TARGETS_MISSING;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (gettype($targets) !== "array") {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_TARGETS_INVALID;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$targets) {
            $errorCode = ErrorInfo::SEARCH_FILTER_SOURCE_VALIDATOR_TARGETS_EMPTY;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $status = Responses\Response::HTTP_SUCCESS;
        $success = new Responses\Response($status);
        return $success;
    }
}