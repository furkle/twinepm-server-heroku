<?php
namespace TwinePM\Persisters;

use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Predis\Client;
class AuthorizationRequestPersister {
    function persist(
        string $requestId,
        string $salt,
        string $domain,
        AuthorizationRequest $authRequest,
        Client $cache,
        callable $encrypt,
        callable $generateHmac): void
    {
        if (!$requestId) {
            $errorCode = "RequestIdInvalid";
            throw new InvalidArgumentException($errorCode);
        }

        if (!$salt) {
            $errorCode = "SaltInvalid";
            throw new InvalidArgumentException($errorCode);
        }

        if (!$domain) {
            $errorCode = "DomainInvalid";
            throw new InvalidArgumentException($errorCode);
        }

        $cookie = [
            "requestId" => $requestId,
            "salt" => $salt,
        ];

        array_multisort($cookie);

        $encryptedCookie = $encrypt(json_encode($cookie));
        $cookieHmac = $generateHmac($encryptedCookie);

        $sar = serialize($authRequest);

        $cacheSession = [
            "serializedAuthenticationRequest" => $sar,
            "salt" => $salt,
            "cookieHmac" => $cookieHmac,
        ];

        array_multisort($cacheSession);

        $redis->HMSET($requestId, $cacheSession);

        $key = "authorizationRequest";
        /* Allow cookie to persist for one hour. */
        $expire = time() + 60 * 60;
        $path = "/authorization";
        $lh = "localhost";
        $scheme_lh = "http://" . $lh;
        $result = null;
        if (substr($domain, 0, strlen($lh)) === $lh or
            substr($domain, 0, strlen($scheme_lh)) === $scheme_lh)
        {
            $result = setcookie(
                $key,
                $value,
                $expire);
        } else {
            $secure = true;
            $httpOnly = true;
            $result = setcookie(
                $key,
                $value,
                $expire,
                $path,
                $domain,
                $secure,
                $httpOnly);
        }

        if (!$result) {
            $errorCode = "AuthorizationSessionPersistence";
            throw new PersistenceFailedException($errorCode);
        }
    }

    function unpersist(
        string $requestId,
        string $domain,
        Client $cache): void
    {
        if (!$requestId) {
            $errorCode = "RequestIdInvalid";
            throw new InvalidArgumentException($errorCode);
        }

        if (!$domain) {
            $errorCode = "DomainInvalid";
            throw new InvalidArgumentException($errorCode);
        }

        $cache->DEL($key);

        $key = "authorizationSession";
        $value = "";
        $expire = -1;
        $path = "/authorization";
        $lh = "localhost";
        $result = null;
        if (substr($domain, 0, strlen($lh)) === $lh) {
            $result = setcookie(
                $key,
                $value,
                $expire);
        } else {
            $secure = true;
            $httpOnly = true;
            $result = setcookie(
                $key,
                $value,
                $expire,
                $path,
                $domain,
                $secure,
                $httpOnly);
        }

        if (!$result) {
            $errorCode = "AuthorizationSessionPersistence";
            throw new UnpersistenceFailedException($errorCode);
        }
    }
}