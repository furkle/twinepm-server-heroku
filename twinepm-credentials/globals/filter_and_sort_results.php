<?php
function filterAndSortResults($results, $query, $filterTargets, $filterStyle, $sortTarget, $sortStyle, $sortDirection) {
	$response = array("status" => 200);

	if (!isset($results)) {
		$response["status"] = 500;
		$response["error"] = "The results argument was not provided to " .
			"filterAndSortResults.";
		return $response;
	} else if (gettype($results) !== "array") {
		$response["status"] = 500;
		$response["error"] = "The results argument was provided to " .
			"filterAndSortResults, but the argument was not an array.";
		return $response;
	}

	if (!isset($query)) {
		$response["status"] = 500;
		$response["error"] = "The query argument was not provided to " .
			"filterAndSortResults.";
		return $response;
	}

	if (!$filterTargets) {
		$response["status"] = 500;
		$response["error"] = "The filterTargets argument was not provided " .
			"to filterAndSortResults.";
		return $response;
	} else if (gettype($filterTargets) !== "array") {
		$response["status"] = 500;
		$response["error"] = "The filterTargets argument was provided to " .
			"filterAndSortResults, but it was not an array.";
		return $response;
	} else if (count($filterTargets) === 0) {
		$response["status"] = 500;
		$response["error"] = "The filterTargets argument was provided to " .
			"filterAndSortResults, but it was an empty array.";
		return $response;
	}

	$allFilterTargets = array(
		"id",
		"name",
		"description",
		"keywords",
		"homepage"
	);
	foreach ($filterTargets as $value) {
		if (!in_array($value, $allFilterTargets)) {
			$response["status"] = 500;
			$response["error"] = "The fieldsToSearch argument was provided " .
				"to filterAndSortResults, but contained an unknown " .
				"argument: $value. The acceptable arguments are " .
				join(", ", $allFilterTargets) . ".";
			return $response;
		}
	}

	if (!isset($filterStyle)) {
		$response["status"] = 500;
		$response["error"] = "The filterStyle argument was not provided to " .
			"filterAndSortResults.";
		return $response;
	} else if (gettype($filterStyle) !== "string") {
		$response["status"] = 500;
		$response["error"] = "The filterStyle argument was provided to " .
			"filterAndSortResults, but it was not a string.";
		return $response;
	} else if (!$filterStyle) {
		$response["status"] = 500;
		$response["error"] = "The filterStyle argument was provided to " .
			"filterAndSortResults, but it was an empty string.";
	}

	$filterStyle = strtolower($filterStyle);
	$allFilterStyles = array(
		"exact",
		"contains",
		"metaphone/contains",
		"similarity",
		"levenshtein",
		"soundex/levenshtein",
		"metaphone/levenshtein"
	);

	if (!in_array($filterStyle, $allFilterStyles)) {
		$response["status"] = 500;
		$response["error"] = "The filterStyle argument was provided to " .
			"filterAndSortResults, but it was not a recognized argument. " .
			"Acceptable arguments are: " . join(", ", $allFilterStyles) . ".";
		return $response;
	}

	if (!isset($sortStyle) or !$sortStyle) {
		return array(
			"status" => 500,
			"error" => "The sortStyle argument was not provided to " .
				"filterAndSortResults.");
	} else if (gettype($sortStyle) !== "string") {
		$response["status"] = 500;
		$response["error"] = "The sortStyle argument was provided to " .
			"filterAndSortResults, but it was not a string.";
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
			"filterAndSortResults, but was not one of the following: " .
			join(", ", $allSortStyles) . ".";
		return $response;
	}

	if (!$sortDirection) {
		return array(
			"status" => 500,
			"error" => "The sortDirection argument was not provided to " .
				"sortResults.");
	} else if ($sortDirection !== "asc" and
		$sortDirection !== "ascending" and
		$sortDirection !== "desc" and
		$sortDirection !== "descending")
	{
		$response["status"] = 500;
		$response["error"] = "The sortDirection argument was provided to " .
			"sortResults, but was not asc, ascending, desc, or " .
			"descending.";
		return $response;
	}

	function filterExact(&$results, $query, $filterTargets) {
		$results = array_filter($results,
			function ($row) use ($query, $filterTargets) {
				if ($query === "" or $query === "*") {
					return true;
				}

				foreach ($filterTargets as $value) {
					if (isset($row[$value]) and
						strtolower($row[$value]) === strtolower($query))
					{
						return true;
					}
				}

				return false;
			}
		);
	}
	
	function filterContains(&$results, $query, $filterTargets) {
		$results = array_filter($results,
			function ($row) use ($query, $filterTargets) {
				if ($query === "" or $query === "*") {
					return true;
				}

				foreach ($filterTargets as $value) {
					if (isset($row[$value]) and
						strpos(strtolower($row[$value]),
							strtolower($query)) !== false)
					{
						return true;
					}
				}

				return false;
			}
		);	
	}

	function filterMetaphoneContains(&$results, $query, $filterTargets) {
		$results = array_filter($results,
			function ($row) use ($query, $filterTargets) {
				if ($query === "" or $query === "*") {
					return true;
				}

				foreach ($filterTargets as $value) {
					if (isset($row[$value]) and
						strpos(metaphone($row[$value]),
							metaphone($query)) !== false)
					{
						return true;
					}
				}

				return false;
			}
		);
	}

	function filterSimilarity(&$results, $query, $filterTargets) {
		$MIN_SIMILARITY = 45;

		$results = array_filter($results,
			function ($row) use ($query, $filterTargets, $MIN_SIMILARITY) {
				if ($query === "" or $query === "*") {
					return true;
				}

				foreach ($filterTargets as $value) {
					if (isset($row[$value])) {
						$percent = null;
						similar_text($query, $row[$value], $percent);
						if ($percent >= $MIN_SIMILARITY) {
							return true;
						}
					}
				}

				return false;
			}
		);
	}

	function filterLevenshtein(&$results, $query, $filterTargets) {
		$MAX_LEVENSHTEIN = 5;

		$results = array_filter($results,
			function ($row) use ($query, $filterTargets, $MIN_LEVENSHTEIN) {
				if ($query === "" or $query === "*") {
					return true;
				}

				foreach ($filterTargets as $value) {
					if (isset($row[$value])) {
						$levenshtein = levenshtein($query, $row[$value]);
						if ($levenshtein > $MAX_LEVENSHTEIN) {
							return true;
						}
					}
				}

				return false;
			}
		);
	}

	function filterSoundexLevenshtein(&$results, $query, $filterTargets) {
		$MAX_SOUNDEX_LEVENSHTEIN = 5;

		$results = array_filter($results,
			function ($row) use ($query,
									$filterTargets,
									$MAX_SOUNDEX_LEVENSHTEIN)
			{
				if ($query === "" or $query === "*") {
					return true;
				}

				foreach ($filterTargets as $value) {
					if (isset($row[$value])) {
						$soundexQuery = soundex($query);
						$soundexValue = soundex($row[$value]);
						$soundexLevenshtein = levenshtein(
							$soundexQuery,
							$soundexValue);

						if ($soundexLevenshtein < $MAX_SOUNDEX_LEVENSHTEIN) {
							return true;
						}
					}
				}
			}
		);
	}

	function filterMetaphoneLevenshtein(&$results, $query, $filterTargets) {
		$MAX_METAPHONE_LEVENSHTEIN = 5;

		$results = array_filter($results,
			function ($row) use ($query,
									$filterTargets,
									$MAX_METAPHONE_LEVENSHTEIN)
			{
				if ($query === "" or $query === "*") {
					return true;
				}

				foreach ($filterTargets as $value) {
					if (isset($row[$value])) {
						$metaphoneQuery = metaphone($query);
						$metaphoneValue = metaphone($row[$value]);
						$metaphoneLevenshtein = levenshtein(
							$metaphoneQuery,
							$metaphoneValue);

						if ($metaphoneLevenshtein <
							$MAX_METAPHONE_LEVENSHTEIN)
						{
							return true;
						}
					}
				}
			}
		);
	}

	switch ($filterStyle) {
		case "exact":
			filterExact(
				$results,
				$query,
				$filterTargets);
			break;
		case "contains":
			filterContains(
				$results,
				$query,
				$filterTargets);
			break;
		case "metaphone/contains":
			filterMetaphoneContains(
				$results,
				$query,
				$filterTargets);
			break;
		case "similarity":
			filterSimilarity(
				$results,
				$query,
				$filterTargets);
			break;
		case "levenshtein":
			filterLevenshtein(
				$results,
				$query,
				$filterTargets);
			break;
		case "soundex/levenshtein":
			filterSoundexLevenshtein(
				$results,
				$query,
				$filterTargets);
			break;
		case "metaphone/levenshtein":
			filterMetaphoneLevenshtein(
				$results,
				$query,
				$filterTargets);
			break;
		default:
			$response["status"] = 500;
			$response["error"] = "An unrecognized filterStyle, $filterStyle, " .
				"was received by the switch statement.";
			die(json_encode($response));
	}

	function sortAlphanumeric(&$results, $sortTarget, $sortDirection) {
		usort($results, function ($a, $b) use ($sortTarget, $sortDirection) {
			$comp = strcasecmp($a[$sortTarget], $b[$sortTarget]);

			if ($comp > 0) {
				if ($sortDirection === "asc" or
					$sortDirection === "ascending")
				{
					return 1;
				} else {
					return -1;
				}
			} else if ($comp < 0) {
				if ($sortDirection === "asc" or
					$sortDirection === "ascending")
				{
					return -1;
				} else {
					return 1;
				}
			} else {
				return 0;
			}
		});
	}

	function sortSimilarity(&$results, $query, $sortTarget, $sortDirection) {
		usort($results, 
			function ($a, $b) use ($query, $sortTarget, $sortDirection) {
				$similarityA = null;
				similar_text($query, $a[$sortTarget], $similarityA);

				$similarityB = null;
				similar_text($query, $b[$sortTarget], $similarityB);

				if ($similarityA > $similarityB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")
					{
						return 1;
					} else {
						return -1;
					}
				} else if ($similarityA < $similarityB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")
					{
						return -1;
					} else {
						return 1;
					}
				} else {
					return 0;
				}
			}
		);
	}

	function sortLevenshtein(&$results, $query, $sortTarget, $sortDirection) {
		usort($results,
			function ($a, $b) use ($query, $sortTarget, $sortDirection) {
				$levenshteinA = levenshtein($query, $a[$sortTarget]);
				$levenshteinB = levenshtein($query, $b[$sortTarget]);

				if ($levenshteinA < $levenshteinB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")
					{
						return 1;
					} else {
						return -1;
					}
				} else if ($levenshteinA > $levenshteinB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")
					{
						return -1;
					} else {
						return 1;
					}
				} else {
					return 0;
				}
			}
		);
	}

	function sortSoundexLevenshtein(&$results, $query, $sortTarget, $sortDirection) {
		usort($results,
			function ($a, $b) use ($query, $sortTarget, $sortDirection) {
				$soundexA = soundex($a[$sortTarget]);
				$soundexLevenshteinA = levenshtein(soundex($query), $soundexA);

				$soundexB = soundex($b[$sortTarget]);
				$soundexLevenshteinB = levenshtein(soundex($query), $soundexB);

				if ($soundexLevenshteinA < $soundexLevenshteinB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")
					{
						return 1;
					} else {
						return -1;
					}
				} else if ($soundexLevenshteinA > $soundexLevenshteinB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")
					{
						return -1;
					} else {
						return 1;
					}
				}
			}
		);
	}

	function sortMetaphoneLevenshtein(&$results, $query, $sortTarget, $sortDirection) {
		usort($results,
			function ($a, $b) use ($query, $sortTarget, $sortDirection) {
				$metaphoneA = metaphone($a[$sortTarget]);
				$metaphoneLevenshteinA = levenshtein(
					metaphone($query),
					$metaphoneA);
				
				$metaphoneB = metaphone($b[$sortTarget]);
				$metaphoneLevenshteinB = levenshtein(
					metaphone($query),
					$metaphoneB);

				if ($metaphoneLevenshteinA < $metaphoneLevenshteinB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")
					{
						return 1;
					} else {
						return -1;
					}
				} else if ($metaphoneLevenshteinA > $metaphoneLevenshteinB) {
					if ($sortDirection === "asc" or
						$sortDirection === "ascending")

					{
						return -1;
					} else {
						return 1;
					}
				}
			}
		);
	}

	switch ($sortStyle) {
		case "alphanumeric":
			sortAlphanumeric(
				$results,
				$sortTarget,
				$sortDirection);
			break;
		case "similarity":
			sortSimilarity(
				$results,
				$query,
				$sortTarget,
				$sortDirection);
			break;
		case "levenshtein":
			sortLevenshtein(
				$results,
				$query,
				$sortTarget,
				$sortDirection);
			break;
		case "soundex/levenshtein":
			sortSoundexLevenshtein(
				$results,
				$query,
				$sortTarget,
				$sortDirection);
			break;
		case "metaphone/levenshtein":
			sortMetaphoneLevenshtein(
				$results,
				$query,
				$sortTarget,
				$sortDirection);
			break;
	}

	if ($response["status"] === 200 and !isset($response["error"])) {
		$response["results"] = $results;
	}

	return $response;
}
?>
