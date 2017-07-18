<?php
require_once __DIR__ . "/getDatabaseUrl.php";
function getDatabaseArgs() {
	return parse_url(getDatabaseUrl());
}
?>
