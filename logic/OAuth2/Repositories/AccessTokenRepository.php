<?php
namespace TwinePM\OAuth2\Repositories;

use \League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use \League\OAuth2\Server\Entities\ClientEntityInterface;
use \League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;
use \League\OAuth2\Server\CryptKey;
use \TwinePM\OAuth2\Entities\AccessTokenEntity;
use \TwinePM\Getters\DatabaseGetter;
use \TwinePM\SqlAbstractions\Authorizations\Authorization;
class AccessTokenRepository implements AccessTokenRepositoryInterface {
    private $cryptKey;
    private $database;

    public function __construct(CryptKey $cryptKey, PDO $database = null) {
        $this->cryptKey = $cryptKey;
        $this->database = $database ?? DatabaseGetter::get();
    }

    public function persistNewAccessToken(
        AccessTokenEntityInterface $accessTokenEntity)
    {
        $ate = $accessTokenEntity;
        $jwt = $ate->convertToJWT($this->cryptKey);
        $jwtStr = (string)$jwt;

        $scopes = array_map(function ($scopeEntity) {
            return $scopeEntity->getIdentifier();
        }, $ate->getScopes());

        $source = [
            "userId" => $accessTokenEntity->getUserIdentifier(),
            "client" => $accessTokenEntity->getClient()->getIdentifier(),
            "oAuthToken" => $jwtStr,
            "scopes" => $scopes,
            "ip" => $_SERVER["REMOTE_ADDR"],
        ];

        $authorization = new Authorization($source, $this->database);
        if ($authorization->isError()) {
            $error = $authorization->getError();
            die(var_dump($error));
            // TODO: something
        }

        $serializeResponse = $authorization->serializeToDatabase();
        if ($serializeResponse->isError()) {
            // TODO: something
            return;
        }
    }

    public function revokeAccessToken($tokenId) {
        // Some logic here to revoke the access token
    }

    public function isAccessTokenRevoked($tokenId) {
        return false; // Access token hasn't been revoked
    }

    public function getNewToken(
        ClientEntityInterface $clientEntity,
        array $scopes,
        $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();
        $accessToken->setClient($clientEntity);
        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        $accessToken->setUserIdentifier($userIdentifier);
        return $accessToken;
    }
}