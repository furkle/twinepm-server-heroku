<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class PackageGetEndpoint extends AbstractEndpoint {
    function execute(ContainerInterface $container): ResponseInterface {
        $request = $container->get("request");
        $params = $request->getQueryParams();
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

        $dataLevel = null;
        if (array_key_exists("packageDataLevel", $params)) {
            $yesStrict = true;
            if (!in_array($dataLevel, Package::DATA_LEVELS, $yesStrict)) {
                $errorCode = "DataLevelInvalid";
                throw new UserRequestFieldInvalidException($errorCode);
            }

            $source["dataLevel"] = $params["packageDataLevel"];
        } else {
            $dataLevel = Package::DEFAULTS["packageDataLevel"];
        }

        $sqlAbstractionType = "package";
        $package = $getFromSource($sqlAbstractionType, $source);

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successArray["package"] = $package->toArray();
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        return $response;
    }
}