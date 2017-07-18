<?php
require_once __DIR__ . "/../twinepm-credentials/globals/headers.php";

$reqMethod = $_SERVER["REQUEST_METHOD"];
if ($reqMethod === "GET") {
	require_once __DIR__ . "/../twinepm-credentials/get/search.php";
} else if ($reqMethod === "OPTIONS") {
	header("Access-Control-Allow-Methods: GET");
	die(json_encode(array("status" => 200)));
} else {
	http_response_code(400);
	$response = array(
		"status" => 400,
		"error" => "Only GET and OPTIONS requests are " .
			"currently supported."
	);
		
	die(json_encode($response));
}
?>
