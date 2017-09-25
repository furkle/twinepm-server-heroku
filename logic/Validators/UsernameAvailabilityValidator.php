<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Getters;
use \PDO;
use \Exception;
class UsernameAvailabilityValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        $value = $source;

        $validationResponse = IdValidator::validate($source);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $validationResponse = NameValidator::validate($source);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $db = null;
        if (array_key_exists("database", $context)) {
            if ($context["database"] instanceof PDO) {
                $db = $context["database"];
            } else {
                $errorCode = ErrorInfo::USERNAME_AVAILABILITY_VALIDATOR_DATABASE_INVALID;
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        } else {
            $db = Getters\DatabaseGetter::get();
        }

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare(
            "SELECT EXISTS(" .
                "SELECT name " .
                "FROM accounts " .
                "WHERE name = :name AND id != :id" .
            ")");

        $name = isset($source["name"]) ? $source["name"] : null;
        $sqlParams = [
            ":name" => $source["name"],
            ":id" => $source["id"],
        ];

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $data = [ "exception" => (string)$e, ];
            $errorCode = ErrorInfo::USERNAME_AVAILABILITY_VALIDATOR_QUERY_FAILED;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        if ($fetch[0]) {
            $errorCode = ErrorInfo::USERNAME_AVAILABILITY_VALIDATOR_USERNAME_NOT_AVAILABLE;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $status = Responses\Response::HTTP_SUCCESS;
        $success = new Responses\Response($status);
        return $success;
    }
}