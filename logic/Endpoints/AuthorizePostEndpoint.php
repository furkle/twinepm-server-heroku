<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Getters;
use \TwinePM\Filters\IdFilter;
use \TwinePM\Persisters;
use \TwinePM\OAuth2\Entities\UserEntity;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
use \League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use \League\OAuth2\Server\AuthorizationServer;
class AuthorizePostEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $cookies = $request->getCookieParams();
        $redis = $container->get(RedisClient::class);
        $context = [
            "request" => $request,
            "redis" => $redis,
        ];

        $user = Getters\LoggedInUserGetter::get($context);
        $userId = isset($user["id"]) ? $user["id"] : null;
        $userName = isset($user["name"]) ? $user["name"] : null;

        if ($user) {
            $filterResponse = IdFilter::filter($userId);
            if ($filterResponse->isError()) {
                return static::convertServerErrorToClientError(
                    $filterResponse);
            }

            $userId = $filterResponse->filtered;
        } else {
            $loginResponse = LoginPostEndpoint::execute(
                $request,
                $container);

            if ($loginResponse->isError()) {
                return static::convertServerErrorToClientError($loginResponse);
            }

            $value = [
                "sessionId" => SessionIdGetter::get(),
                "userId" => $loginResponse->id,
                "userName" => $loginResponse->name,
                "salt" => SaltGetter::get(),
                "redis" => $redis,
            ];

            Persisters\LoginSessionPersister::persist($value);
        }

        $authorizationSessionStr = isset($cookies["authorizationSession"]) ?
            $cookies["authorizationSession"] : null;
        if (!$authorizationSessionStr) {
            $errorCode = "AuthorizePostEndpointAuthorizeSessionCookieMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $yesAssoc = true;
        $cookieAuthSession = json_decode($authorizationSessionStr, $yesAssoc);
        $authorizationSessionId =
            isset($cookieAuthSession["authorizationSessionId"]) ?
                $cookieAuthSession["authorizationSessionId"] : null;
        if (!$authorizationSessionId) {
            $errorCode =
                "AuthorizePostEndpointAuthorizeSessionCookieIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $serverAuthSession = $redis->hGetAll($authorizationSessionId);
        if (!$serverAuthSession) {
            $errorCode = "AuthorizePostEndpointAuthorizeSessionRedisKeyMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $serverSalt = isset($serverAuthSession["salt"]) ?
            $serverAuthSession["salt"] : null;
        if (!isset($cookieAuthSession["salt"]) or
            $cookieAuthSession["salt"] !== $serverSalt)
        {
            $errorCode = "AuthorizePostEndpointAuthorizeSessionSaltInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $k = "serializedAuthorizationRequest";
        $authRequestStr = isset($serverAuthSession[$k]) ?
            $serverAuthSession[$k] : null;
        if (!$authRequestStr) {
            $errorCode =
                "AuthorizePostEndpointRedisHashMissingSerializedAuthenticationRequest";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $options = [
            "allowed_classes" => [
                "League\\OAuth2\\Server\\RequestTypes\\AuthorizationRequest",
                "TwinePM\\OAuth2\\Entities\\ScopeEntity",
                "TwinePM\\OAuth2\\Entities\\ClientEntity",
            ],
        ];

        $authRequest = unserialize($authRequestStr, $options);
        if (!($authRequest instanceof AuthorizationRequest)) {
            $errorCode =
                "AuthorizePostEndpointRedisHashAuthorizationRequestInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $value = [
            "sessionId" => $authorizationSessionId,
            "redis" => $redis,
        ];

        $unpersistResponse =
            Persisters\AuthorizationSessionPersister::unpersist($value);
        if ($unpersistResponse->isError()) {
            /* TODO: something */
        }

        $authServer = $container->get(AuthorizationServer::class);
        try {
            /* Once the user has logged in, set the user on the
             * AuthorizationRequest. */
            $authRequest->setUser(new UserEntity($userId));

            /* Refusal occurs on password validation, not here, so it
             * should always be true. */
            $authRequest->setAuthorizationApproved(true);
        } catch (Exception $e) {
            $errorCode = "AuthorizePostEndpointOAuthServerException";
            $errorData = [ "exception" => $e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $success = new Responses\Response();

        /* Completion and redirection are handled by the calling scope. */
        $success->authRequest = $authRequest;
        return $success;
    }
}