<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
use TwinePM\Exception\NoModificationPerformedException;
class AccountUpdateEndpoint extends AbstractEndpoint {
    function __invoke(ContainerInterface $container): ResponseInterface {
        $request = $container->get("request");
        $source = $request->getParsedBody();
        $token = $container->get("authorizationToken");
        $getFromToken = $container->get("getFromToken");
        $sqlAbstractionType = "account";
        $account = $getFromToken($sqlAbstractionType, $token);

        $foundField = false;
        $changedName = false;
        if (array_key_exists("name", $source) and
            $source["name"] !== $account->getName())
        {
            $changedName = true;
            $foundField = true;
            $account->setName($source["name"]);
        }

        if (array_key_exists("nameVisible", $source) and
            $source["nameVisible"] !== $account->getNameVisible())
        {
            $foundField = true;
            $account->setNameVisible($source["nameVisible"]);
        }

        if (array_key_exists("description", $source) and
            $source["description"] !== $account->getDescription())
        {
            $foundField = true;
            $account->setDescription($source["description"]);
        }

        $acctTimeCreatedVisible = $account->getTimeCreatedVisible();
        if (array_key_exists("timeCreatedVisible", $source) and
            $source["timeCreatedVisible"] !== $acctTimeCreatedVisible)
        {
            $foundField = true;
            $account->setTimeCreatedVisible($source["timeCreatedVisible"]);
        }

        if (array_key_exists("email", $source) and
            $source["email"] !== $account->getEmail())
        {
            $foundField = true;
            $account->setEmail($source["email"]);
        }

        if (array_key_exists("emailVisible", $source) and
            $source["emailVisible"] !== $account->getEmailVisible())
        {
            $foundField = true;
            $account->setEmailVisible($source["description"]);
        }

        if (array_key_exists("dateStyle", $source) and
            $source["dateStyle"] !== $account->getDateStyle())
        {
            $foundField = true;
            $account->setDateStyle($source["dateStyle"]);
        }

        if (array_key_exists("timeStyle", $source) and
            $source["timeStyle"] !== $account->getTimeStyle())
        {
            $foundField = true;
            $account->setTimeStyle($source["timeStyle"]);
        }

        if (array_key_exists("homepage", $source) and
            $source["homepage"] !== $account->getHomepage())
        {
            $foundField = true;
            $account->setHomepage($source["homepage"]);
        }

        if (!$foundField) {
            throw new NoModificationPerformedException();
        }

        /* Perform raw query to prevent needing to look up the whole
         * credential. */
        $db = $container->get("databaseClientWithExceptions");
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

            $stmt->execute($sqlParams);
        }

        $account->serializeToDatabase();
        $db->commit();

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successArray["account"] = $account;
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        return $response;
    }
}