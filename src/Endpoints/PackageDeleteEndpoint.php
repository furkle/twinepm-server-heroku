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
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $params = $request->getParsedBody();

        $source = [];
        if (array_key_exists("id", $params)) {
            $filterResponse = IdFilter::filter($params["id"]);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }

            $source["id"] = $filterResponse->filtered;
        } else if (array_key_exists("name", $params)) {
            $source["name"] = $params["name"];
            $validationResponse = Validators\NameValidator::validate(
                $params["name"]);

            if ($validationResponse->isError()) {
                return $validationResponse;
            }
        } else {
            $errorCode = "PackageDeleteEndpointNoArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }
        
        $db = $container->get(PDO::class);
        $package = Package::get($source, $db);
        if ($package->isError()) {
            $error = $package->getError();
            return static::convertServerErrorToClientError($error);
        }

        $token = $request->getHeader("Authorization")[0];
        $credential = Credential::getFromToken($token, $db);
        if ($credential->isError()) {
            return $credential->getError();
        }

        if ($package->getOwnerId() !== $credential->getId()) {
            $errorCode = "PackageDeleteEndpointPackagePermissionError";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $deletionResponse = $package->deleteFromDatabase();
        if ($deletionResponse->isError()) {
            return static::convertServerErrorToClientError($deletionResponse);
        }

        $success = new Responses\Response();
        return $success;
    }
}