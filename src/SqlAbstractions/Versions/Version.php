<?php
namespace TwinePM\SqlAbstractions\Versions;

use \TwinePM\SqlAbstractions\AbstractSqlAbstraction;
use \TwinePM\Responses;
use \TwinePM\Validators;
use \TwinePM\Filters\IdFilter;
use \TwinePM\SqlAbstractions\Packages\Package;
use \PDO;
use \Exception;
class Version extends AbstractSqlAbstraction implements IVersion {
    private $packageId;
    private $globalVersionId;
    private $name;
    private $version;
    private $authorId;
    private $js;
    private $css;
    private $keywords;
    private $timeCreated;
    private $description;
    private $homepage;
    private $tag;
    private $dataLevel;
    private $errorCode;
    private $errorData;
    private $database;

    public static function get(
        array $source,
        PDO $database = null): Responses\IResponse
    {
        $hasGlobalVersionId = isset($source["globalVersionId"]) and
            $source["globalVersionId"];
        $globalVersionId = isset($source["globalVersionId"]) ?
            $source["globalVersionId"] : null;
        $hasVersion = isset($source["version"]) and $source["version"];
        $version = isset($source["version"]) ? $source["version"] : null;
        if (!$hasGlobalVersionId and !$hasVersion) {
            $errorCode = "VersionGetNoValidArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        } else if ($hasVersion) {
            if (gettype($version) !== "string") {
                $errorCode = "VersionGetVersionInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (!$version) {
                $errorCode = "VersionGetVersionEmpty";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        }

        $yesStrict = true;
        $dataLevel = isset($source["dataLevel"]) ?
            $source["dataLevel"] : null;
        if (!in_array($dataLevel, static::DATA_LEVELS, $yesStrict)) {
            $errorCode = "VersionGetDataLevelInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $sqlParams = [];
        $queryStr = "SELECT id, global_version_id, name, version, owner_id, " .
                "author_id " . ($dataLevel === "full" ? "js, css, " : "") .
                "keywords, time_created, description, homepage, type, " .
            "FROM versions ";
        if (isset($source["globalVersionId"])) {
            $src = [ "id" => $globalVersionId, ];
            $filterResponse = IdFilter::validate($src);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }

            $queryStr .= "WHERE global_version_id = :globalVersionId";
            $sqlParams[":globalVersionId"] = $filterResponse->filtered;
        } else if (isset($source["packageId"])) {
            $packageId = $source["packageId"];
            $src = [ "id" => $packageId, ];
            $filterResponse = IdFilter::validate($src);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }

            $queryStr .= "WHERE id = :packageId AND version = :version";
            $sqlParams[":packageId"] = $filterResponse->filtered;
            $sqlParams[":version"] = $version;
        } else if (isset($source["name"])) {
            $validationResponse = Validators\NameValidator::validate($source);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }

            $queryStr .= "WHERE name = :packageName and version = :version";
            $version = $source["version"];
            $sqlParams[":packageName"] = $source["name"];
            $sqlParams[":version"] = $version;
        } else {
            $errorCode = "VersionGetNoValidArguments";
            $response = new Responses\ErrorResponse($errorCode);
            return $response;
        }

        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "VersionGetQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $response = new Responses\ErrorResponse($errorCode, $errorData);
            return $response;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $source = static::convertFetchToSource($fetch);
        $version = new Version($source, $db);

        $success = new Responses\Response();
        $success->package = $package;
        return $success;
    }

    public static function getFromToken(
        string $token,
        PDO $database = null): Responses\IResponse
    {
        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT V.package_id, V.global_version_id, V.name, " .
                ($dataLevel === "full" ? "V.js, V.css, " : "") .
                "V.description, V.homepage, V.version, V.author_id, " .
                "V.time_created "
            "FROM versions V " .
            "LEFT JOIN authorizations A " .
            "ON V.id = A.id " .
            "WHERE A.oauth_token = :oAuthToken");

        $sqlParams = [ ":oAuthToken" => $token, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "VersionGetFromTokenQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $versions = [];
        foreach ($fetchAll as $fetch) {
            $source = static::convertFetchToSource($fetch);
            $versions[] = new Version($source, $db);
        }

        $success = new Responses\Response();
        $success->versions = $versions;
        return $success;
    }

    public static function convertFetchToSource(array $fetch): array {
        $source = [
            "packageId" => $fetch["package_id"],
            "globalVersionId" => $fetch["global_version_id"],
            "name" => $fetch["name"],
            "type" => $fetch["type"],
            "version" => $fetch["version"],
            "description" => $fetch["description"],
            "homepage" => $fetch["homepage"],
            "js" => isset($fetch["js"]) ? $fetch["js"] : null,
            "css" => isset($fetch["css"]) ? $fetch["css"] : null,
            "keywords" => json_decode($fetch["keywords"]),
            "timeCreated" => $fetch["time_created"],
            "ownerId" => $fetch["owner_id"],
            "authorId" => $fetch["author_id"],
        ];

        return $source;
    }

    public function __construct(array $source, PDO $database = null) {
        $validationResponse = Validators\VersionSourceValidator::validate(
            $source);

        if ($validationResponse->isError()) {
            $error = $validationResponse->getError();
            $this->errorCode = $error->errorCode;
            $this->errorData = $error->errorData;
        }

        $packageId = $source["packageId"];
        $this->packageId = $packageId;

        $globalVersionId = isset($source["globalVersionId"]) ?
            $source["globalVersionId"] : null;
        $this->globalVersionId = $globalVersionId;

        $js = isset($source["js"]) ? $source["js"] : null;
        $this->js = $js;

        $css = isset($source["css"]) ? $source["css"] : null;
        $this->css = $css;

        $this->keywords = isset($source["keywords"]) ?
            $source["keywords"] : [];
        $this->description = $source["description"];
        $homepage = isset($source["homepage"]) ? $source["homepage"] : null;
        $this->homepage = $homepage;
        $this->authorId = $source["authorId"];
        $this->name = isset($source["name"]) ? $source["name"] : null;

        $this->database = $database ?? Getters\DatabaseGetter::get();
    }

    /* Public get, private set. */
    public function __get(string $propName) {
        return isset($this->{$propName}) ? $this->{$propName} : null;
    }

    public function getPackage(): Resposes\IResponse {
        $id = $this->packageId;
        $source = [ "id" => $id, ];
        $package = Package::get($source, $this->database);
        if ($package->isError()) {
            $error = $package->getError();
            return $error;
        }

        $success = new Response\Response();
        $success->package = $package;
        return $response;
    }

    public function getOwner(): Responses\IResponse {
        $ownerId = $this->ownerId;
        $source = [ "id" => $ownerId, ];
        $db = $this->database;
        $account = Accounts\Account::get($source, $db);
        if ($account->isError()) {
            $error = $account->getError();
            return $error;
        }

        $response = new Responses\Response();
        $response->owner = $account;
        return $response;
    }

    public function getAuthor(): Responses\IResponse {
        $db = $this->database;
        $src = [ "id" => $this->authorId, ];
        $accountResponse = Accounts\Account::get($src, $db);
        return $accountResponse;
    }
    
    public function toArray(): array {
         $sqlParams = [
            "id" => $this->$id,
            "name" => $this->name,
            "type" => $this->type,
            "version" => $this->version,
            "description" => $this->description,
            "homepage" => $this->homepage,
            "js" => $this->js,
            "css" => $this->css,
            "keywords" => $this->keywords
            "tag" => $this->tag,
            "timeCreated" => $this->timeCreated,
            "ownerId" => $this->ownerId,
            "authorId" => $this->authorId,
        ];

        /* Convert PackageVersions to arrays as well. */
        foreach ($vars["versions"] as $key => $value) {
            $array = $value->toArray();
            $vars["versions"][$key] = $array;
        }

        return $vars;
    }

    public function getId(): ?int {
        return $this->id;
    }

    public function getGlobalVersionId(): ?int {
        return $this->globalVersionId;
    }

    public function getOwnerId(): int {
        return $this->ownerId;
    }

    public function getAuthorId(): int {
        return $this->authorId;
    }

    public function getName(): ?string {
        return $this->name;
    }

    public function getType(): string {
        return $this->type;
    }

    public function setType(string $type): Responses\IResponse {
        $yesStrict = true;
        if (!in_array($type, self::TYPES, $yesStrict)) {
            $errorCode = "VersionSetTypeTypeInvalid";
            $error = new Resonses\ErrorResponse($errorCode);
            return $error;
        }

        $this->type = $type;

        $success = new Responses\Response();
        return $success;
    }

    public function getVersion(): string {
        $version = $this->version;
        return $version;
    }

    public function setVersion(string $version): Responses\IResponse {
        if (!$version) {
            $errorCode = "VersionSetVersionVersionEmpty";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->version = $version;

        $success = new Responses\Response();
        return $success;
    }

    public function getDescription(): string {
        $description = $this->description;
        return $description;
    }

    public function setDescription(string $description): Responses\IResponse {
        if (!$description) {
            $errorCode = "VersionSetDescriptionDescriptionEmpty";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->description = $description;

        $success = new Responses\Response();
        return $success;
    }

    public function getHomepage(): string {
        $homepage = $this->homepage;
        return $homepage;
    }

    public function setHomepage(string $homepage): Responses\IResponse {
        $this->homepage = $homepage;

        $response = new Responses\Response($status);
        return $response;
    }

    public function getJs(): string {
        return $this->js;
    }

    public function setJs(string $js): string {
        $this->js = $js;

        $response = new Responses\Response();
        return $response;
    }

    public function getCss(): string {
        return $this->css;
    }

    public function setCss(string $css): Responses\IResponse {
        $this->css = $css;

        $response = new Responses\Response();
        return $response;
    }

    public function getKeywords(): array {
        $keywords = $this->keywords;
        return $keywords;
    }

    public function getTag(): string {
        return $this->tag;
    }

    public function setTag(string $tag): Responses\IResponse {
        $this->tag = $tag;

        $response = new Responses\Response();
        return $response;
    }

    public function getTimeCreated(): ?int {
        return $this->timeCreated;
    }

    public function isError(): booL {
        $errorCode = isset($this->errorCode) ? $this->errorCode : null;
        $errorData = isset($this->errorData) ? $this->errorData : null;
        return $errorCode or $errorData;
    }

    public function getError(): Responses\ErrorResponse {
        $errorCode = isset($this->errorCode) ?
            $this->errorCode : "NoCodeProvided";
        $errorDode = isset($this->errorData) ? $this->errorData : null;
        $error = new Responses\ErrorResponse($errorCode, $errorData);
        return $error;
    }

    public function isInDatabase(): bool {
        $globalVersionId = isset($this->globalVersionId) ?
            $this->globalVersionId : null;
        if ($globalVersionId === null) {
            return false;
        }

        $queryString = "SELECT EXISTS(" .
            "SELECT id " .
            "FROM packages " .
            "WHERE global_version_id = :globalVersionId" .
        ")";
        
        $sqlParams = [
            ":globalVersionId" => $globalVersionId,
        ];

        $stmt = $this->database->prepare($queryString);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "VersionIsInDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            die(json_encode($error->getOutput()));
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        $exists = $fetch[0];
        return $exists;
    }

    public function serializeToDatabase(): IResponse {
        if ($this->isInDatabase()) {
            $errorCode = "VersionAlreadyInDatabase";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        if ($nameValidation->isError()) {
            return $nameValidation;
        }

        $src = [
            "packageId" => $this->packageId,
            "version" => $this->version,
        ];

        $versionValidation = 
            Validators\PackageVersionAvailabilityValidator::validate(
                $src,
                $db);

        if ($versionValidation->isError()) {
            return $versionValidation;
        }

        $queryStr =
            "INSERT INTO versions (name, version, description, homepage, " .
                "js, css, tag, author_id, package_id) " .
            "VALUES (:name, :version, :description, :homepage, :js, :css, " .
                ":tag, :authorId, :packageId) " .
            "RETURNING global_version_id, time_created";
        $sqlParams = [
            ":name" => $this->name,
            ":version" => $this->version,
            ":description" => $this->description,
            ":homepage" => $this->homepage,
            ":js" => $this->js,
            ":css" => $this->css,
            ":tag" => $this->tag,
            ":authorId" => $this->authorId,
            ":packageId" => $this->packageId,
        ];

        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "VersionSerializeToDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        if (!isset($this->globalVersionId)) {
            $this->globalVersionId = $fetch["global_version_id"];
        }

        if (!isset($this->timeCreated)) {
            $this->timeCreated = strtotime($fetch["time_created"]);
        }

        $success = new Responses\Response();
        return $success;
    }

    public function getDatabase(): PDO {
        return $this->database;
    }

    public function setDatabase(PDO $database): void {
        $this->database = $database;
    }

    public function updateFromDatabase(): IResponse {
        if ($this->globalVersionId === null) {
            $errorCode = "VersionUpdateFromDatabaseGlobalVersionIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $source = [ "globalVersionId" => $this->globalVersionId, ];
        $updatedResponse = Version::get($source, $this->database);
        if ($updatedResponse->isError()) {
            $error = $updatedResponse->getError();
            return $error;
        }

        $updated = $updatedResponse->version->toArray();
        foreach ($updated as $key => $value) {
            $this->{$key} = $value;
        }

        $success = new Responses\Response();
        return $success;
    }

    public function deleteFromDatabase(): Responses\IResponse {
        $globalVersionId = $this->globalVersionId;
        if ($globalVersionId === null) {
            $errorCode = "VersionDeleteFromDatabaseGlobalVersionIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        if (!isset($this->packageId)) {
            $errorCode = "VersionDeleteFromDatabasePackageIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $db->beginTransaction();

        $queryStr = "DELETE FROM packages " .
            "WHERE global_version_id = :globalVersionId";
        $sqlParams = [ ":globalVersionId" => $globalVersionId, ];
        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $data = [ "exception" => (string)$e, ];
            $errorCode = "VersionDeleteFromDatabaseQueryFailed";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $stmt = $db->prepare("INSERT INTO deleted_versions " .
            "(package_id, version) " .
            "VALUES " .
            "(:packageId, :version)");
        
        $sqlParams = [
            ":packageId" => $this->packageId,
            ":version" => $this->version,
        ];

        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "VersionDeleteFromDatabaseDeletedVersionsQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $db->commit();

        $success = new Responses\Response();
        return $success;
    }
}