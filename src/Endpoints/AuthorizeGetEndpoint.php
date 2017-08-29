<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Getters;
use \TwinePM\Persisters;
use \TwinePM\OAuth2\Repositories\ScopeRepository;
use \TwinePM\OAuth2\Repositories\ClientRepository;
use \Psr\Container\ContainerInterface as IContainer;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \League\OAuth2\Server\AuthorizationServer;
use \Predis\Client as RedisClient;
use \PDO;
use \Exception;
class AuthorizeGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $params = $request->getQueryParams();
        if (!isset($params["state"]) or !$params["state"]) {
            $errorCode = "AuthorizeGetEndpointStateMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $responseType = $params["response_type"];
        if ($responseType !== "token") {
            $errorCode = "AuthorizeGetEndpointResponseTypeInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $redis = $container->get(RedisClient::class);
        $key = null;
        $maxAttempts = 5;
        for ($attempts = 0; $attempts < $maxAttempts; $attempts += 1) {
            $keyAttempt = Getters\SessionIdGetter::get();
            
            /* Do not replace keys. This should prevent collisions. */
            if (!$redis->exists($keyAttempt)) {
                $key = $keyAttempt;
                break;
            }
        }

        if (!$key) {
            $errorCode = "AuthorizeGetEndpointSessionIdGenerationFailed";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $authServer = $container->get(AuthorizationServer::class);

        $authRequest = null;
        try {
            $authRequest = $authServer->validateAuthorizationRequest($request);
        } catch (Exception $e) {
            $errorCode =
                "AuthorizeGetEndpointAuthorizationRequestValidationFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $value = [
            "sessionId" => $key,
            "authorizationRequest" => $authRequest,
            "salt" => Getters\SaltGetter::get(),
        ];

        $context = [ "redis" => $redis, ];
        $persistResponse = Persisters\AuthorizationSessionPersister::persist(
            $value,
            $context);

        if ($persistResponse->isError()) {
            return static::convertClientErrorToServerError($persistResponse);
        }

        $context = [
            "request" => $request,
            "redis" => $redis,
        ];

        $repo = new ClientRepository();
        $clients = $repo->getClients();
        $client = $clients[$params["client_id"]];

        $scopeObjects = ScopeRepository::SCOPES;
        $scopes = explode(" ", $params["scopes"]);
        $scopes = array_map(function($a) {
            return $scopeObjects[$a];
        }, $scopes);

        $currentUser = Getters\LoggedInUserGetter::get($context);

        $templateVars = [
            "client" => $client,
            "scopes" => $scopes,
            "loggedInUser" => $currentUser,
        ];

        $success = new Responses\Response();
        $success->templateVars = $templateVars;

        return $success;
    }
}