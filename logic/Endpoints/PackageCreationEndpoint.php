<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class PackageCreationEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $source = $request->getParsedBody();
        $package = $container->get("packageBuilder")($source);
        $package->serializeToDatabase();
        $packageId = $package->getId();

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