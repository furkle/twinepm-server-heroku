<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
class CredentialSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (array_key_exists("id", $value)) {
            $validationResponse = IdValidator::validate($value["id"]);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }
        }

        if (array_key_exists("name", $value)) {
            $validationResponse = NameValidator::validate($value["name"]);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }
        }

        $hash = isset($value["hash"]) ? $value["hash"] : null;
        if (!array_key_exists("hash", $value)) {
            $errorCode = "CredentialSourceValidatorHashMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$hash or gettype($hash) !== "string") {
            $errorCode = "CredentialSourceValidatorHashInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $validated = isset($value["validated"]) ? $value["validated"] : null;
        if (isset($value["validated"]) and
            gettype($validated) !== "boolean")
        {
            $errorCode = "CredentialSourceValidatorValidatedInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }
}