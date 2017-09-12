<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Filters\IdFilter;
use \TwinePM\Packages\Package;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
public class PackageGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $dataLevel = null;
        if (isset($source["packageDataLevel"])) {
            $dataLevel = $source["packageDataLevel"];
            $yesStrict = true;
            if (!in_array($dataLevel, Package::DATA_LEVELS, $yesStrict)) {
                $errorCode = "PackageGetEndpointDataLevelInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        } else {
            $dataLevel = Package::DEFAULTS["packageDataLevel"];
        }

        if (!isset($source["id"]) and !isset($source["name"])) {
            $errorCode = ErrorInfo::PACKAGE_GET_ENDPOINT_ARGUMENTS_INVALID;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if (isset($source["id"])) {
            $filterResponse = IdFilter::validate($source["id"]);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }
        } else if (isset($source["name"])) {
            $validationResponse = Validators\NameValidator::validate(
                $source["name"]);

            if ($validResponse->isError()) {
                return $validResponse;
            }
        } else {
            $errorCode = "PackageGetEndpointNoValidArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $container->get(PDO::class);
        $package = Package::get($source, $db);
        if ($package->isError()) {
            $error = $package->getError();
            return $error;
        }

        $success = new Responses\Response();
        $success->package = $package;
        return $success;
    }
}
?>