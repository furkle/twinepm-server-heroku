<?php
require_once "/var/www/twinepm-credentials/globals/headers.php";

$reqMethod = $_SERVER["REQUEST_METHOD"];
if ($reqMethod === "GET") {
	require_once "/var/www/twinepm-credentials/get/profile.php";
} else if ($reqMethod === "POST") {
	require_once "/var/www/twinepm-credentials/post/profile.php";
} else if ($reqMethod === "OPTIONS") {
	header("Access-Control-Allow-Methods: GET, POST");
	die(json_encode(array("status" => 200)));
} else {
	http_response_code(400);
	$response = array(
		"status" => 400,
		"error" => "Only GET, POST, and OPTIONS requests are " .
			"currently supported."
	);
		
	die(json_encode($response));
}
?>
