<?php
namespace TwinePM\Persisters;

use \TwinePM\Responses;
use \TwinePM\Getters;
use \TwinePM\Validators\LoginSessionSourceValidator;
use \Predis\Client as RedisClient;
use \PDO;
class LoginSessionPersister implements IPersister {
    public static function persist(
        $value,
        array $context = null): Responses\IResponse
    {
        $validationResponse = LoginSessionSourceValidator::validate($value);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $sessionId = $value["sessionId"];
        $userId = $value["userId"];

        $db = isset($context["database"]) ? $context["database"] : null;
        if (array_key_exists("database", $context)) {
            if (!($db instanceof PDO)) {
                $errorCode = "LoginSessionPersisterDatabaseInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        } else {
            $db = Getters\DatabaseGetter::get();
        }

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare("SELECT name FROM credentials WHERE id = :id");
        $sqlParams = [ ":id" => $userId, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "LoginSessionPersisterQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($error);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fetch) {
            $errorCode = "LoginSessionPersisterIdNotInCredentialsTable";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $userName = $fetch["name"];

        $salt = $value["salt"];

        $redis = isset($value["redis"]) ? $value["redis"] : null;
        if (!($redis instanceof RedisClient)) {
            $redis = Getters\RedisServerGetter::get();
        }

        $key = $sessionId;
        $value = [
            "userId" => $userId,
            "userName" => $userName,
            "salt" => $salt,
        ];

        $redis->hmset($key, $value);

        $key = "loginSession";
        $value = json_encode([
            "loginSessionId" => $sessionId,
            "salt" => $salt,
        ]);

        /* Cookies last 30 days. */
        $expire = time() + 60 * 60 * 24 * 30;
        $path = "/authorize";
        $domain = Getters\ServerDomainNameGetter::get();
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
            $errorCode = "LoginSessionPersisterUnpersistSessionIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (!$key or gettype($key) !== "string") {
            $errorCode = "LoginSessionPersisterUnpersistSessionIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $redis = isset($context["redis"]) ? $context["redis"] : null;
        if (!array_key_exists("redis", $context)) {
            $redis = Getters\RedisServerGetter::get();
        } else if (!($redis instanceof RedisClient)) {
            $errorCode = "LoginSessionPersisterUnpersistRedisClientInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
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