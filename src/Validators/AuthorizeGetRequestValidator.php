<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
class AuthorizeGetRequestValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (!($value instanceof IRequest)) {
            $errorCode = "AuthorizeGetRequestValidatorInvalidValue";
            $error = Responses\ErrorResponse($errorCode);
            return $error;
        }

        $params = $value->getQueryParams();
        if (!isset($params["response_type") and
            $params["response_type"] !== "token")
        {
            $errorCode = "AuthorizeGetEndpointResponseTypeInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (!isset($params["client_id"])) {
            $errorCode = "AuthorizeGetEndpointClientIdMissing";
            $error = new Responses\ErrorResponse($error);
            return $error;
        }

        if (!isset($params["client_id"])) {
            $errorCode = "AuthorizeGetEndpointClientIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (!isset($params["scope"])) {
            $errorCode = "AuthorizeGetEndpointScopeMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (!isset($params["state"])) {
            $errorCode = "AuthorizeGetEndpointStateMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }
}