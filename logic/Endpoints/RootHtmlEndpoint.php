<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface as IContainer;
class RootHtmlEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $success = $container->get("response");
        $success->templateVars = [];
        return $success;
    }
}