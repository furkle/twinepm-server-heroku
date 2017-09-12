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
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $redis = $container->get(RedisClient::class);
        $context = [
            "request" => $request,
            "redis" => $redis,
        ];

        $currentUser = LoggedInUserGetter::get($context);
        $templatingAuths = [];
        if ($currentUser) {
            $id = $currentUser["id"];
            $db = $container->get(PDO::class);
            $authorizationsResponse = Authorization::getFromUserId($id, $db);
            if ($authorizationsResponse->isError()) {
                return static::convertServerErrorToClientError(
                    $authorizationsResponse);
            }

            $auths = $authorizationsResponse->authorizations;

            $transformResponse =
                AuthorizationToTemplatingArrayTransformer::transform($auths);

            if ($transformResponse->isError()) {
                return static::convertServerErrorToClientError($transformResponse);
            }

            $templatingAuths = $transformResponse->transformed;
        }
        
        $templateVars = [
            "loggedInUser" => $currentUser,
            "authorizations" => $templatingAuths,
        ];

        $success = new Responses\Response();
        $success->templateVars = $templateVars;
        return $success;
    }
}