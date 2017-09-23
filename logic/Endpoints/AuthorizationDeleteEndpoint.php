<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
use TwinePM\Exceptions\UserRequestFieldInvalidException;
class UnauthorizePostEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $request = $container->get("request");
        $source = $request->getParsedBody();
        $authorization = null;
        $sqlAbstractionType = "authorization";
        if (array_key_exists("globalAuthorizationId"], $source)) {
            $globalAuthorizationId = $source["globalAuthorizationId"];
            $id = $container->get("idFilter")($globalAuthorizationId);
            $key = "getAbstractionFromPrimaryKey";
            $getFromPrimaryKey = $container->get();
            $authorization = $getFromSource($sqlAbstractionType, $id);
        } else if (isset($source["oAuthToken"])) {
            $getFromToken = $container->get("getAbstractionFromToken");
            $authorization = $getFromToken($source["oAuthToken"]);
        } else {
            $errorCode = "IdentifierInvalid";
            throw new UserRequestFieldInvalidException($errorCode);
        }

        $authorization->deleteFromDatabase();


    }
}