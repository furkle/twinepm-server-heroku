<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
class VersionSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (isset($value["packageId"])) {
            $src = [ "id" => $value["packageId"], ];
            $validationResponse = IdValidator::validate($src);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }
        }

        $validationResponse = NameValidator::validate($value);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        if (!array_key_exists("version", $value)) {
            $errorCode = "VersionSourceValidatorVersionMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$value["version"] and
            gettype($value["version"]) !== "string")
        {
            $errorCode = "VersionSourceValidatorVersionInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (isset($value["js"]) and gettype($value["js"]) !== "string") {
            $errorCode = "VersionSourceValidatorJsInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (isset($value["css"]) and gettype($value["css"]) !== "string") {
            $errorCode = "VersionSourceValidatorCssInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (isset($value["keywords"])) {
            if (gettype($value["keywords"]) !== "array") {
                $errorCode = "VersionSourceValidatorKeywordsInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!$value["keywords"]) {
                $errorCode = "VersionSourceValidatorKeywordsEmpty";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }

            foreach ($value["keywords"] as $value) {
                if (gettype($value) !== "string") {
                    $errorCode = "VersionSourceValidatorKeywordInvalid";
                    $error = new Responses\ErrorResponse($errorCode);
                    return $error;
                } else if (!$value) {
                    $errorCode = "VersionSourceValidatorKeywordEmpty";
                    $error = new Responses\ErrorResponse($errorCode);
                    return $error;
                }
            }
        }

        if (!array_key_exists("destination", $value)) {
            $errorCode = "VersionSourceValidatorDescriptionMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$value["description"] or
            gettype($value["description"]]) !== "string")
        {
            $errorCode = "VersionSourceValidatorDescriptionInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (isset($value["homepage"]) and
            gettype($value["homepage"]) !== "string")
        {
            $errorCode = "VersionSourceValidatorHomepageInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }
}