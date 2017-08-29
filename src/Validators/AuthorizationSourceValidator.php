<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\OAuth2\Repositories\ClientRepository;
use \TwinePM\OAuth2\Repositories\ScopeRepository;
class AuthorizationSourceValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (gettype($value) !== "array") {
            $errorCode = "AuthorizationSourceValidatorValueInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (array_key_exists("userId", $value)) {
            $validationResponse = IdValidator::validate($value["userId"]);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }
        } else {
            $errorCode = "AuthorizationSourceValidatorIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (array_key_exists("client", $value)) {
            $client = $value["client"];
            $clients = (new ClientRepository())->getClients();
            if (!array_key_exists($client, $clients)) {
                $errorCode = "AuthorizationSourceValidatorClientInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        } else {
            $errorCode = "AuthorizationSourceValidatorClientMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (array_key_exists("scopes", $value)) {
            $scopes = $value["scopes"];
            if (gettype($scopes) !== "array") {
                $errorCode = "AuthorizationSourceValidatorScopesInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!$scopes) {
                $errorCode = "AuthorizationSourceValidatorScopesEmpty";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;                
            }

            foreach ($scopes as $scope) {
                if (!array_key_exists($scope, ScopeRepository::SCOPES)) {
                    $errorCode = "AuthorizationSourceValidatorScopeInvalid";
                    $error = new Responses\ErrorResponse($errorCode);
                    return $error;
                }
            }
        } else {
            $errorCode = "AuthorizationSourceValidatorClientMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (!array_key_exists("ip", $value)) {
            $errorCode = "AuthorizationSourceValidatorIpMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$value["ip"] or gettype($ip) !== "string") {
            $errorCode = "AuthorizationSourceValidatorIpInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (!array_key_exists("oAuthToken", $value)) {
            $errorCode = "AuthorizationSourceValidatorOAuthTokenMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (gettype($oAuthToken) !== "string" or !$oAuthToken) {
            $errorCode = "AuthorizationSourceValidatorOAuthTokenInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $timeCreated = isset($value["timeCreated"]) ?
            $value["timeCreated"] : null;
        if (array_key_exists("timeCreated", $value) and
            (gettype($timeCreated) !== "integer" or $timeCreated < 0))
        {
            $errorCode = "AuthorizationSourceValidatorTimeCreatedInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }
}