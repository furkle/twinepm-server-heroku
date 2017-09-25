<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Getters\LoggedInUserGetter;
use \TwinePM\SqlAbstractions\Authorizations\Authorization;
use \TwinePM\Transformers\AuthorizationToTemplatingArrayTransformer;
use \Psr\Container\ContainerInterface as IContainer;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \League\OAuth2\Server\AuthorizationServer;
use \Predis\Client as RedisClient;
use \PDO;
class ServerUserOptionsGetEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface
    {
        $currentUser = $container->get("loggedInUser");
        $templatingAuths = [];
        if ($currentUser) {
            /* Throws exception if invalid. */
            $id = $container->get("filterId")($currentUser["id"]);
            $sqlAbstractionType = "authorization";
            $getFromUserId = $container->get("getAbstractionFromUserId");
            $authorizations = $getFromUserId($sqlAbstractionType, $id);
            $key = "transformAuthorizationToTemplatingArray";
            $transformer = $container->get($key);
            $templatingAuths = $transformer($auths);
        }

        $response = $container->get("response");
        $response->templateVars = [
            "loggedInUser" => $currentUser,
            "authorizations" => $templatingAuths,
        ];

        return $response;
    }
}