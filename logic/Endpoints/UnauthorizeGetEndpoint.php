<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Getters\LoggedInUserGetter;
use \TwinePM\Transformers\AuthorizationToTemplatingArrayTransformer;
use \TwinePM\SqlAbstractions\Authorizations\Authorization;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
use \PDO;
class UnauthorizeGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $context = [
            "request" => $request,
            "redis" => $container->get(RedisClient::class),
        ];

        $user = LoggedInUserGetter::get($context);
        if (!$user) {
            $errorCode = "LogoutGetEndpointNotLoggedIn";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $id = $user["id"];
        $db = $container->get(PDO::class);
        $authorizationsResponse = Authorization::getFromUserId($id, $db);

        if ($authorizationsResponse->isError()) {
            return static::convertServerErrorToClientError(
                $authorizationsResponse);
        }

        $authorizations = $authorizationsResponse->authorizations;

        $transformResponse =
            AuthorizationToTemplatingArrayTransformer::transform(
                $authorizations);

        if ($transformResponse->isError()) {
            return static::convertServerErrorToClientError($transformResponse);
        }

        $templatingAuths = $transformResponse->transformed;
        $templateVars = [
            "authorizations" => $templatingAuths,
            "loggedInUser" => $user,
        ];

        $success = new Responses\Response();
        $success->templateVars = $templateVars;
        return $success;
    }
}