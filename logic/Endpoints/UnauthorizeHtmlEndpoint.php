<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface as IContainer;
class UnauthorizeHtmlEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $user = $container->get("loggedInUser");
        $sqlAbstractionType = "authorization";
        $getFromUserId = $container->get("getAbstractionFromUserId");
        $authorizations = $getFromUserId($user["id"], $db);

        $key = "transformAuthorizationToTemplatingArray";
        $transformer = $container->get($key);
        $templatingAuths = $transform($authorizations);

        $response = $container->get("request");
        $response->templateVars = [
            "authorizations" => $templatingAuths,
            "loggedInUser" => $user,
        ];

        return $response;
    }
}