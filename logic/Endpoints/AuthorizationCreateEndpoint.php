<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
use TypeError;
use TwinePM\DecryptionFailedException;
class AuthorizePostEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $request = $container->get("request");
        $cookies = $request->getCookieParams();
        $user = $container->get("loggedInUser");
        $userId = null;
        $userName = null;
        if ($user) {
            $userId = $container->get("idFilter")($user["id"]);
            $userName = $user["userName"];
        } else {
            $loginPostEndpoint = $container->get("loginPostEndpoint");
            $loginResponse = $loginPostEndpoint($container);
            $persistLoginSession = $container->get("persistLoginSession");
            $userId = $loginResponse->userId;
            $userName = $loginResponse->userName;
            $persistLoginSession($userId, $userName);
            $userId = $loginResponse->userId;
        }

        $decrypt = $container->get("decrypt");
        $encryptedSession = isset($cookies["authorizationSession"]) ?
            $cookies["authorizationSession"] : null;
        if (!$encryptedSession) {
            $errorCode = "AuthorizationSessionInvalid";
            throw new InvalidRequestFieldException($errorCode);
        }

        $cookieSessionStr = null;

        try {
            $decrypt($encryptedSession);
        } catch (TypeError $e) {
            $errorCode = "AuthorizationSessionDecryptionFailed";
            throw new DecryptionFailedException($errorCode);
        }

        $yesAssoc = true;
        $cookieSession = json_decode($cookieSessionStr, $yesAssoc);
        $authRequestId = isset($cookieSession["authorizationRequestId"]) ?
            $cookieSession["authorizationRequestId"] : null;
        if (!$authRequestId) {
            $errorCode = "AuthorizationSessionIdInvalid";
            throw new InvalidRequestFieldException($errorCode);
        }

        $cache = $container->get("cacheClient");
        $serverAuthSession = $cache->HGETALL($authorizationSessionId);
        if (!$serverAuthSession) {
            $errorCode = "AuthorizationSessionInvalid";
            throw new InvalidRequestFieldException($errorCode);
        }

        $serverSalt = isset($serverAuthSession["salt"]) ?
            $serverAuthSession["salt"] : null;
        $serverHmac = isset($serverAuthSession["cookieHmac"]) ?
            $serverAuthSession["cookieHmac"] : null;
        if (!isset($cookieSession["salt"]) or
            $cookieSession["salt"] !== $serverSalt or
            $generateHmac($encryptedSession) !== $serverHmac)
        {
            $errorCode = "AuthorizationSessionInvalid";
            throw new PermissionDeniedException($errorCode);
        }

        $k = "serializedAuthorizationRequest";
        $authRequestStr = isset($serverAuthSession[$k]) ?
            $serverAuthSession[$k] : null;
        if (!$authRequestStr) {
            $errorCode = "AuthorizationSessionInvalid";
            throw new PermissionDeniedException($errorCode);
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
            $errorCode = "AuthorizationRequestDeserializationFailed";
            throw new PermissionDeniedException($errorCode);
        }

        $container->get("unpersistAuthorizationSession")($authRequestId);

        $authServer = $container->get("authorizationServer");

        /* Once the user has logged in, set the user on the
         * AuthorizationRequest. */
        $getUserEntity = $container->get("getUserEntity");
        $authRequest->setUser($getUserEntity($userId));

        /* Refusal occurs on password validation, not here, so it
         * should always be true. */
        $authRequest->setAuthorizationApproved(true);

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        return $response;
    }
}