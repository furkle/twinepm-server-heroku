<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class LoginHtmlEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $response = $container->get("response");
        $response->templateVars = [
            "loggedInUser" => $container->get("loggedInUser"),
        ];

        return $response;
    }
}