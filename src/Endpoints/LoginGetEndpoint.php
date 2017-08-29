<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Getters\LoggedInUserGetter;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
use \PDO;
class LoginGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $context = [
            "request" => $request,
            "redis" => $container->get(RedisClient::class),
        ];

        $loggedInUser = LoggedInUserGetter::get($context);
        $templateVars = [ "loggedInUser" => $loggedInUser, ];

        $success = new Responses\Response();
        $success->templateVars = $templateVars;
        return $success;
    }
}