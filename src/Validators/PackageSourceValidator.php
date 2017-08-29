<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\Packages\Package;
use \PDO;
class PackageSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (isset($value["id"])) {
            $validationResponse = IdValidator::validate($value["id"]);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }
        }

        if (!array_key_exists("name", $value)) {
            $errorCode = "PackageSourceValidatorNameMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $validationResponse = NameValidator::validate($value["name"]);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        if (!array_key_exists("authorId", $value)) {
            $errorCode = "PackageSourceValidatorAuthorIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $validationResponse = IdValidator::validate($value["authorId"]);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        if (!array_key_exists("ownerId", $value)) {
            $errorCode = "PackageSourceValidatorOwnerIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $validationResponse = IdValidator::validate($value["ownerId"]);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        if (!array_key_exists("description", $value)) {
            $errorCode = "PackageSourceValidatorDescriptionMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$value["description"] or
            gettype($value["description"]) !== "string")
        {
            $errorCode = "PackageSourceValidatorDescriptionInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $type = isset($value["type"]) ? $value["type"] : null;
        $yesStrict = true;
        if (!isset($value["type"])) {
            $errorCode = "PackageSourceValidatorTypeMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!in_array($type, Package::TYPES, $yesStrict)) {
            $errorCode = "PackageSourceValidatorTypeInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $currentVersion = isset($value["currentVersion"]) ?
            $value["currentVersion"] : null;
        if (isset($value["currentVersion"]) and
            (!$currentVersion or gettype($currentVersion) !== "string"))
        {
            $errorCode = "PackageSourceValidatorVersionInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $timeCreated = isset($value["timeCreated"]) ?
            $value["timeCreated"] : null;
        if (isset($value["timeCreated"]) and
            gettype($timeCreated) !== "integer")
        {
            $errorCode = "PackageSourceValidatorTimeCreatedInvalid";
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $keywords = isset($value["keywords"]) ? $value["keywords"] : null;
        if (isset($value["keywords"])) {
            if (gettype($keywords) !== "array") {
                $errorCode = "PackageSourceValidatorKeywordsInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }

            foreach ($keywords as $value) {
                if (!$value or gettype($value) !== "string") {
                    $errorCode = "PackageSourceValidatorKeywordInvalid";
                    $error = new Responses\ErrorResponse($errorCode);
                    return $error;
                }
            }
        }

        if (isset($value["tag"]) and gettype($value["tag"]) !== "string") {
            $errorCode = "PackageSourceValidatorTagInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response($status);
        return $success;
    }
}