<?php
require_once __DIR__ . "/getSearchDefaults.php";
function getSearchParams() {
	$defaults = getSearchDefaults();

	$response = array("status" => 200);

	$type = isset($_GET["type"]) ? $_GET["type"] : "";
	if (!$type) {
		$response["status"] = 400;
		$response["error"] = "The type argument was not provided.";
		return $response;
	}

	$type = strtolower($type);
	$yesStrict = true;
	if (!in_array($type, $defaults["type"], $yesStrict)) {
		$response["status"] = 400;
		$response["error"] = "The type argument was not recognized. $type was " .
			"provided, but was not one of the following: " .
			explode($defaults["type"], ",") . ".";
		return $response;
	}

	$query = isset($_GET["query"]) ? strtolower($_GET["query"]) : null;
	if ($query === null) {
		$response["status"] = 400;
		$response["error"] = "The query argument was not provided.";
		return $response;
	}

	$query = strtolower($query);

	$filterTargets = isset($_GET["filterTargets"]) ?
		json_decode($_GET["filterTargets"], true) :
		null;
	if (!$filterTargets) {
		$response["status"] = 400;
		$response["error"] = "The filterTargets argument was not provided, or " .
			"was not a valid JSON object.";
		return $response;
	} else if (count($filterTargets) === 0) {
		$response["status"] = 400;
		$response["error"] = "The filterTargets argument was provided, but was " .
			"empty.";
		return $response));
	}

	$filterTargets = array_map(function($value) {
		return strtolower($value);
	}, $filterTargets);

	foreach ($filterTargets as $value) {
		if (!in_array($value, $defaults["filterTargets"], $yesStrict)) {
			$response["status"] = 400;
			$response["error"] = "The filterTargets argument was provided, but " .
				"contained the unknown value $value. Acceptable values are: " .
				join(", ", $allFilterTargets) . ".";
			return $response;
		}
	}

	$filterStyle = isset($_GET["filterStyle"]) ?
		strtolower($_GET["filterStyle"]) : null;
	if (!$filterStyle) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The filterStyle argument was not provided.";
		die(json_encode($response));
	} else if (!in_array($filterStyle, $defaults["filterStyles"], $yesStrict)) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The filterStyle argument was provided, but was " .
			"not one of the following: " . join(",", $allFilterStyles) . ".";
		die(json_encode($response));
	}

	$allSortTargets = array(
		"id",
		"name",
		"description",
		"keywords",
		"homepage",
	);
	$sortTarget = isset($_GET["sortTarget"]) ?
		strtolower($_GET["sortTarget"]) : null;
	if (!$sortTarget) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The sortTarget argument was not provided.";
		die(json_encode($response));
	} else if (!in_array($sortTarget, $allSortTargets)) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The sortTarget argument was provided, but was not " .
			"one of the following: " . join(",", $allSortTargets) . ".";
		die(json_encode($response));
	}

	$allSortStyles = array(
		"alphanumeric",
		"similarity",
		"levenshtein",
		"soundex/levenshtein",
		"metaphone/levenshtein",
	);
	$sortStyle = isset($_GET["sortStyle"]) ?
		strtolower($_GET["sortStyle"]) : null;
	if (!$sortStyle) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The sortStyle argument was not provided.";
		die(json_encode($response));
	} else if (!in_array($sortStyle, $allSortStyles)) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The sortStyle argument was provided, but was not " .
			"one of the following: " . join(",", $allSortStyles) . ".";
		die(json_encode($response));
	}

	$sortDirection = isset($_GET["sortDirection"]) ? $_GET["sortDirection"] : null;
	if (!$sortDirection) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The sortDirection argument was not provided.";
		die(json_encode($response));
	} else if ($sortDirection !== "asc" and
		$sortDirection !== "ascending" and
		$sortDirection !== "desc" and
		$sortDirection !== "descending")
	{
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The sortDirection argument was provided, but was " .
			"asc, ascending, desc, or descending.";
		die(json_encode($response));
	}

	$dateCreatedRange = isset($_GET["dateCreatedRange"]) ?
		$_GET["dateCreatedRange"] : null;
	if ($dateCreatedRange) {
		$dateCreatedRange = json_decode($dateCreatedRange, true);
		
		if (!$dateCreatedRange) {
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The dateCreatedRange argument was provided, " .
				"but it could not be deserialized from JSON.";
			die(json_encode($response));
		} else if (count($dateCreatedRange) !== 2) {
			http_response_code(400);
			$response["status"] = 400;
			$length = count($dateCreatedRange);
			$response["error"] = "The dateCreatedRange argument was included, " .
				"but it contained $length elements, not two.";
			die(json_encode($response));
		} else if (gettype($dateCreatedRange[0]) !== "integer" or
			gettype($dateCreatedRange[1]) !== "integer")
		{
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The dateCreatedRange argument was provided, " .
				"but one or both array items is not an integer.";
		} else if (!($dateCreatedRange[0] >= 0 and
			$dateCreatedRange[1] >= $dateCreatedRange[0]))
		{
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The dateCreatedRange argument was included, " .
				"and had two elements, but it did not contain two integers, the " .
				"first greater than or equal to 0, and the second greater than " .
				"or equal the first.";
			die(json_encode($response));
		}
	}

	$dateModifiedRange = isset($_GET["dateModifiedRange"]) ?
		$_GET["dateModifiedRange"] : null;
	if ($dateModifiedRange) {
		$dateModifiedRange = json_decode($dateModifiedRange, true);

		if (!$dateModifiedRange) {
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The dateModifiedRange argument was provided, " .
				"but it could not be deserialized from JSON.";
			die(json_encode($response));
		} else if (count($dateModifiedRange) !== 2) {
			http_response_code(400);
			$response["status"] = 400;
			$length = count($dateModifiedRange);
			$response["error"] = "The dateModifiedRange argument was included, " .
				"but it contained $length elements, not two.";
			die(json_encode($response));
		} else if (gettype($dateModifiedRange[0]) !== "integer" or
			gettype($dateModifiedRange[1]) !== "integer")
		{
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The dateModifiedRange argument was provided, " .
				"but one or both array items is not an integer.";
			die(json_encode($response));
		} else if (!($dateModifiedRange[0] >= 0 and
			$dateModifiedRange[1] >= $dateModifiedRange[0]))
		{
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The dateModifiedRange argument was included, " .
				"and had two elements, but it did not contain two integers, the " .
				"first greater than or equal to 0, and the second greater than " .
				"or equal the first.";
			die(json_encode($response));
		}
	}

	$versionRange = isset($_GET["versionRange"]) ?
		$_GET["versionRange"] : null;
	if ($versionRange) {
		$versionRange = json_decode($versionRange, true);

		if (!$versionRange) {
			$response["status"] = 400;
			$response["error"] = "The versionRange argument was provided, but it " .
				"could not be deserialized from JSON.";
			return $response;
		} else if (count($versionRange) !== 2) {
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The versionRange argument was included, but it " .
				"did not contain only two elements.";
			die(json_encode($response));
		} else if (gettype($versionRange[0]) !== "string" or
			gettype($versionRange[1]) !== "string")
		{
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The versionRange argument was provided, " .
				"but one or both array items is not a string.";
			die(json_encode($response));
		} else if (!($versionRange[1] >= $versionRange[0])) {
			http_response_code(400);
			$response["status"] = 400;
			$response["error"] = "The versionRange argument was included, and " .
				"had two elements, but it did not contain two strings, the " .
				"second greater than or equal the first.";
			die(json_encode($response));
		}
	}

	$subtype = isset($_GET["subtype"]) ? $_GET["subtype"] : null;

	return $response;
?>
