<?php
require_once __DIR__ . "/../twinepm-credentials/globals/headers.php";

$reqMethod = $_SERVER["REQUEST_METHOD"];
if ($reqMethod === "GET") {
	require_once __DIR__ . "/../twinepm-credentials/get/package.php";
} else if ($reqMethod === "POST") {
	require_once __DIR__ . "/../twinepm-credentials/post/package.php";
} else if ($reqMethod === "DELETE") {
	require_once __DIR__ . "/../twinepm-credentials/delete/package.php";
} else if ($reqMethod === "OPTIONS") {
	header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
	die(json_encode(array("status" => 200)));
} else {
	http_response_code(400);
	die("Only GET, POST, DELETE, and OPTIONS requests are currently supported.");
}
?>
