<?php
require_once __DIR__ . "/getSearchDefaults.php";
function getSearchParams($source) {
    $defaults = getSearchDefaults();
    $yesStrict = true;
    $response = array("status" => 200);

    $query = isset($source["query"]) ? strtolower($source["query"]) : null;
    if ($query === null) {
        $response["status"] = 400;
        $response["error"] = "The query argument was not provided.";
        return $response;
    }

    $type = isset($source["type"]) ? strtolower($source["type"]) : "";
    if (!$type) {
        $response["status"] = 400;
        $response["error"] = "The type argument was not provided.";
        return $response;
    } else if (!in_array($type, $defaults["type"], $yesStrict)) {
        $response["status"] = 400;
        $response["error"] = "The type argument was not recognized. $type " .
            "was provided, but was not one of the following: " .
            join($defaults["type"], ",") . ".";
        return $response;
    }

    $filterTargets = isset($source["filterTargets"]) ?
        json_decode($source["filterTargets"], true) :
        null;
    if (!$filterTargets) {
        $response["status"] = 400;
        $response["error"] = "The filterTargets argument was not provided, " .
            "or was not a valid JSON object.";
        return $response;
    } else if (count($filterTargets) === 0) {
        $response["status"] = 400;
        $response["error"] = "The filterTargets argument was provided, but " .
            "was empty.";
        return $response));
    }

    $filterTargets = array_map(function($value) {
        return strtolower($value);
    }, $filterTargets);

    foreach ($filterTargets as $value) {
        if (!in_array($value, $defaults["filterTargets"], $yesStrict)) {
            $response["status"] = 400;
            $response["error"] = "The filterTargets argument was provided, " .
                "but contained the unknown value $value. Acceptable values " .
                "are: " . join(", ", $defaults["filterTargets"]) . ".";
            return $response;
        }
    }

    $filterStyle = isset($source["filterStyle"]) ?
        strtolower($source["filterStyle"]) : null;
    if (!$filterStyle) {
        $response["status"] = 400;
        $response["error"] = "The filterStyle argument was not provided.";
        return $response;
    } else if (!in_array($filterStyle, $defaults["filterStyles"], $yesStrict)) {
        $response["status"] = 400;
        $response["error"] = "The filterStyle argument was provided, but " .
            "was not one of the following: " .
            join(", ", $defaults["filterStyles"]) . ".";
        return $response;
    }

    $sortTarget = isset($source["sortTarget"]) ?
        strtolower($source["sortTarget"]) : null;
    if (!$sortTarget) {
        $response["status"] = 400;
        $response["error"] = "The sortTarget argument was not provided.";
        return $response;
    } else if (!in_array($sortTarget, $defaults["sortTargets"], $yesStrict)) {
        $response["status"] = 400;
        $response["error"] = "The sortTarget argument was provided, but was " .
            "not one of the following: " .
            join(", ", $defaults["sortTargets"]) . ".";
        return $response;
    }

    $sortStyle = isset($source["sortStyle"]) ?
        strtolower($source["sortStyle"]) : null;
    if (!$sortStyle) {
        $response["status"] = 400;
        $response["error"] = "The sortStyle argument was not provided.";
        return $response;
    } else if (!in_array($sortStyle, $defaults["sortStyles"], $yesStrict)) {
        $response["status"] = 400;
        $response["error"] = "The sortStyle argument was provided, but was " .
            "not one of the following: " .
            join(", ", $defaults["sortStyles"]) . ".";
        return $response;
    }

    $sortDirection = isset($source["sortDirection"]) ?
        $source["sortDirection"] : null;
    if (!$sortDirection) {
        $response["status"] = 400;
        $response["error"] = "The sortDirection argument was not provided.";
        return $response;
    } else if (!in_array($sortDirection, $defaults["sortDirection"], $yesStrict)) {
        $response["status"] = 400;
        $response["error"] = "The sortDirection argument was provided, but " .
            "was not one of the following: " .
            join(", ", $defaults["sortDirection"]) . ".";
        return $response;
    }

    $dateCreatedRange = isset($source["dateCreatedRange"]) ?
        $source["dateCreatedRange"] : null;
    if ($dateCreatedRange) {
        $dateCreatedRange = json_decode($dateCreatedRange, true);
        
        if (!$dateCreatedRange) {
            http_response_code(400);
            $response["status"] = 400;
            $response["error"] = "The dateCreatedRange argument was provided, " .
                "but it could not be deserialized from JSON.";
            return $response;
        } else if (count($dateCreatedRange) !== 2) {
            $response["status"] = 400;
            $length = count($dateCreatedRange);
            $response["error"] = "The dateCreatedRange argument was included, " .
                "but it contained $length elements, not two.";
            return $response;
        } else if (gettype($dateCreatedRange[0]) !== "integer" or
            gettype($dateCreatedRange[1]) !== "integer")
        {
            $response["status"] = 400;
            $response["error"] = "The dateCreatedRange argument was provided, " .
                "but one or both array items is not an integer.";
            return $response;
        } else if (!($dateCreatedRange[0] >= 0 and
            $dateCreatedRange[1] >= $dateCreatedRange[0]))
        {
            $response["status"] = 400;
            $response["error"] = "The dateCreatedRange argument was included, " .
                "and had two elements, but it did not contain two integers, the " .
                "first greater than or equal to 0, and the second greater than " .
                "or equal the first.";
            return $response;
        }
    }

    $dateModifiedRange = isset($source["dateModifiedRange"]) ?
        $source["dateModifiedRange"] : null;
    if ($dateModifiedRange) {
        $dateModifiedRange = json_decode($dateModifiedRange, true);
        if (!$dateModifiedRange) {
            $response["status"] = 400;
            $response["error"] = "The dateModifiedRange argument was provided, " .
                "but it could not be deserialized from JSON.";
            return $response;
        } else if (count($dateModifiedRange) !== 2) {
            $response["status"] = 400;
            $length = count($dateModifiedRange);
            $response["error"] = "The dateModifiedRange argument was included, " .
                "but it contained $length elements, not two.";
            return $response;
        } else if (gettype($dateModifiedRange[0]) !== "integer" or
            gettype($dateModifiedRange[1]) !== "integer")
        {
            $response["status"] = 400;
            $response["error"] = "The dateModifiedRange argument was provided, " .
                "but one or both array items is not an integer.";
            return $response;
        } else if (!($dateModifiedRange[0] >= 0 and
            $dateModifiedRange[1] >= $dateModifiedRange[0]))
        {
            $response["status"] = 400;
            $response["error"] = "The dateModifiedRange argument was included, " .
                "and had two elements, but it did not contain two integers, the " .
                "first greater than or equal to 0, and the second greater than " .
                "or equal the first.";
            return $response;
        }
    }

    $versionRange = isset($source["versionRange"]) ?
        $source["versionRange"] : null;
    if ($versionRange) {
        $versionRange = json_decode($versionRange, true);

        if (!$versionRange) {
            $response["status"] = 400;
            $response["error"] = "The versionRange argument was provided, but it " .
                "could not be deserialized from JSON.";
            return $response;
        } else if (count($versionRange) !== 2) {
            $response["status"] = 400;
            $response["error"] = "The versionRange argument was included, but it " .
                "did not contain only two elements.";
            return $response;
        } else if (gettype($versionRange[0]) !== "string" or
            gettype($versionRange[1]) !== "string")
        {
            $response["status"] = 400;
            $response["error"] = "The versionRange argument was provided, " .
                "but one or both array items is not a string.";
            return $response;
        } else if (!($versionRange[1] >= $versionRange[0])) {
            $response["status"] = 400;
            $response["error"] = "The versionRange argument was included, " .
                "and had two elements, but it did not contain two strings, " .
                "the second greater than or equal the first.";
            return $response;
        }
    }

    $subtype = isset($source["subtype"]) ? $source["subtype"] : null;

    $params = array(
        "query" => $query,
        "type" => $type,
        "filterTargets" => $filterTargets,
        "filterStyle" => $filterStyle,
        "sortTarget" => $sortTarget,
        "sortStyle" => $sortStyle,
        "sortDirection" => $sortDirection,
        "dateCreatedRange" => $dateCreatedRange,
        "dateModifiedRange" => $dateModifiedRange,
        "versionRange" => $versionRange,
        "subtype" => $subtype
    );

    $response["params"] = $params;

    return $response;
?>
