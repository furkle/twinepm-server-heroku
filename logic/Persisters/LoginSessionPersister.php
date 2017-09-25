<?php
namespace TwinePM\Persisters;

use TwinePM\Exceptions\InvalidArgumentException;
use TwinePM\Exceptions\NoResultExistsException;
use TwinePM\Exceptions\PersistenceFailedException;
class LoginSessionPersister {
    function persist(
        string $requestId,
        int $userId,
        string $salt,
        string $domain,
        AuthorizationRequest $authRequest,
        Client $cache,
        PDO $database,
        callable $encrypt,
        callable $generateHmac): void
    {
        $db = $database;
        $db->setAttribute($db::ATTR_ERRMODE, $db::ERRMODE_EXCEPTION);
        $queryStr = "SELECT name FROM credentials WHERE id = :id";
        $stmt = $db->prepare($queryStr);
        $sqlParams = [ ":id" => $userId, ];
        $stmt->execute($sqlParams);

        $fetch = $stmt->fetch($db::FETCH_ASSOC);
        if (!$fetch) {
            $errorCode = "IdNotInCredentials";
            throw new NoResultExistsException($errorCode);
        }

        $key = "loginSession";
        $cookie = [
            "requestId" => $requestId,
            "salt" => $salt,
        ];

        array_multisort($cookie);

        $encryptedCookie = $encrypt(json_encode($cookie));
        $cookieHmac = $generateHmac($encryptedCookie);

        $userName = $fetch["name"];
        $key = $requestId;
        $cacheSession = [
            "userId" => $userId,
            "userName" => $userName,
            "salt" => $salt,
            "cookieHmac" => $cookieHmac,
        ];

        array_multisort($cacheSession);

        $cache->HMSET($requestId, $cacheSession);

        /* Cookies last 30 days. */
        $expire = time() + 60 * 60 * 24 * 30;
        $path = "/authorize";

        $lh = "localhost";
        $scheme_lh = "http://" . $lh;
        $result = null;
        if (substr($domain, 0, strlen($lh)) === $lh or
            substr($domain, 0, strlen($scheme_lh)) === $scheme_lh)
        {
            $result = setcookie(
                $key,
                $cookieSession,
                $expire);
        } else {
            $secure = true;
            $httpOnly = true;
            $result = setcookie(
                $key,
                $cookieSession,
                $expire,
                $path,
                $domain,
                $secure,
                $httpOnly);
        }

        if (!$result) {
            $errorCode = "LoginSessionPersistence";
            throw new PersistenceFailedException($errorCode);
        }
    }

    function unpersist(
        string $requestId,
        string $salt,
        string $domain,
        AuthorizationRequest $authRequest,
        Client $cache): void
    {
        $cache->DEL($requestId);

        $key = "authorizationSession";
        $value = "";
        $expire = -1;
        $path = "/authorize";
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
            $errorCode = "LoginSessionPersistence";
            throw new UnpersistenceFailedException($errorCode);
        }
    }
}