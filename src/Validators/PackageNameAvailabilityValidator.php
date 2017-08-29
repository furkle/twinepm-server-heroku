<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\Getters\DatabaseGetter;
use \PDO;
use \Exception;
class PackageNameAvailabilityValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (gettype($value) !== "array") {
            $errorCode = "PackageNameAvailabilityValidatorValueInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $name = isset($value["name"]) ? $value["name"] : null;
        if (!array_key_exists("name", $value)) {
            $errorCode = "PackageNameAvailabilityValidatorNameMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$name or gettype($value) !== "string") {
            $errorCode = "PackageNameAvailabilityValidatorNameInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $validationResponse = NameValidator::validate($name);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $db = isset($context["database"]) ? $context["database"] : null;
        if (!array_key_exists("database", $context)) {
            $db = DatabaseGetter::get();
        } else if (!($db instanceof PDO)) {
            $errorCode = "PackageNameAvailabilityValidatorDatabaseInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $queryStr = "SELECT EXISTS(" .
            "SELECT name " .
            "FROM packages " .
            "WHERE name = :name";
        $sqlParams = [ "name" => $value, ];
        if (isset($value["id"])) {
            $validationResponse = IdValidator::validate($value["id"]);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }

            $queryStr .= " AND id != :id";
            $sqlParams[":id"] = $value["id"];
        }

        /* Close EXISTS expression. */
        $queryStr .= ")";

        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorData = [ "exception" => (string)$e, ];
            $errorCode = "PackageNameAvailabilityValidatorQueryFailed";
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        if ($fetch[0]) {
            $errorCode = "PackageNameAvailabilityValidatorNameNotAvailable";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $response = new Responses\Response();
        return $response;
    }
}