<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Packages\Package;
use \TwinePM\Getters\LoggedInUserGetter;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
class PackageCreationEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $db = $container->get(PDO::class);

        $source = $request->getParsedBody();
        $package = new Package($source, $db);
        if ($package->isError()) {
            $error = $package->getError();
            return static::convertServerErrorToClientError($error);
        }

        $serializeResponse = $package->serializeToDatabase();
        if ($serializeResponse->isError()) {
            return static::convertServerErrorToClientError($serializeResponse);
        }

        $success = new Responses\Response($status);
        $success->id = $serializeResponse->id;
        return $success;
    }
}