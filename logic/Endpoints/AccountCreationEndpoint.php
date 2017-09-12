<?php
namespace TwinePM\Endpoints;

use \TwinePM\Getters;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Responses;
use \TwinePM\Transformers\PasswordToHashTransformer;
use \TwinePM\SqlAbstractions\Credentials\Credential;
use \TwinePM\SqlAbstractions\Accounts\Account;
use \TwinePM\Miscellaneous\Miscellaneous;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
use \Exception;
class AccountCreationEndpoint extends AbstractEndpoint { 
    public static function execute(
        IRequest $request,
        IContainer $container): Responses\IResponse
    {
        $source = $request->getParsedBody();

        $email = isset($source["email"]) ? $source["email"] : null;
        if (!$email) {
            $errorCode = "AccountCreationEndpointEmailInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $name = isset($source["name"]) ? $source["name"] : null;
        $password = isset($source["password"]) ?
            $source["password"] : null;

        $hashResponse = PasswordToHashTransformer::transform($password);

        if ($hashResponse->isError()) {
            $db->rollBack();
            $error = static::convertServerErrorToClientError($hashResponse);
            $error->respondAndDie();
        }

        $hash = $hashResponse->transformed;

        $db = $container->get(PDO::class);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        $src = [
            "name" => $name,
            "hash" => $hash,
        ];

        $credential = new Credential($src, $db);
        if ($credential->isError()) {
            $db->rollBack();
            $error = $credential->getError();
            return static::convertServerErrorToClientError($error);
        }

        $serializationResponse = $credential->serializeToDatabase();
        if ($serializationResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError(
                $serializationResponse);
        }

        $id = $credential->getId();
        $src = [
            "id" => $id,
            "name" => $name,
            "email" => $email,
        ];

        $account = new Account($src, $db);
        if ($account->isError()) {
            $db->rollBack();
            $error = $account->getError();
            return static::convertServerErrorToClientError($error);
        }

        $serializationResponse = $account->serializeToDatabase();
        if ($serializationResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError(
                $serializationResponse);
        }

        $stmt = $db->prepare(
            "INSERT INTO email_validation (user_id, token) " .
            "VALUES (:userId, :token)");
        $token = Getters\SessionIdGetter::get();
        $sqlParams = [
            ":userId" => $id,
            ":token" => $token,
        ];

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $db->rollBack();

            $errorCode =
                "AccountCreateEndpointEmailValidationInsertQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $db->commit();

        $address = $source["email"];
        $title = "Validate TwinePM E-mail";
        $body = "Please follow this link to activate your account:<br>" .
            "<a href='https://furkleindustries.com/twinepm/validateEmail/" .
            "?id=$id&token=$token'>Activate</a>";
        $sender = "no-reply@furkleindustries.com";
        Miscellaneous::sendMail($address, $title, $body, $sender);

        $success = new Responses\Response();
        return $success;
    }
}