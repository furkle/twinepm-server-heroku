<?php
namespace TwinePM\Endpoints;

use Psr\Http\Message\ResponseInterface;
use Slim\ContainerInterface;
class AccountDeletionEndpoint extends AbstractEndpoint { 
    function __invoke(ContainerInterface $container): ResponseInterface {
        /* Collect all data owned by the user. */
        $getFromToken = $container->get("getFromToken");
        $token = $request->getHeader("Authorization")[0];
        $sqlAbstractionType = "credential";
        $credential = $getFromToken($sqlAbstractionType, $token);
        $authorizations = $credential->getAuthorizations();
        $account = $credential->getAccount();
        $ownedPackages = $account->getPackages();

        $db = $container->get("databaseClientWithExceptions");
        $db->beginTransaction();
        
        /* Give each owned package to secretaryBot. */
        foreach ($ownedPackages as $package) {
            $package->setOwnerId(1);
            $package->serializeToDatabase();
        }

        /* Remove each token entry. */
        foreach ($authorizations as $authorization) {
            $deleteResponse = $authorization->deleteFromDatabase();
        }

        /* Delete the account. */
        $account->deleteFromDatabase();

        /* Delete the credential. */
        $credential->deleteFromDatabase();

        $db->commit();

        $body = $container->get("responseBody");
        $successArray = $container->get("successArray");
        $successStr = json_encode($successArray);
        $body->write($successStr);
        $response = $container->get("response")->withBody($body);
        return $response;
    }
}