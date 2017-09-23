<?php
namespace TwinePM\SqlAbstractions\Packages;

use \TwinePM\SqlAbstractions\AbstractSqlAbstraction;
use \TwinePM\Responses;
use \TwinePM\Getters;
use \TwinePM\Filters\IdFilter;
use \TwinePM\Validators;
use \TwinePM\SqlAbstractions\Versions\Version;
use \TwinePM\SqlAbstractions\Accounts\Account;
use \PDO;
use \Exception;
class Package extends AbstractSqlAbstraction implements IPackage {
    private $id;
    private $name;
    private $authorId;
    private $ownerId;
    private $description;
    private $homepage;
    private $type;
    private $currentVersion;
    private $timeCreated;
    private $keywords;
    private $tag;
    private $errorCode;
    private $errorData;
    private $database;

    public static function get(
        array $source,
        PDO $database = null): Package
    {
        $db = $database ?? Getters\TwinepmDatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $package = null;
        $stmt = null;
        $sqlParams = [];
        $queryStr = "SELECT id, name, author_id, owner_id, description, " .
                "homepage, type, current_version, time_created, keywords, " .
                "tag " .
            "FROM packages " .
            "ORDER BY version ";
        $id = isset($source["id"]) ? $source["id"] : null;
        if (array_key_exists("name", $params)) {
            /* Throws exception if invalid. */
            $validateName($params["name"]);
        } else if (array_key_exists("id", $params)) {
            /* Throws exception if invalid. */
            $filterId($params["id"]);
        } else if (array_key_exists("nameOrId", $params)) {
            try {
                /* Throws exception if invalid. */
                $filtered = $filterId($params["nameOrId"]);
            } catch (Exception $e) {
                try {
                    /* Throws exception if invalid. */
                    $validateName($params["nameOrId"]);
                } catch (Exception $e) {
                    $errorCode = "NameOrIdInvalid";
                    throw new RequestFieldInvalidException($errorCode);
                }
            }
        } else {
            $errorCode = "LoginPostEndpointNameAndIdInvalid";
            throw new RequestFieldInvalidException($errorCode);
        }

        $stmt = $db->prepare($queryString);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "PackageGetQueryFailed";
            throw new PersistenceFailedException($errorCode);
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $src = static::convertFetchToSource($fetch);
        return new self($src, $db);
    }

    public static function getFromToken(
        string $token,
        PDO $database): array
    {
        $errmodeKey = $database::ATTR_ERRMODE;
        $errmodeValue = $database::ERRMODE_EXCEPTION;
        $db->setAttribute($errmodeKey, $errmodeValue);

        $stmt = $db->prepare(
            "SELECT P.id, P.name, P.author_id, P.owner_id, P.description, " .
                "P.homepage, P.type, P.current_version, P.time_created, " .
                "P.keywords, P.tag " .
            "FROM packages P " .
            "LEFT JOIN authorizations A " .
            "ON P.id = A.id " .
            "WHERE A.oauth_token = :oAuthToken");

        $sqlParams = [ ":oAuthToken" => $token, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "PackageGetFromTokenQueryFailed";
            $data = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $data);
            return $error;
        }

        $packages = [];
        $fetchAll = $stmt->fetchAll($database::FETCH_ASSOC);
        foreach ($fetchAll as $fetch) {
            $source = Package::convertFetchToSource($fetch);
            $package = new Package($source, $db);
            $packages[] = $package;
        }

        $success = new Responses\Response();
        $success->packages = $packages;
        return $success;
    }

    public static function convertFetchToSource(array $fetch): array {
        $source = [
            "id" => $fetch["id"],
            "name" => $fetch["name"],
            "authorId" => $fetch["author_id"],
            "ownerId" => $fetch["owner_id"],
            "description" => $fetch["description"],
            "homepage" => $fetch["homepage"],
            "type" => $fetch["type"],
            "currentVersion" => $fetch["current_version"],
            "timeCreated" => strtotime($fetch["time_created"]),
            "keywords" => json_decode($fetch["keywords"]),
            "tag" => $fetch["tag"],
        ];

        return $source;
    }

    public function __construct(array $source, PDO $database = null) {
        $validationResponse = Validators\PackageSourceValidator::validate(
            $source);

        if ($validationResponse->isError()) {
            $e = $validationResponse->getError();
            $this->errorCode = isset($e->errorCode) ? $e->errorCode : null;
            $this->errorData = isset($e->errorData) ? $e->errorData : null;
            return;
        }

        $this->id = isset($source["id"]) ? $source["id"] : null;
        $this->name = $source["name"];
        $this->authorId = $source["authorId"];
        $this->ownerId = $source["ownerId"];
        $this->description = $source["description"];
        $this->homepage = isset($source["homepage"]) ?
            $source["homepage"] : "";
        $this->type = $source["type"];
        $this->currentVersion = isset($source["currentVersion"]) ?
            $source["currentVersion"] : null;
        $this->timeCreated = isset($source["timeCreated"]) ?
            $source["timeCreated"] : null;
        $this->keywords = isset($source["keywords"]) ?
            $source["keywords"] : [];
        $this->tag = isset($source["tag"]) ? $source["tag"] : "";

        $this->database = $database ?? Getters\DatabaseGetter::get();
    }

    /* Public get, private set. */
    public function __get(string $propName) {
        return isset($this->{$propName}) ? $this->{$propName} : null;
    }

    public function getOwner(): Responses\IResponse {
        $source = [ "id" => $this->id, ];
        $accountResponse = Account::get($source, $this->database);
        return $accountResponse;
    }

    public function getVersions(string $dataLevel): Responses\IResponse {
        if (!is_array($dataLevel, Version::DATA_LEVELS, $yesStrict)) {
            $errorCode = "PackageGetVersionsDataLevelInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $errorCode;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT package_id, global_version_id, description, " .
                "homepage, version, author_id, time_created, name " .
                ($dataLevel === "full" ? "js, css, " : "") .
            "FROM versions " .
            "WHERE package_id = :packageId " .
            "ORDER BY package_id");

        $sqlParams = [ ":packageId" => $this->id, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "PackageGetVersionsQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $versions = [];
        foreach ($fetchAll as $fetch) {
            $source = Version::convertFetchToSource($fetch);
            $version = new Version($source, $db);
            if ($version->isError()) {
                $error = $version->getError();
                return $error;
            }

            $versions[] = $version;
        }

        $success = new Responses\Response();
        $success->versions = $versions;
        return $success;
    }

    public function getCurrentVersionObject(): Responses\IResponse {
        $id = $this->id;
        $currentVersion = $this->currentVersion;
        $source = [
            "packageId" => $id,
            "version" => $currentVersion,
        ];

        $currentVersionResponse = Version::get($id, $currentVersion);
        return $currentVersionResponse;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getOwnerId(): int {
        $ownerId = ;
        return $this->ownerId;
    }

    public function setOwnerId(int $id): Responses\IResponse {
        $source = [ "id" => $id, ];
        $filterResponse = Filters\IdFilter::validate($source);
        if ($filterResponse->isError()) {
            return $filterResponse;
        }

        $this->ownerId = $filterResponse->filtered;

        $success = new Responses\Response();
        return $success;
    }

    public function getAuthorId(): int {
        return $this->authorId;
    }

    public function getName(): string {
        return $this->name;
    }

    public function setName(string $name): Responses\IResponse {
        $source = [ "name" => $name, ];
        $validationResponse = Validators\NameValidator::validate($source);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $this->name = $name;

        $success = Responses\Response();
        return $success;
    }

    public function getType(): string {
        return $this->type;
    }

    public function setType(string $type): Responses\IResponse {
        $yesStrict = true;
        if (!in_array($type, self::TYPES, $yesStrict)) {
            $errorCode = "PackageSetTypeTypeInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->type = $type;

        $success = new Responses\Response();
        return $success;
    }

    public function getCurrentVersion(): string {
        return $this->currentVersion;
    }

    public function setCurrentVersion(
        string $currentVersion): Responses\IResponse
    {
        if (!$currentVersion) {
            $errorCode = "PackageSetCurrentVersionVersionEmpty";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->currentVersion = $currentVersion;

        $success = new Responses\Response();
        return $success;
    }

    public function getDescription(): string {
        return $this->description;
    }

    public function setDescription(string $description): Responses\IResponse {
        if (!$description) {
            $errorCode = "PackageSetDescriptionDescriptionEmpty";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->description = $description;

        $success = new Responses\Response();
        return $success;
    }

    public function getHomepage(): string {
        return $this->homepage;
    }

    public function setHomepage(string $homepage): Responses\IResponse {
        $this->homepage = $homepage;

        $response = new Responses\Response();
        return $response;
    }

    public function getKeywords(): array {
        return $this->keywords;
    }

    public function setKeywords(array $keywords): Responses\IResponse {
        foreach ($keywords as $value) {
            if (gettype($value) !== "string") {
                $errorCode = "PackageSetKeywordsKeywordInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!$value) {
                $errorCode = "PackageSetKeywordsKeywordEmpty";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        }

        $this->keywords = $keywords;

        $response = new Responses\Response();
        return $response;
    }

    public function getTag(): string {
        return $this->tag;
    }

    public function setTag(string $tag): Responses\IResponse {
        $this->tag = $tag;

        $response = new Responses\Response($status);
        return $response;
    }

    public function getTimeCreated(): ?int {
        return $this->timeCreated;
    }

    public function isInDatabase(): bool {
        if ($this->id === null) {
            return false;
        }

        $queryString = "SELECT EXISTS(" .
            "SELECT id " .
            "FROM packages " .
            "WHERE id = :package_id" .
        ")";
        
        $sqlParams = [
            ":package_id" => $this->id,
        ];

        $stmt = $this->database->prepare($queryString);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "PackageIsInDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            die(json_encode($error->getOutput()));
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        $exists = $fetch[0];
        return $exists;
    }

    public function serializeToDatabase(): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $queryStr = null;
        $sqlParams = [
            ":name" => $this->name,
            ":ownerId" => $this->ownerId,
            ":description" => $this->description,
            ":homepage" => $this->homepage,
            ":type" => $this->type,
            ":currentVersion" => $this->currentVersion,
            ":keywords" => json_encode($this->keywords),
            ":tag" => $this->tag,
        ];

        if ($this->id === null) {
            $queryStr = "INSERT INTO packages (" .
                    "name, owner_id, description, homepage, " .
                    "type, current_version, keywords, tag, author_id,  " .
                ")" .
                "VALUES (" .
                    ":name, :ownerId, :description, :homepage, " .
                    ":type, :currentVersion, :keywords, :tag, :authorId" .
                ")" .
                "RETURNING id";
            $sqlParams[":authorId"] = $this->authorId;
        } else {
            $queryStr = "UPDATE packages " .
                "SET name = :name, owner_id = :ownerId, " .
                    "description = :description, homepage = :homepage, " .
                    "type = :type, current_version = :currentVersion, " .
                    "keywords = :keywords, tag = :tag " .
                "WHERE id = :id";
            $sqlParams[":id"] = $this->id;
        }

        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "PackageSerializeToDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];

            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "PackageSerializeToDatabasePackageDoesNotExist";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if ($this->id === null) {
            $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
            $this->id = $fetch["id"];
        }

        $success = new Responses\Response();
        return $success;
    }

    public function updateFromDatabase(): Responses\IResponse {
        $source = [ "id" => $this->id, ];
        $package = new Package($source, $this->database);
        if ($package->isError()) {
            $error = $package->getError();
            return $error;
        }

        $array = $package->toArray();
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }

        $success = new Responses\Response();
        return $success;
    }

    public function deleteFromDatabase(): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("DELETE FROM packages WHERE id = :id");
        $sqlParams = [ ":id" => $this->id, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "PackageDeleteFromDatabaseQueryFailed";
            $errorData = [ "exception" => $e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "PackageDeleteFromDatabaseAccountDoesNotExist";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->id = null;
        $success = new Responses\Response($status);
        return $success;
    }
}
?>