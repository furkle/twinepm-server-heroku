<?php
namespace TwinePM\Responses;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
use \TwinePM\Getters;
use \PDO;
use \Exception;
public class Search implements ISearch {
    const DEFAULTS = [
        "type" => "packages",
        
        "filterTargets" => [
            "name",
            "keywords",
        ],

        "filterStyle" => "metaphone/contains",

        "sortDirection" => "descending",

        "includePackages" => [
            "packages" => "metadata",
            "users" => null,
        ],
    ];

    private $source;
    private $database;

    public static function __construct(
        array $source,
        PDO $database = null)
    {
        $this->source = $source;
        $this->database = $database ?? Getters\DatabaseGetter::get();
    }

    public function query(string $type, string $query): Responses\IResponse {
        $source = $this->source;
        if ($source["type"] === "packages") {
            $response = $this->searchPackages($queryString);
        } else if ($source["type"] === "users") {
            $response = $this->searchUsers($queryString);
        } else {
            $errorCode = "SearchQuerySearchTypeInvalid";
            $response = new Responses\ErrorResponse($errorCode);
        }

        return $response;
    }

    protected function searchPackages(string $queryString): IResponse {
        $source = $this->source;
        $includeP = $source["packageDataLevel"];
        if ($includeP !== "full" and $includeP !== "metadata") {
            $errorCode = "SearchPackagesDataLevelInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $queryStr = "SELECT id, name, description, keywords, type, homepage " .
            ($includeP === "all" or $includeP === "full" ? "js, css " : "") .
            "FROM packages ";

        if ($source->timeCreatedRange or $source->versionRange) {
            $queryStr .= "WHERE ";
        }

        $sqlParams = [];
        if ($source->timeCreatedRange) {
            $queryStr .= "time_created >= :time_created_lower " .
                "AND time_created <= :time_created_upper ";
            $sqlParams[":time_created_lower"] = $source->timeCreatedRange[0];
            $sqlParams[":time_created_upper"] = $source->timeCreatedRange[1];
        }

        if ($source->versionRange) {
            if ($source->timeCreatedRange) {
                $queryStr .= "AND ";
            }

            $queryStr .= "version >= :version_lower AND " .
                "version <= :version_upper ";
            $sqlParams[":version_lower"] = $source->versionRange[0];
            $sqlParams[":version_upper"] = $source->versionRange[1];
        }

        if ($source->subtype)) {
            if ($source->timeCreatedRange or $source->versionRange) {
                $queryStr .= "AND ";
            }

            $queryStr .= "subtype = :subtype ";
            $sqlParams[":subtype"] = $source->subtype;
        }

        $stmt = $db->prepare($queryStr);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "SearchPackagesQueryFailed";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $response = null;
        if ($stmt->rowCount()) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $filterResponseResponse = $this->filterResults($results);
            if ($filterResponseResponse->isError()) {
                return $filterResponseResponse;
            }

            $response = $this->sortResults($filterResponseResponse->results);
        } else {
            $response = new Responses\Response($status);
            $response->results = [];
        }

        return $response;
    }

    protected function searchUsers(string $queryString): Responses\IResponse {
        $source = $this->source;
        $includeP = $source->includePackages;
        if ($includeP !== null and
            $includeP !== "full" and
            $includeP !== "metadata")
        {
            $errorCode = "SearchUsersDataLevelInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $stmt = "SELECT id, name, description, time_created, email, " .
                "date_style, time_style " .
            "FROM userdata ";
        $sqlParams = [];
        $timeCreatedRange = $source->timeCreatedRange;
        if ($timeCreatedRange) {
            $stmt .= "WHERE time_created >= :time_created_lower " .
                "AND time_created <= :time_created_upper ";
            $sqlParams[":time_created_lower"] = $timeCreatedRange[0];
            $sqlParams[":time_created_upper"] = $timeCreatedRange[1];
        }

        $stmt = $this->database->prepare($stmt);
        try {
            $stmt->execute($sqlParams);
        } catch (Exception $e) {
            $errorCode = "SearchUsersQueryFailed";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $response = null;
        if ($results) {
            $filterResponseResponse = $this->filterResults($results);
            if ($filterResponseResponse->isError()) {
                return $filterResponseResponse;
            }

            $response = $this->sortResults($filterResponseResponse->results);
            if ($response->isError()) {
                return $response;
            }
        } else {
            $response = new Responses\Response();
            $response->results = [];
        }

        $results = $response->results;
        if ($includeP) {
            foreach ($results as $key => $value) {
                $packagesResponse = UserIdToPackagesTransformer::transform(
                    $value["id"],
                    $includeP,
                    $this->database);
                if ($packagesResponse->isError()) {
                    return $packagesResponse;
                }

                $results[$key]["packages"] = $packagesResponse->packages;
            }
        }

        $response->results = $results;
        return $response;
    }

    protected function filterResults(array $results): Responses\IResponse {
        $filterResponse = null;
        switch ($this->source["filterStyle"]) {
            case "exact":
                $filterResponse = Filters\ExactFilter::filter(
                    $results,
                    $query,
                    $targets);
                break;
            case "contains":
                $filterResponse = Filters\ContainsFilter::filter(
                    $results,
                    $params["query"],
                    $params["filterTargets"]);
                break;
            case "metaphone/contains":
                $filterResponse = Filters\MetaphoneContainsFilter::filter(
                    $results,
                    $params["query"],
                    $params["filterTargets"]);
                break;
            case "similarity":
                $filterResponse = Filters\SimilarityFilter::filter(
                    $results,
                    $params["query"],
                    $params["filterTargets"]);
                break;
            case "levenshtein":
                $filterResponse = Filters\LevenshteinFilter::filter(
                    $results,
                    $params["query"],
                    $params["filterTargets"]);
                break;
            case "soundex/levenshtein":
                $filterResponse = Filters\SoundexLevenshtein::filter(
                    $results,
                    $params["query"],
                    $params["filterTargets"]);
                break;
            case "metaphone/levenshtein":
                $filterResponse = Filters\MetaphoneLevenshteinFilter::filter(
                    $results,
                    $params["query"],
                    $params["filterTargets"]);
                break;
        }

        return $filterResponse;
    }

    public function getSource(): array {
        return $this->source;
    }

    public function setSource(array $source): Responses\IResponse {
        $this->source = $source;

        $success = new Responses\Response();
        return $success;
    }

    public function getDatabase(): PDO {
        return $this->database;
    }

    public function isError(): bool {
        return (bool)$this->errorCode;
    }

    public function getError(): ?ErrorResponse {
        if ($this->isError()) {
            $response = new Responses\ErrorResponse($this->errorCode);
            return $response;
        } else {
            return null;
        }
    }
}