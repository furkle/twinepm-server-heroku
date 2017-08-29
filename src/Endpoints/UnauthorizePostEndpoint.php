<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Getters;
use \TwinePM\Filters\IdFilter;
use \TwinePM\SqlAbstractions\Authorizations\Authorization;
use \TwinePM\OAuth2\Entities\UserEntity;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
use \League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use \League\OAuth2\Server\AuthorizationServer;
use \PDO;
class UnauthorizePostEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $db = $container->get(PDO::class);

        $params = $request->getParsedBody();
        $authorizationResponse = null;
        if (isset($params["globalAuthorizationId"])) {
            $globalAuthorizationId = $params["globalAuthorizationId"];
            $filterResponse = IdFilter::filter($globalAuthorizationId);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }

            $globalAuthorizationId = $filterResponse->filtered;
            $src = [ "globalAuthorizationId" => $globalAuthorizationId, ];
            $authorizationResponse = Authorization::get($src, $db);
        } else if (isset($params["oAuthToken"])) {
            $token = $params["oAuthToken"];
            $authorizationResponse = Authorization::getFromToken($token, $db);

        } else {
            $errorCode = "UnauthorizePostEndpointNoArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if ($authorizationResponse->isError()) {
            return $authorizationResponse;
        }

        $authorization = $authorizationResponse->authorization;

        $cookies = $request->getCookieParams();
        $redis = $container->get(RedisClient::class);
        $context = [
            "request" => $request,
            "redis" => $redis,
        ];

        $user = Getters\LoggedInUserGetter::get($context);
        $userId = isset($user["id"]) ? $user["id"] : null;
        if ($userId === null) {
            $errorCode = "UnauthorizePostEndpointNoSessionUserId";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if ($authorization->getUserId() !== $userId) {
            $errorCode = "UnauthorizePostEndpointPermissionDenied";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $deletionResponse = $authorization->deleteFromDatabase();
        if ($deletionResponse->isError()) {
            return $deletionResponse;
        }

        $success = new Responses\Response();
        return $success;
    }
}