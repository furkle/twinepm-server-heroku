<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Packages\Package;
use \TwinePM\Filters\IdFilter;
use \TwinePM\Validators;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
class PackageDeleteEndpoint extends AbstractEndpoint {
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

        $sqlAbstractionType = "package";
        $getFromSource = $container->get("getAbstractionFromSource");
        $package = $getFromSource($sqlAbstractionType, $source);
        $packageId = $package->get($package);

        $sqlAbstractionType = "credential";
        $token = $request->getHeader("Authorization")[0];
        $getFromToken = $container->get("getAbstractionFromToken");
        $credential = $getFromToken($sqlAbstractionType, $token);

        if ($package->getOwnerId() !== $credential->getId()) {
            $errorCode = "PackagePermissionError";
            throw new PermissionDeniedException($errorCode);
        }

        $package->deleteFromDatabase();

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successArray["packageId"] = $packageId;
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        $response->packageId = $packageId;
        return $response;
    }
}