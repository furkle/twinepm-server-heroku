<?php
function searchPackages(
	$query,
	$filterTargets,
	$filterStyle,
	$sortTarget,
	$sortStyle,
	$sortDirection,
	$dateCreatedRange,
	$dateModifiedRange,
	$versionRange,
	$subtype)
{
	$response = array("status" => 200);

	if (!isset($query)) {
		$response["status"] = 500;
		$response["error"] = "The query argument was not provided to " .
			"searchPackages.";
		return $response;
	}

	if (!isset($filterTargets) or !$filterTargets) {
		$response["status"] = 500;
		$response["error"] = "The filterTargets argument was not provided " .
			"to searchPackages.";
		return $response;
	} else if (gettype($filterTargets) !== "array") {
		$response["status"] = 500;
		$response["error"] = "The filterTargets argument was provided to " .
			"searchPackages, but was not an array.";
		return $response;
	} else if (count($filterTargets) === 0) {
		$response["status"] = 500;
		$response["error"] = "The filterTargets argument was provided to " .
			"searchPackages, but it was an empty array.";
		return $response;
	}

	// matches for fields should be case-invariant
	$filterTargets = array_map(function ($value) {
		return strtolower($value);
	}, $filterTargets);
	
	$allFilterTargets = array(
		"id",
		"name",
		"description",
		"keywords",
		"homepage"
	);

	foreach ($filterTargets as $key) {
		if (!in_array($key, $allFilterTargets)) {
			$response["status"] = 500;
			$response["error"] = "An unrecognized argument was found in the " .
				"filterTargets array in searchPackages. The unrecognized " .
				"argument is $key. Acceptable arguments are " .
				join(", ", $allFilterTargets) . ".";
			return $response;
		}
	}

	if (!isset($filterStyle)) {
		$response["status"] = 500;
		$response["error"] = "The filterStyle argument was not provided to " .
			"searchPackages.";
		return $response;
	} else if (gettype($filterStyle) !== "string") {
		$response["status"] = 500;
		$response["error"] = "The filterStyle argument was provided to " .
			"searchPackages, but it was not a string.";
		return $response;
	}

	$filterStyle = strtolower($filterStyle);
	$allFilterStyles = array(
		"exact",
		"contains",
		"metaphone/contains",
		"similarity",
		"levenshtein",
		"soundex/levenshtein",
		"metaphone/levenshtein",
	);

	if (!in_array($filterStyle, $allFilterStyles)) {
		$response["status"] = 500;
		$response["error"] = "An unrecognized argument was found in the " .
			"filterStyle argument in searchPackages. The unrecognized " .
			"argument is $filterStyle. Acceptable arguments are " .
			join(", ", $allFilterStyles) . ".";
		return $response;
	}
 
	if (!isset($sortTarget) or !$sortTarget) {
		$response["status"] = 500;
		$response["error"] = "The sortTarget argument was not provided to " .
			"searchPackages.";
		return $response;
	} else if (gettype($sortTarget) !== "string") {
		$response["status"] = 500;
		$response["error"] = "The sortTarget argument was provided to " .
			"searchPackages, but it was not a string.";
		return $response;
	}

	$sortTarget = strtolower($sortTarget);
	$allSortTargets = array(
		"id",
		"name",
		"description",
		"keywords",
		"homepage"
	);

	if (!in_array($sortTarget, $allSortTargets)) {
		$response["status"] = 500;
		$response["error"] = "The sortTarget argument was provided to " .
			"searchPackages, but it was not a recognized argument. " .
			"Acceptable arguments are " . join(", ", $allSortTargets) . ".";
		return $response;
	}

	if (!isset($sortStyle) or !$sortStyle) {
		$response["status"] = 500;
		$response["error"] = "The sortStyle argument was not provided to " .
			"searchPackages.";
		return $response;
	} else if (gettype($sortStyle) !== "string") {
		$response["status"] = 500;
		$response["error"] = "The sortStyle argument was provided to " .
			"searchPackages, but it was not a string.";
		return $response;
	}

	$sortStyle = strtolower($sortStyle);
	$allSortStyles = array(
		"alphanumeric",
		"similarity",
		"levenshtein",
		"soundex/levenshtein",
		"metaphone/levenshtein",
	);

	if (!in_array($sortStyle, $allSortStyles)) {
		$response["status"] = 500;
		$response["error"] = "The sortStyle argument was provided to " .
			"searchPackages, but it was not a recognized argument. " .
			"Acceptable arguments are " . join(", ", $allSortStyles) . ".";
		return $response;
	}

	if (!isset($sortDirection) or !$sortDirection) {
		$response["status"] = 500;
		$response["error"] = "The sortDirection argument was not provided " .
			"to searchPackages.";
		return $response;
	} else if (gettype($sortDirection) !== "string") {
		$response["status"] = 500;
		$response["error"] = "The sortDirection argument was provided to " .
			"searchPackages, but was not a string.";
		return $response;
	}

	if ($sortDirection !== "asc" and
		$sortDirection !== "ascending" and
		$sortDirection !== "desc" and
		$sortDirection !== "descending")
	{
		$response["status"] = 500;
		$response["error"] = "The sortDirection argument was provided to " .
			"searchPackages, but it was not a recognized value. Acceptable " .
			"values are asc, ascending, desc, and descending.";
		return $response;
	}

	if (isset($dateCreatedRange) and $dateCreatedRange) {
		if (gettype($dateCreatedRange) !== "array") {
			$response["status"] = 500;
			$response["error"] = "The dateCreatedRange argument was " .
				"provided to searchPackages, but it was not an array.";
			return $response;
		} else if (count($dateCreatedRange) !== 2) {
			$response["status"] = 500;
			$length = count($dateCreatedRange);
			$response["error"] = "The dateCreatedRange argument was " .
				"provided to searchPackages, but its length is $length, " .
				"not 2.";
			return $response;
		} else if (gettype($dateCreatedRange[0]) !== "integer" or
			gettype($dateCreatedRange[1]) !== "integer")
		{
			$response["status"] = 500;
			$response["error"] = "The dateCreatedRange argument was " .
				"provided to searchPackages, but one or both items are " .
				"not an integer.";
			return $response;
		} else if (!($dateCreatedRange[0] >= 0 and
			$dateCreatedRange[1] >= $dateCreatedRange[0]))
		{
			$response["status"] = 500;
			$response["error"] = "The dateCreatedRange argument was " .
				"provided to searchPackages, but the first element is not " .
				"greater than 0, and/or the second element is not greater " .
				"than the first.";
			return $response;
		}
	}

	if (isset($dateModifiedRange) and $dateModifiedRange) {
		if (gettype($dateModifiedRange) !== "array") {
			$response["status"] = 500;
			$response["error"] = "The dateModifiedRange argument was " .
				"provided to searchPackages, but it was not an array.";
			return $response;
		} else if (count($dateModifiedRange) !== 2) {
			$response["status"] = 500;
			$length = count($dateModifiedRange);
			$response["error"] = "The dateModifiedRange argument was " .
				"provided to searchPackages, but its length was $length, " .
				"not 2.";
			return $response;
		} else if (gettype($dateModifiedRange[0]) !== "integer" or
			gettype($dateModifiedRange[1]) !== "integer")
		{
			$response["status"] = 500;
			$response["error"] = "The dateModifiedRange argument was " .
				"provided to searchPackages, but one or both items is " .
				"not an integer.";
			return $response;
		} else if (!($dateModifiedRange[0] >= 0 and
			$dateModifiedRange[1] >= $dateModifiedRange[0]))
		{
			$response["status"] = 500;
			$response["error"] = "The dateModifiedRange argument was " .
				"provided to searchPackages, but the first element is not " .
				"greater than 0, and/or the second element is not greater " .
				"than the first.";
			return $response;
		}
	}

	if (isset($versionRange) and $versionRange) {
		if (gettype($versionRange) !== "array") {
			$response["status"] = 500;
			$response["error"] = "The versionRange argument was " .
				"provided to searchPackages, but it is not an array.";
			return $response;
		} else if (count($versionRange) !== 2) {
			$response["status"] = 500;
			$length = count($versionRange);
			$response["error"] = "The versionRange argument was " .
				"provided to searchPackages, but its length is $length, " .
				"not 2.";
			return $response;
		} else if (gettype($versionRange[0]) !== "string" or
			gettype($versionRange[1]) !== "string")
		{
			$response["status"] = 500;
			$response["error"] = "The versionRange argument was " .
				"provided to searchPackages, but one or both items is " .
				"not a string.";
			return $response;
		} else if (!($versionRange[1] >= $versionRange[0])) {
			$response["status"] = 500;
			$response["error"] = "The dateModifiedRange argument was " .
				"provided to searchPackages, but the second element was " .
				"not greater than the first.";
			return $response;
		}
	}

	$allSubtypes = array(
		"scripts",
		"styles",
		"macros",
		"passagethemes",
		"storythemes");
	if (isset($subtype)) {
		if (gettype($subtype) !== "string") {
			$response["status"] = 500;
			$response["error"] = "The subtype argument was provided to " .
				"searchPackages, but it was not a string.";
			return $response;
		} else if (!$subtype) {
			$response["status"] = 500;
			$response["error"] = "The subtype argument was provided to " .
				"searchPackages, but it was an empty string.";
			return $response;
		}

		$subtype = strtolower($subtype);
		if (!in_array($subtype, $allSubtypes)) {
			$response["status"] = 500;
			$response["error"] = "The subtype argument was provided to " .
				"searchPackages, but it was not a recognized argument. " .
				"Acceptable arguments are " . join(", ", $allSubtypes) . ".";
			return $response;
		}
	}

	$dsn = "mysql:host=localhost;dbname=twinepm;";
	
	$username = "tpm_packages_get_user";
	$password = trim(file_get_contents(__DIR__ .
		"/../get/tpm_packages_get_user.txt"));

	$db = new PDO($dsn, $username, $password);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$queryStr = "SELECT id, name, description, keywords, type, homepage FROM " .
		"packages ";
	if ($dateCreatedRange or $dateModifiedRange or $versionRange) {
		$queryStr .= "WHERE ";
	}

	$parameters = array();

	if ($dateCreatedRange) {
		$queryStr .= "date_created >= ? AND date_created <= ? ";
		$parameters[] = $dateCreatedRange[0];
		$parameters[] = $dateCreatedRange[1];
	}

	if ($dateModifiedRange) {
		if ($dateCreatedRange) {
			$queryStr .= "AND ";
		}

		$queryStr .= "date_modified >= ? AND date_modified <= ? ";
		$parameters[] = $dateModifiedRange[0];
		$parameters[] = $dateModifiedRange[1];
	}

	if ($versionRange) {
		if ($dateCreatedRange or $dateModifiedRange) {
			$queryStr .= "AND ";
		}

		$queryStr .= "version >= ? AND version <= ? ";
		$parameters[] = $versionRange[0];
		$parameters[] = $versionRange[1];
	}

	if (isset($subtype)) {
		if ($dateCreatedRange or
			$dateModifiedRange or
			$versionRange)
		{
			$queryStr .= "AND ";
		}

		$queryStr .= "type=? ";
		$parameters[] = $subtype;
	}

	$stmt = $db->prepare($queryStr);

	try {
		$stmt->execute($parameters);
	} catch (Exception $e) {
		$response["status"] = 500;
		$response["error"] = "An unknown error was encountered while " .
			"querying the package database in search_packages. Please " .
			"contact webmaster.";
		return $response;
	}

	$results = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if ($results) {
		require_once __DIR__ . "/../globals/filter_and_sort_results.php";

		$results = filterAndSortResults(
			$results,
			$query,
			$filterTargets,
			$filterStyle,
			$sortTarget,
			$sortStyle,
			$sortDirection);
	} else {
		$results = array("results" => array());
	}

	if (isset($results["error"]) or $results["status"] !== 200) {
		$response["status"] = $results["status"];
		$response["error"] = $results["error"];
	} else {
		$response["results"] = $results["results"];
	}

	return $response;
}
?>
