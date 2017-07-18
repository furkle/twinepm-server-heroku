<?php
function makeDSN($prefix, $host, $port, $dbname, $charset) {
	return sprintf("%s:host=%s;port=%s;dbname=%s;",
		$prefix,
		$host, 
		$port,
		$dbname,
		isset($charset) ? $charset : "utf8");
}
?>
