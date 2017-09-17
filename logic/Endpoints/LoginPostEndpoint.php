<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Getters;
use \TwinePM\Filters\IdFilter;
use \TwinePM\Persisters\LoginSessionPersister;
use \TwinePM\Validators;
use \TwinePM\SqlAbstractions\Credentials\Credential;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \Predis\Client as RedisClient;
use \PDO;
class LoginPostEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $params = $request->getParsedBody();
        $source = [];
        if (isset($params["name"])) {
            $name = $params["name"];
            $validationResponse = Validators\NameValidator::validate($name);
            if ($validationResponse->isError()) {
                return static::convertServerErrorToClientError(
                    $validationResponse);
            }

            $source["name"] = $name;
        } else if (isset($params["id"])) {
            $filterResponse = IdFilter::filter($params["id"]);
            if ($filterResponse->isError()) {
                return static::convertServerErrorToClientError(
                    $filterResponse);
            }

            $source["id"] = $filterResponse->filtered;
        } else {
            $errorCode = "LoginPostEndpointNoArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $password = isset($params["password"]) ? $params["password"] : null;
        if (!array_key_exists("password", $params)) {
            $errorCode = "LoginPostEndpointPasswordMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $container->get(PDO::class);
        $credentialResponse = Credential::get($source, $db);
        if ($credentialResponse->isError()) {
            return static::convertServerErrorToClientError(
                $credentialResponse);
        }

        $credential = $credentialResponse->credential;
        if (!password_verify($password, $credential->getHash())) {
            $errorCode = "LoginPostEndpointCredentialsInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $redis = $container->get(RedisClient::class);
        $context = [ "redis" => $redis, ];
        $session = [
            "sessionId" => Getters\SessionIdGetter::get(),
            "userId" => $credential->getId(),
            "userName" => $credential->getName(),
            "salt" => Getters\SaltGetter::get(),
        ];

        $persistResponse = LoginSessionPersister::persist($session, $context);
        if ($persistResponse->isError()) {
            return static::convertServerErrorToClientError($persistResponse);
        }

        $success = new Responses\Response();
        return $success;
    }
}