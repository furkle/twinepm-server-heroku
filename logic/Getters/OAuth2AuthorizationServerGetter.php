<?php
namespace TwinePM\Getters;

use \League\OAuth2\Server\AuthorizationServer;
use \League\OAuth2\Server\Grant\ImplicitGrant;
use \League\OAuth2\Server\CryptKey;
use \TwinePM\OAuth2\Repositories;
use \TwinePM\Responses;
use \DateInterval;
class OAuth2AuthorizationServerGetter implements IGetter {
    public static function get(array $context = null): AuthorizationServer {
        $pathOne = __DIR__ . "/../OAuth2/private.key";
        $pathTwo = "file://" . __DIR__ . "/../OAuth2/privatePassphrase.txt";
        $privateKey = new CryptKey($pathOne, $pathTwo);

        $clientRepository = new Repositories\ClientRepository();
        $scopeRepository = new Repositories\ScopeRepository();
        $accessTokenRepository = new Repositories\AccessTokenRepository(
            $privateKey);

        $pathThree = __DIR__ . "/../OAuth2/encryptionKey.txt"; 
        $encryptionKey = file_get_contents($pathThree);
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