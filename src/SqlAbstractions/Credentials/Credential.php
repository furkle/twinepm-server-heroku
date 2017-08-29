<?php
namespace TwinePM\SqlAbstractions\Credentials;

use \TwinePM\SqlAbstractions\AbstractSqlAbstraction;
use \TwinePM\Responses;
use \TwinePM\Getters;
use \TwinePM\Validators;
use \TwinePM\Filters\IdFilter;
use \TwinePM\Transformers;
use \TwinePM\SqlAbstractions\Accounts;
use \TwinePM\SqlAbstractions\TokensAndUserIds;
use \PDO;
use \Exception;
class Credential extends AbstractSqlAbstraction implements ICredential {
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
        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $queryStr = "SELECT id, name, hash, validated " .
            "FROM credentials " .
            "WHERE ";
        $sqlParams = [];
        $id = isset($source["id"]) ? $source["id"] : null;
        $name = isset($source["name"]) ? $source["name"] : null;
        if (isset($source["id"])) {     
            $filterResponse = IdFilter::filter($id);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }

            $queryStr .= "id = :id";
            $sqlParams[":id"] = $filterResponse->filtered;
        } else if (isset($source["name"])) {
            $validationResponse = Validators\NameValidator::validate($name);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }

            $queryStr .= "name = :name";
            $sqlParams[":name"] = $name;
        } else {
            $errorCode = "CredentialGetNoArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "CredentialGetQueryFailed";
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "CredentialGetQueryNoResults";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $credential = new Credential($fetch, $db);
        if ($credential->isError()) {
            $error = $credential->getError();
            return $error;
        }

        $success = new Responses\Response();
        $success->credential = $credential;
        return $success;
    }

    public static function getFromToken(
        string $token,
        PDO $database = null): Responses\IResponse
    {
        $db = $database ?? Getters\TwinepmDatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT C.id, C.name, C.hash, C.validated " .
            "FROM credentials C " .
            "LEFT JOIN authorizations A " .
            "ON C.id = A.id " .
            "WHERE T.oauth_token = :oAuthToken");

        $sqlParams = [ ":oAuthToken" => $token, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "CredentialGetFromTokenQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "CredentialGetFromTokenNoResult";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $credential = new Credential($fetch, $db);
        if ($credential->isError()) {
            $error = $credential->getError();
            return $error;
        }

        $success = new Responses\Response();
        $success->credential = $credential;
        return $success;
    }

    public static function convertFetchToSource(array $fetch): array {
        $source = [
            "id" => $fetch["id"],
            "name" => $fetch["name"],
            "hash" => $fetch["hash"],
            "validated" => $fetch["validated"],
        ];

        return $source;
    }

    public function __construct(array $source, PDO $database = null) {
        $validationResponse = Validators\CredentialSourceValidator::validate(
            $source);

        if ($validationResponse->isError()) {
            $this->errorCode = isset($validationResponse->errorCode) ?
                $validationResponse->errorCode :
                "NoCodeProvided";
            $this->errorData = isset($validationResponse->errorData) ?
                $validationResponse->errorData : null;
            return;
        }

        $this->id = isset($source["id"]) ? $source["id"] : null;
        $this->name = isset($source["name"]) ? $source["name"] : null;
        $this->hash = $source["hash"];
        $this->validated = isset($source["validated"]) ?
            $source["validated"] : false;

        $db = $database ?? Getters\DatabaseGetter::get();
        $this->database = $db;
    }

    /* Public get, private set. */
    public function __get(string $propName) {
        return isset($this->{$propName}) ? $this->{$propName} : null;
    }

    public function getAccount(): Responses\IResponse {
        $id = $this->id;
        if ($id === null) {
            $errorCode = "CredentialGetAccountIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $src = [ "id" => $id, ];
        $accountResponse = Accounts\Account::get($src, $this->database);
        return $accountResponse;
    }

    public function updateFromDatabase(): Responses\IResponse {
        if ($this->id === null) {
            $errorCode = "CredentialUpdateFromDatabaseIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $this->id;
        $source = [ "id" => $id, ];
        $credentialResponse = static::get($source, $db);
        if ($credentialResponse->isError()) {
            return $credentialResponse;
        }

        $credentialArr = $credentialResponse->credential->toArray();
        foreach ($credentialArr as $key => $value) {
            $this->{$key} = $value;
        }

        $success = new Responses\Response($status);
        return $success;
    }

    public function deleteFromDatabase(): Responses\IResponse {
        if ($this->id === null) {
            $errorCode = "CredentialDeleteFromDatabaseIdMissing";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $id = $this->id;

        $stmt = $db->prepare("DELETE FROM credentials WHERE id = :id");
        $sqlParams = [ ":id" => $id, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "CredentialDeleteFromDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "CredentialDeleteFromDatabaseIdNotInDatabase";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->id = null;

        $success = new Responses\Response();
        return $success;
    }

    public function serializeToDatabase(): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlParams = [
            ":name" => $this->name,
            ":hash" => $this->hash,
        ];

        $stmt = null;
        if ($this->id === null) {
            $stmt = $db->prepare(
                "INSERT INTO credentials (name, hash) " .
                "VALUES (:name, :hash) " .
                "RETURNING id");
        } else {
            $stmt = $db->prepare(
                "UPDATE credentials " .
                "SET name = :name, " .
                    "hash = :hash, " .
                    "validated = :validated " .
                "WHERE id = :id");
            $sqlParams[":validated"] = $this->validated;
            $sqlParams[":id"] = $this->id;
        }

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "CredentialSerializeToDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if ($this->id === null) {
            $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $fetch["id"];
        }

        $success = new Responses\Response();
        return $success;
    }

    public function resetPassword(string $cleartextPassword): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $source = [ "password" => $cleartextPassword, ];
        $hashResponse = Transformers\PasswordToHashTransformer::transform(
            $source);

        if ($hashResponse->isError()) {
            return $hashResponse;
        }

        $this->password = $hashResponse->hash;

        $success = new Responses\Response();
        return $success;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function setName(?string $name): Responses\IResponse {
        $validationResponse = Validators\NameValidator::validate($name);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $this->name = $name;

        $success = new Responses\Response();
        return $success;
    }

    public function getHash(): string {
        return $this->hash;
    }

    public function getValidated(): bool {
        return $this->validated;
    }

    public function setValidated(bool $validated): Responses\IResponse {
        $this->validated = $validated;

        $success = new Responses\Response();
        return $success;
    }

    public function isInDatabase(): bool {
        if ($this->id === null) {
            return false;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("SELECT EXISTS(" .
            "SELECT id FROM credentials WHERE id = :id" .
        ")");

        $sqlParams = [
            ":id" => $this->id,
        ];

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "CredentialIsInDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            die($error->toOutput());
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        return $fetch[0];
    }
}