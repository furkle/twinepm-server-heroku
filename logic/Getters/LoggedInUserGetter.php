<?php
namespace TwinePM\Getters;

use \TwinePM\Filters\IdFilter;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
class LoggedInUserGetter implements IGetter {
    public static function get(array $context = null): ?array {
        $request = isset($context["request"]) ? $context["request"] : null;
        if (!($request instanceof IRequest)) {
            return null;
        }

        $redis = isset($context["redis"]) ? $context["redis"] : null;
        if (!$redis) {
            $redis = RedisServerGetter::get();
        }

        $cookieParams = $request->getCookieParams();
        $loginSessionStr = isset($cookieParams["loginSession"]) ?
            $cookieParams["loginSession"] :
            null;
        if ($loginSessionStr) {
            $yesAssoc = true;
            $loginSession = json_decode($loginSessionStr, $yesAssoc);
            $sessionId = isset($loginSession["loginSessionId"]) ?
                $loginSession["loginSessionId"] : null;
            if ($sessionId) {
                $serverSession = $redis->HGETALL($sessionId);
                if (isset($serverSession["salt"]) and
                    isset($loginSession["salt"]) and
                    $serverSession["salt"] === $loginSession["salt"])
                {
                    $userId = $serverSession["userId"];
                    $filterResponse = IdFilter::filter($userId);
                    if ($filterResponse->isError()) {
                        return null;
                    }

                    $id = $filterResponse->filtered;
                    unset($serverSession["userId"]);

                    $name = $serverSession["userName"];
                    unset($serverSession["userName"]);

                    $user = [
                        "sessionId" => $sessionId,
                        "id" => $id,
                        "name" => $name,
                        "salt" => $serverSession["salt"],
                    ];

                    $user = array_merge($serverSession, $user);
                    return $user;
                }
            }
        }

        return null;
    }
}