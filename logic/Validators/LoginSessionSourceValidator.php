<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
class LoginSessionSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        $sessionId = isset($value["sessionId"]) ? $value["sessionId"] : null;
        if (!array_key_exists("sessionId", $value)) {
            $errorCode = "LoginSessionSourceValidatorSessionIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$sessionId or gettype($sessionId) !== "string") {
            $errorCode = "LoginSessionSourceValidatorSessionIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $userId = isset($value["userId"]) ? $value["userId"] : null;
        if (!array_key_exists("userId", $value)) {
            $errorCode = "LoginSessionSourceValidatorUserIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (gettype($userId) !== "integer" or $userId < 0) {
            $errorCode = "LoginSessionSourceValidatorUserIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $salt = isset($value["salt"]) ? $value["salt"] : null;
        if ($salt === null) {
            $errorCode = "LoginSessionSourceValidatorSaltMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$salt or gettype($salt) !== "string") {
            $errorCode = "LoginSessionSourceValidatorSaltInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }
}