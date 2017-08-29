<?php
namespace TwinePM\Endpoints;

use \TwinePM\Getters;
use \TwinePM\Responses;
use \TwinePM\SqlAbstractions\Credentials\Credential;
use \Psr\Http\Message\ServerRequestInterface as IRequest;
use \Psr\Container\ContainerInterface as IContainer;
use \PDO;
class AccountDeletionEndpoint extends AbstractEndpoint { 
    public static function execute(
        IRequest $request,
        IContainer $container = null): Responses\IResponse
    {
        $db = $container->get(PDO::class);
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        $token = $request->getHeader("Authorization")[0];
        $credentialResponse = Credential::getFromToken($token, $db);
        if ($credentialResponse->isError()) {
            $db->rollBack();
            $error = static::convertServerErrorToClientError(
                $credentialResponse);
            $error->respondAndDie();
        }

        $credential = $credentialResponse->credential;

        $authorizationsResponse = $credential->getAuthorizations();
        if ($authorizationsResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError(
                $authorizationsResponse);
        }

        $authorizations = $authorizationsResponse->authorizations;

        $accountResponse = $credential->getAccount();
        if ($accountResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError($accountResponse);
        }

        $account = $accountResponse->account;

        $ownedPackagesResponse = $account->getPackages();
        if ($ownedPackagesResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError(
                $ownedPackagesResponse);
        }

        $packages = $ownedPackagesResponse->packages;
        
        /* Give each owned package to secretaryBot. */
        foreach ($packages as $package) {
            $setOwnerResponse = $package->setOwnerId(1);
            if ($setOwnerResponse->isError()) {
                $db->rollBack();
                return static::convertServerErrorToClientError(
                    $setOwnerResponse);
            }

            $serializeResponse = $package->serializeToDatabase();
            if ($serializeResponse->isError()) {
                $db->rollBack();
                return static::convertServerErrorToClientError(
                    $serializeResponse);
            }
        }

        /* Remove each token entry. */
        foreach ($authorizations as $authorization) {
            $deleteResponse = $authorization->deleteFromDatabase();
            if ($deleteResponse->isError()) {
                $db->rollBack();
                return static::convertServerErrorToClientError(
                    $deleteResponse);
            }
        }

        /* Delete the account. */
        $deleteResponse = $account->deleteFromDatabase();
        if ($deleteResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError(
                $deleteResponse);
        }

        /* Delete the credential. */
        $deleteResponse = $credential->deleteFromDatabase();
        if ($deleteResponse->isError()) {
            $db->rollBack();
            return static::convertServerErrorToClientError(
                $deleteResponse);
        }

        $db->commit();

        $success = new Responses\Response();
        $success->respondAndDie();
    }
}