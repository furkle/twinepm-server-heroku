<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\Getters\DatabaseGetter;
class VersionAvailabilityValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (gettype($value) !== "array") {
            $errorCode = "VersionAvailabilityValidatorValueInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (!array_key_exists("id", $value)) {
            $errorCode = "VersionAvailabilityValidatorIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $validateResponse = IdValidator::validate($value["id"]);
        if ($validateResponse->isError()) {
            return $validateResponse;
        }

        if (!array_key_exists($value["version"])) {
            $errorCode = "VersionAvailabilityValidatorVersionMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $version = $value["version"];

        $db = null;
        if (array_key_exists("database", $context)) {
            if (!($context["database"] instanceof PDO)) {
                $errorCode = "VersionAvailabilityValidatorDatabaseInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
            
            $db = $context["database"];
        } else {
            $db = DatabaseGetter::get();
        }

        $stmt = $db->prepare(
            "SELECT EXISTS(" .
                "SELECT version " .
                "FROM packages " .
                "WHERE id = :id AND version = :version" .
            ")");

        $sqlParams = [
            ":id" => $id,
            ":version" => $version,
        ];

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "PackageVersionAvailabilityQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        if ($fetch[0]) {
            $errorCode = "VersionAvailabilityValidatorVersionNotAvailable";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $response = new Responses\Response();
        return $response;
    }
}