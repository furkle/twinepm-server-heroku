<?php
require_once __DIR__ . "/getDatabaseUrl";
function getDatabaseArgs() {
	return parse_url(getDatabaseUrl());
}
?>
