<?php
namespace TwinePM\Endpoints;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Slim\Container\ContainerInterface;
use TwinePM\Exceptions\UserRequestFieldInvalidException;
class LoginPostEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $params = $request->getParsedBody();
        $source = [];
        if (array_key_exists("name", $params)) {
            /* Throws exception if invalid. */
            $container->get("validateName")($params["name"]);
            $source["name"] = $params["name"];
        } else if (array_key_exists("id", $params)) {
            /* Throws exception if invalid. */
            $source["id"] = $container->get("filterId")($params["id"]);
        } else if (array_key_exists("nameOrId", $params)) {
            try {
                $filtered = $container->get("filterId")($params["nameOrId"]);
                $source["id"] = $filtered;
            } catch (Exception $e) {
                try {
                    $validateName = $container->get("validateName");
                    /* Throws exception if invalid. */
                    $source["name"] = $validateName($params["nameOrId"]);
                } catch (Exception $e) {
                    $errorCode = "NameOrIdInvalid";
                    throw new RequestFieldInvalidException($errorCode);
                }
            }
        } else {
            $errorCode = "LoginPostEndpointNameAndIdInvalid";
            throw new RequestFieldInvalidException($errorCode);
        }

        $password = isset($params["password"]) ? $params["password"] : null;
        if (!$password) {
            $errorCode = "LoginPostEndpointInvalidPassword";
            throw new RequestFieldInvalidException($errorCode);
        }

        $getFromSource = $container->get("getFromSource");
        $sqlAbstractionType = "credential";
        $credential = $getFromSource($sqlAbstractionType, $source);
        if (!password_verify($password, $credential->getHash())) {
            $errorCode = "LoginPostEndpointCredentialsInvalid";
            throw new PermissionDeniedException($errorCode);
        }

        $userId = $credential->getId();

        $container->get("persistLoginSession")($userId, $userName);

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successArray["userId"] = $userId;
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        $response->userId = $userId;
        return $response;
    }
}