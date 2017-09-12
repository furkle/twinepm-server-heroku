<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Getters\LoggedInUserGetter;
use \TwinePM\OAuth2\Repositories\ClientRepository;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
class ClientsGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $clientRepo = new ClientRepository();
        $src = [ "request" => $request, ];
        $ctx = [ "redis" => $container->get(RedisClient::class), ];
        $templateVars = [
            "loggedInUser" => LoggedInUserGetter::get($src, $ctx),
            "clients" => $clientRepo->getClients(),
        ];

        $success = new Responses\Response();
        $success->templateVars = $templateVars;
        return $success;
    }
}