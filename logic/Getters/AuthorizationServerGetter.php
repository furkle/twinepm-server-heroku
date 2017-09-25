<?php
namespace TwinePM\Getters;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\ImplicitGrant;
use League\OAuth2\Server\CryptKey;
use TwinePM\OAuth2\Repositories;
use TwinePM\Responses;
use DateInterval;
class OAuth2AuthorizationServerGetter implements IGetter {
    function __invoke(array $context = null): AuthorizationServer {
        $privateKey = new CryptKey(__DIR__ . "/../OAuth2/private.key");

        $clientRepository = new Repositories\ClientRepository();
        $scopeRepository = new Repositories\ScopeRepository();
        $accessTokenRepository = new Repositories\AccessTokenRepository(
            $privateKey);

        $encryptionKey = file_get_contents(__DIR__ . "/../OAuth2/encryptionKey");
        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $privateKey,
            $encryptionKey
        );

        $oneMonth = new DateInterval('P30D');
        $implicitGrant = new ImplicitGrant($oneMonth);
        $server->enableGrantType($implicitGrant, $oneMonth);
        return $server;
    }
}