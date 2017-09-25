<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class AccountCreationEndpoint extends AbstractEndpoint { 
    function execute(ContainerInterface $container): ResponseInterface {
        $request = $container->get("request");
        $source = $request->getParsedBody();

        /* Throws exception if invalid. */
        $container->get("validateAccountCreationSource")($source);

        $key = "passwordToHashTransformer";
        $passwordToHashTransformer = $container->get($key);
        $hash = $passwordToHashTransformer($password);

        $db = $container->get("databaseClientWithExceptions");
        $db->setAttribute($errmodeKey, $errmodeValue);
        $db->beginTransaction();

        $src = [
            "name" => $name,
            "hash" => $hash,
        ];

        $credential = $container->get("buildCredential")($src);
        $credential->serializeToDatabase();

        $id = $credential->getId();
        $src = [
            "id" => $id,
            "name" => $name,
            "email" => $email,
        ];

        $account = $container->get("buildAccount")($src);
        $account->serializeToDatabase();

        $db->commit();

        $requestId = $container->get("requestId");
        $array = [
            "userId" => $id,
            "salt" => $container->get("salt"),
        ];

        $cacheClient = $container->get("cacheClient");
        $cacheClient->HMSET($requestId, $array);

        $address = $source["email"];
        $title = "Validate TwinePM E-mail";
        $body = "Please follow this link to activate your account:<br>" .
            "<a href='https://furkleindustries.com/twinepm/validateEmail/" .
            "?id=$id&token=$token'>Activate</a>";
        $sender = "no-reply@furkleindustries.com";
        $container->get("sendMail")($address, $title, $body, $sender);

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        return $response;
    }
}