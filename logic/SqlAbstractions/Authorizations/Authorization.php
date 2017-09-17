<?php
namespace TwinePM\SqlAbstractions\Authorizations;

use \TwinePM\SqlAbstractions\AbstractSqlAbstraction;
use \TwinePM\Responses;
use \TwinePM\Getters;
use \TwinePM\Validators;
use \TwinePM\Filters;
use \TwinePM\Transformers;
use \TwinePM\SqlAbstractions\Credentials;
use \TwinePM\SqlAbstractions\Accounts;
use \TwinePM\OAuth2\Repositories\ClientRepository;
use \TwinePM\OAuth2\Entities\ClientEntity;
use \Lcobucci\JWT\Parser;
use \Lcobucci\JWT\Parsing;
use \PDO;
use \Exception;
class Authorization extends AbstractSqlAbstraction implements IAuthorization {
    private $id;
    private $name;
    private $hash;
    private $validated;
    private $database;
    private $errorCode;
    private $errorInfo;
    private $errorData;

    public static function get(
        array $source,
        PDO $database = null): Responses\IResponse
    {
        $globalAuthorizationId = isset($source["globalAuthorizationId"]) ?
            $source["globalAuthorizationId"] : null;
        if (!$globalAuthorizationId) {
            $errorCode = "AuthorizationGetNoArgument";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT global_authorization_id, user_id, client, scopes, ip, " .
                "oauth_token, time_created " .
            "FROM authorizations " .
            "WHERE global_authorization_id = :globalAuthorizationId");
        
        $sqlParams = [ ":globalAuthorizationId" => $globalAuthorizationId, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AuthorizationGetQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$fetch) {
            $errorCode = "AuthorizationGetQueryNoResults";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $source = static::convertFetchToSource($fetch);
        $authorization = new Authorization($source, $db);

        $success = new Responses\Response();
        $success->authorization = $authorization;
        return $success;
    }

    public static function getFromToken(
        string $token,
        PDO $database = null): Responses\IResponse
    {
        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT global_authorization_id, user_id, client, scopes, ip, " .
                "oauth_token, time_created " .
            "FROM credentials " .
            "WHERE oauth_token = :oAuthToken");

        $sqlParams = [ ":oAuthToken" => $token, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AuthorizationGetFromTokenQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "AuthorizationGetFromTokenNoResult";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $source = static::convertFetchToSource($fetch);
        $authorization = new Authorization($source, $db);
        if ($authorization->isError()) {
            $error = $authorization->getError();
            return $error;
        }

        $success = new Responses\Response();
        $success->authorization = $authorization;
        return $success;
    }

    public static function getFromUserId(
        int $userId,
        PDO $database = null): Responses\IResponse
    {
        $filterResponse = Filters\IdFilter::filter($userId);
        if ($filterResponse->isError()) {
            return $filterResponse;
        }

        $userId = $filterResponse->filtered;

        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $stmt = $db->prepare(
            "SELECT global_authorization_id, user_id, client, scopes, ip, " .
                "oauth_token, time_created " .
            "FROM authorizations " .
            "WHERE user_id = :userId");

        $sqlParams = [ ":userId" => $userId, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AuthorizationGetFromUserIdQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $authorizations = [];
        foreach ($fetchAll as $fetch) {
            $source = static::convertFetchToSource($fetch);
            $authorization = new Authorization($source, $db);
            if ($authorization->isError()) {
                return $authorization->getError();
            }

            $authorizations[] = $authorization;
        }

        $success = new Responses\Response();
        $success->authorizations = $authorizations;
        return $success;
    }

    public static function convertFetchToSource(array $fetch): array {
        $yesAssoc = true;
        $source = [
            "globalAuthorizationId" => $fetch["global_authorization_id"],
            "userId" => $fetch["user_id"],
            "client" => $fetch["client"],
            "scopes" => json_decode($fetch["scopes"], $yesAssoc),
            "ip" => $fetch["ip"],
            "oAuthToken" => $fetch["oauth_token"],
            "timeCreated" => strtotime($fetch["time_created"]),
        ];

        return $source;
    }

    public function __construct(array $source, PDO $database = null) {
        $validationResponse =
            Validators\AuthorizationSourceValidator::validate(
                $source);

        if ($validationResponse->isError()) {
            $this->errorCode = isset($validationResponse->errorCode) ?
                $validationResponse->errorCode :
                "NoCodeProvided";
            $this->errorData = isset($validationResponse->errorData) ?
                $validationResponse->errorData : null;
            return;
        }

        $this->globalAuthorizationId =
            isset($source["globalAuthorizationId"]) ?
                $source["globalAuthorizationId"] : null;
        $this->userId = $source["userId"];
        $this->client = $source["client"];
        $this->scopes = $source["scopes"];
        $this->ip = $source["ip"];
        $this->oAuthToken = $source["oAuthToken"];
        $this->timeCreated = isset($source["timeCreated"]) ?
            $source["timeCreated"] : null;

        $db = $database ?? Getters\DatabaseGetter::get();
        $this->database = $db;
    }

    /* Public get, private set. */
    public function __get(string $propName) {
        return isset($this->{$propName}) ? $this->{$propName} : null;
    }

    public function getCredential(): Responses\IResponse {
        $src = [ "id" => $this->userId, ];
        $db = $this->database;
        $credentialResponse = Credentials\Credential::get($src, $db);
        return $credentialResponse;
    }

    public function getAccount(): Responses\IResponse {
        $src = [ "id" => $this->userId, ];
        $accountResponse = Accounts\Account::get($src, $this->database);
        return $accountResponse;
    }

    public function getClientObject(): ?array {
        $clients = (new ClientRepository())->getClients();
        if (array_key_exists($this->client, $clients)) {
            return null;
        }

        $client = $clients[$this->client];
        
        /* Don't pass client secrets around. */
        unset($client["secret"]);
        
        return $client;
    }

    public function updateFromDatabase(): Responses\IResponse {
        if (!isset($this->globalAuthorizationId)) {
            $errorCode =
                "AuthorizationUpdateFromDatabaseGlobalAuthorizationIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $source = [ "globalAuthorizationId" => $this->globalAuthorizationId, ];
        $authorizationResponse = static::get(
            $source,
            $this->database);

        if ($authorizationResponse->isError()) {
            return $authorizationResponse;
        }

        $authorization = $authorizationResponse->authorization;
        $authArray = $tokensAndUserId->toArray();
        foreach ($authArray as $key => $value) {
            $this->{$key} = $value;
        }

        $success = new Responses\Response();
        return $success;
    }

    public function deleteFromDatabase(): Responses\IResponse {
        if (!isset($this->globalAuthorizationId)) {
            $errorCode =
                "AuthorizationDeleteFromDatabaseGlobalAuthorizationIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "DELETE FROM authorizations " .
            "WHERE global_authorization_id = :globalAuthorizationId");

        $sqlParams = [
            ":globalAuthorizationId" => $this->globalAuthorizationId,
        ];

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AuthorizationDeleteFromDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "AuthorizationsDeleteFromDatabaseIdNotInDatabase";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }

    public function serializeToDatabase(): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "INSERT INTO authorizations " .
            "(user_id, client, scopes, ip, oauth_token) " .
            "VALUES (:userId, :client, :scopes, :ip, :oAuthToken) " .
            "RETURNING global_authorization_id, time_created");

        $sqlParams = [
            ":userId" => $this->userId,
            ":client" => $this->client,
            ":scopes" => json_encode($this->scopes),
            ":ip" => $this->ip,
            ":oAuthToken" => $this->oAuthToken,
        ];

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AuthorizationSerializeToDatabaseQueryFailed";
            $errorData = [ "exception" => $e, ];
            $response = new Responses\ErrorResponse($errorCode, $errorData);
            return $response;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->globalAuthorizationId = $fetch["global_authorization_id"];
        $this->timeCreated = strtotime($fetch["time_created"]);

        $success = new Responses\Response();
        return $success;
    }

    public function isInDatabase(): bool {
        if (!$this->globalAuthorizationId) {
            return false;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT EXISTS(" .
                "SELECT global_authorization_id " .
                "FROM authorizations " .
                "WHERE oauth_token = :oAuthToken" .
            ")");

        $sqlParams = [ ":oAuthToken" => $this->oAuthToken, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AuthorizationIsInDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            die(json_encode($error->getOutput()));
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        return $fetch[0];
    }

    public function getGlobalAuthorizationId(): ?int {
        return $this->globalAuthorizationId;
    }

    public function getUserId(): int {
        return $this->userId;
    }

    public function getClient(): string {
        return $this->client;
    }

    public function getScopes(): array {
        return $this->scopes;
    }

    public function getIp(): string {
        return $this->ip;
    }

    public function getOAuthToken(): string {
        return $this->oAuthToken;
    }

    public function getDecryptedOAuthToken(): JWT {
        $decoder = new Parsing\Decoder();
        $parser = new Parser($decoder);
        $jwt = $parser->parse($this->getOAuthToken());
    }

    public function getTimeCreated(): ?int {
        return $this->timeCreated;
    }
}
?>