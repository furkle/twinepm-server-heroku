<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\SqlAbstractions\Profiles\Profile;
use \TwinePM\Filters\IdFilter;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
class ProfileGetEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $params = $request->getQueryParams();
        $source = [];
        if (isset($params["id"])) {
            $filterResponse = IdFilter::filter($params["id"]);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }

            $source["id"] = $filterResponse->filtered;
        } else if (isset($params["name"])) {
            $source["name"] = $params["name"];
        } else {
            $errorCode = "ProfileGetEndpointNoArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $container->get(PDO::class);
        $profileResponse = Profile::get($source, $db);
        if ($profileResponse->isError()) {
            return static::convertServerErrorToClientError($profileResponse);
        }

        $success = new Responses\Response();
        $success->profile = $profileResponse->profile;
        return $success;
    }
}
