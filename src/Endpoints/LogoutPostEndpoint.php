<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Getters\LoggedInUserGetter;
use \TwinePM\Persisters\LoginSessionPersister;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
class LogoutPostEndpoint extends AbstractEndpoint {
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

        $user = LoggedInUserGetter::get($context);
        
        $ctx = [ "redis" => $redis, ];
        $unpersistResponse = LoginSessionPersister::unpersist($user, $ctx);
        if ($unpersistResponse->isError()) {
            return static::convertServerErrorToClientError($unpersistResponse);
        }

        $success = new Responses\Response();
        return $success;
    }
}