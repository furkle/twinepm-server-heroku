<?php
namespace TwinePM\Transformers;

use \TwinePM\Responses;
use \TwinePM\OAuth2\Repositories\ClientRepository;
use \TwinePM\OAuth2\Repositories\ScopeRepository;
use \DateTime;
class AuthorizationToTemplatingArrayTransformer implements ITransformer {
    public static function transform(
        $value,
        array $context = null): Responses\IResponse
    {
        if (gettype($value) !== "array") {
            $errorCode =
                "AuthorizationToTemplatingArrayTransformerValueInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $authorizations = array_map(function ($auth) {
            $clientId = $auth->getClient();
            $clients = (new ClientRepository())->getClients();
            $clientArray = isset($clients[$clientId]) ?
                $clients[$clientId] : null;

            $clientName = null;
            if ($clientArray) {
                $clientName = $clientArray["name"];
            } else {
                $clientName = "CLIENT_NAME_ERROR";
            }

            $time = date(DateTime::COOKIE, $auth->getTimeCreated());
            $scopes = array_map(function ($scope) {
                if (array_key_exists($scope, ScopeRepository::SCOPES)) {
                    return ScopeRepository::SCOPES[$scope]["name"];
                }

                return "SCOPE_ERROR";
            }, $auth->getScopes());

            $scopes = implode(", ", $scopes);
            $templatedAuth = [
                "globalAuthorizationId" => $auth->getGlobalAuthorizationId(),
                "clientName" => $clientName,
                "time" => $time,
                "scopes" => $scopes,
                "ip" => $auth->getIp(),
            ];

            return $templatedAuth;
        }, $value);

        $success = new Responses\Response();
        $success->transformed = $authorizations;
        return $success;
    }
}