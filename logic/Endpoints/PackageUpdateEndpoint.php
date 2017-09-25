<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class PackageUpdateEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $request = $container->get("request");
        $source = $container->getParsedBody();

        /* Throws an exception if invalid. */
        $container->get("validatePackageSource")($source);

        $getFromSource = $container->get("getAbstractionFromSource");
        $sqlAbstractionType = "package";
        $package = $getFromSource($sqlAbstractionType, $source);
        $packageId = $package->getId();

        foreach ($source as $key => $value) {
            if ($key === "name") {
                $package->setName($value);
            } else if ($key === "description") {
                $package->setDescription($value);
            } else if ($key === "homepage") {
                $package->setHomepage($value);
            } else if ($key === "type") {
                $package->setType($value);
            } else if ($key === "currentVersion") {
                $package->setCurrentVersion($value);
            } else if ($key === "keywords") {
                $package->setKeywords($value);
            } else if ($key === "tag") {
                $package->setTag($value);
            }
        }

        $package->serializeToDatabase();

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successArray["package"] = $package->toArray();
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        $response->packageId = $packageId;
        return $response;

    }
}