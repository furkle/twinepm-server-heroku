<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
use TwinePM\Exceptions\InvalidArgumentException;
use TwinePM\Exceptions\NoResultExistsException;
use TwinePM\Exceptions\PersistenceFailedException;
class AuthorizationHtmlEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $source = $request->getQueryParams();
        if (!isset($source["state"]) or !$source["state"]) {
            $errorCode = "AuthorizationStateMissing";
            throw new InvalidArgumentException($errorCode);
        }

        $responseType = isset($source["response_type"]) ?
            $source["response_type"] : null;
        if ($responseType !== "token") {
            $errorCode = "AuthorizationResponseTypeInvalid";
            throw new InvalidArgumentException($errorCode);
        }

        $clientId = isset($source["client_id"]) ? $source["client_id"] : null;
        if (!$clientId) {
            $errorCode = "AuthorizationClientIdInvalid";
            throw new InvalidArgumentException($errorCode);
        }

        $cache = $container->get("cacheClient");
        $requestId = null;
        $maxAttempts = 5;
        for ($attempts = 0; $attempts < $maxAttempts; $attempts += 1) {
            $attempt = $container->get("requestId");
            
            /* Do not replace keys. This should prevent collisions. */
            if (!$cache->EXISTS($attempt)) {
                $requestId = $attempt;
                break;
            }
        }

        if (!$requestId) {
            $errorCode = "RequestIdGenerationFailed";
            throw new RequestIdGenerationFailedException($errorCode);
        }

        $authServer = $container->get("authorizationServer");
        $authRequest = $authServer->validateAuthorizationRequest($request);

        $value = [
            "requestId" => $requestId,
            "authorizationRequest" => $authRequest,
        ];

        $key = "persistAuthorizationRequest";
        $persistAuthorizationRequest = $container->get($key);
        $persistAuthorizationRequest($requestId, $authRequest);

        $clientRepo = $container->get("clientRepository");
        $clients = $clientRepo->getClients();
        $client = isset($clients[$clientId]) ? $clients[$clientId] : null;
        if (!$client) {
            $errorCode = "AuthorizationHtmlClientLookup";
            throw new ClientDoesNotExistException($errorCode);
        }

        $scopeObjects = $container->get("scopeRepository")::SCOPES;
        $scopes = explode(" ", $source["scopes"]);
        $scopes = array_map(function($a) {
            return $scopeObjects[$a];
        }, $scopes);

        $response = $container->get("response");
        $response->templateVars = [
            "client" => $client,
            "scopes" => $scopes,
            "loggedInUser" => $container->get("loggedInUser"),
        ];

        return $response;
    }
}