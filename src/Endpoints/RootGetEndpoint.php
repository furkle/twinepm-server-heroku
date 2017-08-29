<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Getters;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
use \PDO;
class RootGetEndpoint extends AbstractEndpoint {
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