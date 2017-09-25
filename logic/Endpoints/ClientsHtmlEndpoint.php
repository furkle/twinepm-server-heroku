<?php
namespace TwinePM\Endpoints;

use TwinePM\OAuth2\Repositories\ClientRepository;
use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class ClientsReadEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $clientRepo = $container->get("oAuthClientRepository");

        $response = $container->get("response");
        $response->templateVars = [
            "loggedInUser" => $container->get("loggedInUser"),
            "clients" => $clientRepo->getClients(),
        ];

        return $response;
    }
}