<?php
namespace TwinePM\Persisters;

use \TwinePM\Responses;
use \TwinePM\Getters;
use \TwinePM\Validators\AuthorizationSessionSourceValidator;
use \Predis\Client as RedisClient;
use \League\OAuth2\Server\RequestTypes\AuthorizationRequest;
class AuthorizationSessionPersister implements IPersister {
    public static function persist(
        $value,
        array $context = null): Responses\IResponse
    {
        $validationResponse = AuthorizationSessionSourceValidator::validate(
            $value);

        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $sessionId = $value["sessionId"];

        $authRequest = $value["authorizationRequest"];
        $sar = serialize($authRequest);

        $salt = $value["salt"];

        $redis = isset($context["redis"]) ? $context["redis"] : null;
        if (!($redis instanceof RedisClient)) {
            $redis = Getters\RedisServerGetter::get();
        }

        $key = $sessionId;
        $value = [
            "serializedAuthorizationRequest" => $sar,
            "salt" => $salt,
        ];

        $redis->hmset($key, $value);

        $key = "authorizationSession";
        $value = json_encode([
            "authorizationSessionId" => $sessionId,
            "salt" => $salt,
        ]);

        /* Allow cookie to persist for one hour. */
        $expire = time() + 60 * 60;
        $path = "/authorize";
        $domain = Getters\ServerDomainNameGetter::get();
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
            $errorCode = "AuthorizationSessionPersisterPersistenceFailed";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }

    public static function unpersist(
        $value,
        array $context = null): Responses\IResponse
    {
        $key = isset($value["sessionId"]) ? $value["sessionId"] : null;
        if (!array_key_exists("sessionId", $value)) {
            $errorCode = "AuthorizationSessionPersisterUnpersistSessionIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$key or gettype($key) !== "string") {
            $errorCode = "AuthorizationSessionPersisterUnpersistSessionIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $redis = isset($context["redis"]) ? $context["redis"] : null;
        if (!($redis instanceof RedisClient)) {
            $redis = Getters\RedisServerGetter::get();
        }

        $redis->del($key);

        $key = "authorizationSession";
        $value = "";
        $expire = -1;
        $path = "/authorize";
        $domain = Getters\ServerDomainNameGetter::get();
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
            $errorCode = "AuthorizationSessionPersisterUnpersistenceFailed";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }
        
        $success = new Responses\Response();
        return $success;
    }
}