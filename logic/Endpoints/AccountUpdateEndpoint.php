<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\SqlAbstractions\Accounts\Account;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
class AccountUpdateEndpoint extends AbstractEndpoint {
    public static function execute(
        IRequest $source,
        IContainer $container): Responses\IResponse
    {
        $token = $request->getHeader("Authorization")[0];

        $db = $container->get(PDO::class);
        $accountResponse = Account::getFromToken($token, $db);
        if ($accountResponse->isError()) {
            return static::convertServerErrorToClientError($accountResponse);
        }

        $account = $accountResponse->account;

        $foundField = false;
        $changedName = false;
        if (array_key_exists("name", $source)) {
            $foundField = true;
            $name = $source["name"];
            if ($name !== $account->getName()) {
                $changedName = true;
                $setResponse = $account->setName($name);
                if ($setResponse->isError()) {
                    $error = static::convertServerErrorToClientError(
                        $setResponse);
                }
            }
        }

        if (array_key_exists("nameVisible", $source)) {
            $foundField = true;
            $setResponse = $account->setNameVisible($source["nameVisible"]);
            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (array_key_exists("description", $source)) {
            $foundField = true;
            $setResponse = $account->setDescription($source["description"]);
            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (array_key_exists("timeCreatedVisible", $source)) {
            $foundField = true;
            $setResponse = $account->setTimeCreatedVisible(
                $source["timeCreatedVisible"]);

            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (array_key_exists("email", $source)) {
            $foundField = true;
            $setResponse = $account->setEmail($source["email"]);
            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (array_key_exists("emailVisible", $source)) {
            $foundField = true;
            $setResponse = $account->setEmailVisible($source["description"]);
            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (array_key_exists("dateStyle", $source)) {
            $foundField = true;
            $setResponse = $account->setDateStyle($source["dateStyle"]);
            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (array_key_exists("timeStyle", $source)) {
            $foundField = true;
            $setResponse = $account->setTimeStyle($source["timeStyle"]);
            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (array_key_exists("homepage", $source)) {
            $foundField = true;
            $setResponse = $account->setHomepage($source["homepage"]);
            if ($setResponse->isError()) {
                return static::convertServerErrorToClientError($setResponse);
            }
        }

        if (!$foundField) {
            $errorCode = "AccountUpdateEndpointNoUpdatedFields";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        if ($changedName) {
            $stmt = $db->prepare(
                "UPDATE credentials " .
                "SET name = :name " .
                "WHERE id = :id");

            $sqlParams = [
                ":name" => $account->getName(),
                ":id" => $account->getId(),
            ];

            try {
                $stmt->execute($sqlParams);
            } catch (Exception $e) {
                $db->rollBack();
                $errorCode = "AccountUpdateNameChangeQueryFailed";
                $errorData = [ "exception" => (string)$e, ];
                $error = new Responses\ErrorResponse($errorCode, $errorData);
                return $error;
            }
        }

        $serializeResponse = $account->serializeToDatabase();
        if ($serializeResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError($serializeResponse);
        }

        $db->commit();

        $response = new Responses\Response();
        return $response;
    }
}