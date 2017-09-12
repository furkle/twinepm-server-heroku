<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \League\OAuth2\Server\RequestTypes\AuthorizationRequest;
class AuthorizationSessionSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        $sessionId = isset($value["sessionId"]) ? $value["sessionId"] : null;
        if (!array_key_exists("sessionId", $value)) {
            $errorCode =
                "AuthorizationSessionSourceValidatorPersistSessionIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$sessionId or gettype($sessionId) !== "string") {
            $errorCode = "AuthorizationSessionSourceValidatorSessionIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $authRequest = isset($value["authorizationRequest"]) ?
            $value["authorizationRequest"] : null;
        if (!array_key_exists("authorizationRequest", $value)) {
            $errorCode =
                "AuthorizationSessionSourceValidatorAuthorizationRequestMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!($authRequest instanceof AuthorizationRequest)) {
            $errorCode =
                "AuthorizationSessionSourceValidatorAuthorizationRequestInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $salt = isset($value["salt"]) ? $value["salt"] : null;
        if (!array_key_exists("salt", $value)) {
            $errorCode = "AuthorizationSessionSourceValidatorSaltMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$salt or gettype($salt) !== "string") {
            $errorCode = "AuthorizeSessionSourceValidatorSaltInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }
}