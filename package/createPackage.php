<?php
require_once "/var/www/twinepm-credentials/globals/headers.php";

$reqMethod = $_SERVER['REQUEST_METHOD'];
if ($reqMethod === 'POST') {
	require_once '/var/www/twinepm-credentials/post/create_package.php';
} else if ($reqMethod === 'OPTIONS') {
	header('Access-Control-Allow-Methods: POST');
	die(json_encode(array("status" => 200)));
} else {
	http_response_code(400);
	die('Only POST requests are supported.');
}
?>
