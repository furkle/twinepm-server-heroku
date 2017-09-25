<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class ProfileReadEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface
    {
        $params = $request->getQueryParams();
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

        $profile = $container->get("getAbstractionFromSource")($source);
        $profileId = $profile->getId();

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successArray["profile"] = $profile->toArray();
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        $response->packageId = $profileId;
        return $response;
    }
}
