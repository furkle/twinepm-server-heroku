<?php
namespace TwinePM\SqlAbstractions\Accounts;

use \TwinePM\SqlAbstractions\AbstractSqlAbstraction;
use \TwinePM\Responses;
use \TwinePM\Validators;
use \TwinePM\Filters;
use \TwinePM\Getters;
use \TwinePM\Miscellaneous;
use \TwinePM\SqlAbstractions\Packages\Package;
use \TwinePM\SqlAbstractions\Credentials\Credential;
use \PDO;
use \Exception;
class Account extends AbstractSqlAbstraction implements IAccount {
    private $id;
    private $name;
    private $nameVisible;
    private $description;
    private $timeCreated;
    private $timeCreatedVisible;
    private $email;
    private $emailVisible;
    private $dateStyle;
    private $timeStyle;
    private $homepage;
    private $errorCode;
    private $errorData;
    private $database;

    public static function get(
        array $source,
        PDO $database = null): Responses\IResponse
    {
        $db = $database ?? Getters\TwinepmDatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $sqlParams = [];
        $queryStr = "SELECT id, name, name_visible, description, " .
                "time_created, time_created_visible, email, email_visible, " .
                "date_style, time_style " .
            "FROM accounts WHERE ";
        if (isset($source["id"])) {
            $filterResponse = Filters\IdFilter::filter($source);
            if ($filterResponse->isError()) {
                return $filterResponse;
            }

            $queryStr .= "id = :userId";
            $sqlParams[":userId"] = $filterResponse->filtered;
        } else if (isset($source["name"])) {
            $validationResponse = Validators\NameValidator::validate($source);
            if ($validationResponse->isError()) {
                return $validationResponse;
            }

            $queryStr .= "name = :userName";
            $sqlParams[":userName"] = $source["name"];
        } else {
            $errorCode = "AccountGetNoValidArguments";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorData = [ "exception" => (string)$e, ];
            $errorCode = "AccountGetQueryFailed";
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "AccountGetAccountDoesNotExist";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $source = static::convertFetchToSource($fetch);
        $account = new Account($source, $db);
        if ($account->isError()) {
            $error = $account->getError();
            return $error;
        }

        $success = new Responses\Response();
        $success->account = $account;
        return $success;
    }

    public static function getFromToken(
        string $token,
        PDO $database = null): Responses\IResponse
    {
        $db = $database ?? Getters\DatabaseGetter::get();
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $validationResponse = Validators\TokensValidator::validate(
            $source,
            $db);

        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $stmt = $db->prepare(
            "SELECT A.id, A.name, A.name_visible, A.description, " .
                "A.time_created, A.time_created_visible, A.email, " .
                "A.email_visible, A.date_style, A.time_style " .
            "FROM accounts A " .
            "LEFT JOIN authorizations auth " .
            "ON A.id = auth.id " .
            "WHERE auth.oauth_token = :oAuthToken");

        $sqlParams = [ ":oAuthToken" => $token, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AccountGetFromTokenQueryFailed";
            $data = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $data);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "AccountGetFromTokenAccountOrTokenDoesNotExist";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $source = static::convertFetchToSource($fetch);
        $account = new Account($source, $db);

        $success = new Responses\Response();
        $success->account = $account;
        return $success;
    }

    public static function convertFetchToSource(array $fetch): array {
        $source = [
            "id" => $fetch["id"],
            "name" => $fetch["name"],
            "nameVisible" => $fetch["name_visible"],
            "description" => $fetch["description"],
            "timeCreated" => strtotime($fetch["time_created"]),
            "timeCreatedVisible" => $fetch["time_created_visible"],
            "email" => $fetch["email"],
            "emailVisible" => $fetch["email_visible"],
            "dateStyle" => $fetch["date_style"],
            "timeStyle" => $fetch["time_style"],
        ];

        return $source;
    }

    public function __construct(array $source, PDO $database = null) {
        $validationResponse = Validators\AccountSourceValidator::validate(
            $source);

        if ($validationResponse->isError()) {
            $error = $validationResponse->getError();
            $this->errorCode = $error->errorCode;
            $this->errorData = $error->errorData;
            return;
        }

        $this->id = $source["id"];
        $this->name = isset($source["name"]) ? $source["name"] : null;
        $this->nameVisible = isset($source["nameVisible"]) ?
            $source["nameVisible"] : static::DEFAULTS["nameVisible"];
        $this->description = isset($source["description"]) ?
            $source["description"] : static::DEFAULTS["description"];
        $this->timeCreated = isset($source["timeCreated"]) ?
            $source["timeCreated"] : null;
        $this->timeCreatedVisible = isset($source["timeCreatedVisible"]) ?
            $source["timeCreatedVisible"] : true;
        $this->email = isset($source["email"]) ?
            $source["email"] : static::DEFAULTS["email"];
        $this->emailVisible = isset($source["emailVisible"]) ?
            $source["emailVisible"] : static::DEFAULTS["emailVisible"];
        $this->dateStyle = isset($source["dateStyle"]) ?
            $source["dateStyle"] : static::DEFAULTS["dateStyle"];
        $this->timeStyle = isset($source["timeStyle"]) ?
            $source["timeStyle"] : static::DEFAULTS["timeStyle"];
        $this->homepage = isset($source["homepage"]) ?
            $source["homepage"] : static::DEFAULTS["homepage"];

        $db = $database ?? Getters\DatabaseGetter::get();
        $this->database = $db;
    }

    /* Public get, private set. */
    public function __get(string $propName) {
        return isset($this->{$propName}) ? $this->{$propName} : null;
    }

    public function updateFromDatabase(): Responses\IResponse {
        $id = $this->id;
        $source = [
            "id" => $id,
        ];

        $account = new Account($source, $this->database);
        if ($account->isError()) {
            $error = $account->getError();
            return $error;
        }

        $array = $account->toArray();
        foreach ($array as $key => $value) {
            $this->{$key} = $value;
        }

        $success = new Responses\Response();
        return $success;
    }

    public function deleteFromDatabase(): Responses\IResponse {
        $id = $this->id;

        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare("DELETE FROM accounts WHERE id = :id");
        $sqlParams = [ ":id" => $id, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AccountDeleteFromDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "AccountDeleteFromDatabaseAccountDoesNotExist";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }

    public function getPackages(): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT id, name, author_id, owner_id, description, homepage, " .
                "type, current_version, time_created, keywords, tag " .
            "FROM packages " .
            "WHERE owner_id = :id " .
            "ORDER BY id");

        $sqlParams = [ ":id" => $this->id,];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorData = [ "exception" => (string)$e, ];
            $errorCode = "AccountGetPackagesQueryFailed";
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $packages = [];
        foreach ($fetchAll as $fetch) {
            $source = Package::convertFetchToSource($fetch);
            $package = new Package($source, $db);
            if ($package->isError()) {
                $error = $package->getError();
                return $error;
            }

            $packages[] = $package;
        }

        $success = new Responses\Response();
        $success->packages = $packages;
        return $success;
    }

    public function getCredential(): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $stmt = $db->prepare(
            "SELECT id, name, password, validated " .
            "FROM credentials " .
            "WHERE id = :id");

        $sqlParams = [ ":id" => $this->id, ];
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AccountGetCredentialsQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        if (!$stmt->rowCount()) {
            $errorCode = "AccountGetCredentialsNoResults";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        $source = Credential::convertFetchToSource($fetch);
        $credential = new Credential($source, $db);

        $success = new Responses\Response();
        $success->credential = $credential;
        return $success;
    }

    public function getId(): int {
        $id = $this->id;
        return $id;
    }

    public function setId(int $id): Responses\IResponse {
        $this->id = $id;

        $success = new Responses\Response();
        return $success;
    }

    public function getName(): ?string {
        $name = $this->name;
        return $name;
    }

    public function setName(?string $name): Responses\IResponse {
        $source = [ "name" => $name, ];
        $validationResponse = Validators\NameValidator::validate($source);
        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $this->name = $name;
        $response = new Responses\Response();
        return $response;
    }

    public function getNameVisible(): bool {
        $visible = $this->nameVisible;
        return $visible;   
    }

    public function setNameVisible(bool $nameVisible): Responses\IResponse {
        $this->nameVisible = $nameVisible;

        $success = Responses\Response();
        return $success;
    }

    public function getDescription(): string {
        $description = $this->description;
        return $description;
    }

    public function setDescription(string $description): Responses\IResponse {
        $this->description = $description;
        
        $success = new Responses\Response();
        return $success;
    }

    public function getTimeCreated(): ?int {
        $timeCreated = $this->timeCreated;
        return $timeCreated;
    }

    public function getTimeCreatedVisible(): bool {
        $visible = $this->timeCreatedVisible;
        return $visible;
    }

    public function setTimeCreatedVisible(bool $visible): Responses\IResponse {
        $this->timeCreatedVisible = $visible;
        
        $success = new Responses\Response();
        return $success;
    }

    public function getEmail(): string {
        $email = $this->email;
        return $email;
    }

    public function setEmail(string $email): Responses\IResponse {
        $this->email = $email;
        
        $success = new Responses\Response();
        return $success;
    }

    public function getEmailVisible(): bool {
        $visible = $this->emailVisible;
        return $visible; 
    }

    public function setEmailVisible(bool $visible): Responses\IResponse {
        $this->emailVisible = $visible;
        $success = new Responses\Response();
        return $success;
    }

    public function getDateStyle(): string {
        $dateStyle = $this->dateStyle;
        return $dateStyle;
    }

    public function setDateStyle(string $dateStyle): Responses\IResponse {
        $yesStrict = true;
        if (!in_array($dateStyle, static::DATE_STYLES, $yesStrict)) {
            $errorCode = "AccountSetDateStyleDateStyleInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->dateStyle = $dateStyle;

        $success = new Responses\Response();
        return $success;
    }

    public function getTimeStyle(): string {
        $timeStyle = $this->timeStyle;
        return $timeStyle;
    }

    public function setTimeStyle(string $timeStyle): Responses\IResponse {
        $yesStrict = true;
        if (!in_array($timeStyle, static::TIME_STYLES, $yesStrict)) {
            $errorCode = "AccountSetTimeStyleTImeStyleInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $this->timeStyle = $timeStyle;

        $success = new Responses\ErrorResponse();
        return $success;
    }

    public function getHomepage(): string {
        $homepage = $this->homepage;
        return $homepage;
    }

    public function setHomepage(string $homepage): Responses\IResponse {
        $this->homepage = $homepage;

        $success = new Responses\Response();
        return $success;
    }

    public function isInDatabase(): bool {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $queryStr = "SELECT EXISTS(" .
            "SELECT id " .
            "FROM accounts " .
            "WHERE id = :id" .
        ")";
        
        $sqlParams = [ ":id" => $this->id, ];
        $stmt = $db->prepare($queryString);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "AccountIsInDatabaseQueryFailed";
            $data = [ "exception" => (string)$e, ];
            $response = new Responses\ErrorResponse($errorCode, $data);
            $response->respondAndDie();
        }

        $fetch = $stmt->fetch(PDO::FETCH_NUM);
        $exists = $fetch[0];
        return $exists;
    }

    public function serializeToDatabase(): Responses\IResponse {
        $db = $this->database;
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $source = [
            "id" => $this->id,
            "name" => $this->name,
        ];

        $validationResponse = Validators\UsernameAvailabilityValidator::validate(
            $source,
            $db);

        if ($validationResponse->isError()) {
            return $validationResponse;
        }

        $stmt = $db->prepare(
            "INSERT INTO accounts (id, name, name_visible, description, " .
                "time_created_visible, email, email_visible, date_style, " .
                "time_style, homepage) " .
            "VALUES (:id, :name, :nameVisible, :description, " .
                ":timeCreatedVisible, :email, :emailVisible, :dateStyle, " .
                ":timeStyle, :homepage) " .
            "ON CONFLICT (id) " .
            "DO UPDATE SET " .
                "name = excluded.name, " .
                "name_visible = excluded.name_visible, " .
                "description = excluded.description, " .
                "time_created_visible = excluded.time_created_visible, " .
                "email = excluded.email, " .
                "email_visible = excluded.email_visible, " .
                "date_style = excluded.date_style, " .
                "time_style = excluded.time_style, " .
                "homepage = excluded.homepage " .
            "RETURNING time_created");

        $paramInt = PDO::PARAM_INT;
        $paramStr = PDO::PARAM_STR;
        $paramBool = PDO::PARAM_BOOL; 
        $stmt->bindValue(":id", $this->id, $paramInt);
        $stmt->bindValue(":name", $this->name, $paramStr);
        $stmt->bindValue(":nameVisible", $this->nameVisible, $paramBool);
        $stmt->bindValue(":description", $this->description, $paramStr);
        $stmt->bindValue(
            ":timeCreatedVisible",
            $this->timeCreatedVisible,
            $paramBool);

        $stmt->bindValue(":email", $this->email, $paramStr);
        $stmt->bindValue(":emailVisible", $this->emailVisible, $paramBool);
        $stmt->bindValue(":dateStyle", $this->dateStyle, $paramStr);
        $stmt->bindValue(":timeStyle", $this->timeStyle, $paramStr);
        $stmt->bindValue(":homepage", $this->homepage, $paramStr);
        try {
            $stmt->execute();
        } catch (Exception $e) {
            $errorCode = "AccountSerializeToDatabaseQueryFailed";
            $errorData = [ "exception" => (string)$e, ];
            $error = new Responses\ErrorResponse($errorCode, $errorData);
            return $error;
        }

        $fetch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (isset($fetch["time_created"])) {
            $this->timeCreated = strtotime($fetch["time_created"]);
        }

        $success = new Responses\Response();
        return $success;
    }
}
?>