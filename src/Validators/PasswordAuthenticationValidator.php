<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \PDO;
class PasswordAuthenticationValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        $value = $source;

        $password = isset($source["password"]) ? $source["password"] : null;
        if (array_key_exists("password", $source)) {
            if (gettype($password) !== "string") {
                $errorCode = ErrorInfo::PASSWORD_AUTHENTICATION_VALIDATOR_PASSWORD_INVALID;
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!$password) {
                $errorCode = ErrorInfo::PASSWORD_AUTHENTICATION_VALIDATOR_PASSWORD_EMPTY;
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        } else {
            $errorCode = ErrorInfo::PASSWORD_AUTHENTICATION_VALIDATOR_PERMISSION_DENIED;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $hash = isset($source["hash"]) ? $source["hash"] : null;
        if (array_key_exists("hash", $source)) {
            if (gettype($hash) !== "string") {
                $errorCode = ErrorInfo::PASSWORD_AUTHENTICATION_VALIDATOR_PASSWORD_INVALID;
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!$hash) {
                $errorCode = ErrorInfo::PASSWORD_AUTHENTICATION_VALIDATOR_PASSWORD_EMPTY;
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        } else {
            $errorCode = ErrorInfo::PASSWORD_AUTHENTICATION_VALIDATOR_PERMISSION_DENIED;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $valid = password_verify($password, $hash);
        if (!$valid) {
            $errorCode = ErrorInfo::PASSWORD_AUTHENTICATION_VALIDATOR_PERMISSION_DENIED;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $status = Responses\Response::HTTP_SUCCESS;
        $success = new Responses\Response($status);
        return $success;
    }
}