<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Psr\Container\ContainerInterface;
use TwinePM\Exceptions\UserRequestFieldInvalidException;
class PackagesSearchEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $request = $container->get("request");
        $source = $request->getQueryParams();
        $query = isset($source["query"]) ? $source["query"] : null;
        if (!array_key_exists("query", $source)) {
            $errorCode = "QueryInvalid";
            throw new UserRequestFieldInvalidException($errorCode);
        }

        $queryType = "packages";
        $results = $container->get("searchQuery")($queryType, $query);

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successArray["results"] = $results
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        return $response;
    }
}