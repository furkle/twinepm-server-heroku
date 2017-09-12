<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
class AccountCreationGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $templateVars = [];

        $success = new Responses\Response();
        $success->templateVars = $templateVars;
        return $success;
    }
}