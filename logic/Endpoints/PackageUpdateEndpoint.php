<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Packages\Package;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
use \Exception;
class PackageUpdateEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $source = $request->getParsedBody();
        $validationResponse = PackageSourceValidator::validate($source);
        if ($validationResponse->isError()) {
            return static::translateServerErrorToClientError($validationResponse);
        }

        $package = Package::get($source);
        foreach ($source as $key => $value) {
            try {
                /* e.g. if the provided key were "fooBar" an attempt would be 
                 * made to call $package->setFooBar($value). */
                $funcName = "set" . strtoupper($key[0]) . substr($key, 1);
                $userFuncArray = [
                    $package,
                    $funcName,
                ];

                call_user_func($userFuncArray, $value);
            } catch (Exception $e) {
                /* TODO: needs to be implemented */
                $errorCode = "NoCodeProvided";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        }


    }
}