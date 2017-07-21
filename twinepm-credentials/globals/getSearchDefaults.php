<?php
function getSearchDefaults() {
	$defaults = array(
		"type" => array("packages", "users"),
		"filterTargets" => array("id", "name", "description", "homepage", "keywords"),
		"filterTargets" => array("id", "name", "description", "homepage", "keywords"),
		"filterStyles" => array(
			"exact",
			"contains",
			"metaphone/contains",
			"similarity",
			"levenshtein",
			"soundex/levenshtein",
			"metaphone/levenshtein"
		)
	);

	return $defaults;
}
?>
