<?php
$response = array("status" => 200);

// get DELETE vars
$_DELETE = json_decode(file_get_contents("php://input"), true);

require_once "../globals/token_to_userdata.php";
$userdata = tokenToUserdata();
if (!$userdata) {
	http_response_code(500);
	$response["status"] = 500;
	$response["status"] = "Unknown error while converting token to userdata " .
		"in delete/package. Please contact webmaster.";
	die(json_encode($response));
} else if (isset($userdata["error"])) {
	$status = isset($userdata["status"]) ?
		$userdata["status"] :
		500;
	http_response_code($status);
	$response["status"] = $status;
	$response["error"] = $userdata["error"];
	die(json_encode($response));
} else if ($userdata["status"] !== 200) {
	http_response_code($userdataObj["status"]);
	$response["status"] = $userdataObj["status"];
	$response["error"] = "The status received from tokenToUserdata in " .
		"delete/package was not 200, but no error message was included.";
	die(json_encode($response));
} else if (!isset($_DELETE["id"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The id argument was not provided.";
	die(json_encode($response));
}

$dsn = "mysql:host=localhost;dbname=twinepm;charset=utf8;";
$username = "tpm_packages_delete_user";
$password = trim(file_get_contents("../delete/tpm_packages_delete_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT author_id FROM packages WHERE id=?");

try {
	$stmt->execute(array((int)$_DELETE["id"]));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error fetching from packages in " .
		"delete/package. Please contact webmaster.";
	die(json_encode($response));
}

$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fetch) {
	http_response_code(404);
	$response["status"] = 404;
	$response["error"] = "There is no package matching the id argument.";
	die(json_encode($response));
} else if ((int)$fetch["author_id"] !== $userdata["userdata"]["id"]) {
	http_response_code(403);
	$response["status"] = 403;
	$response["error"] = "The package you are trying to delete is owned by " .
		"someone else.";
	die(json_encode($response));
}

$stmt = $db->prepare("DELETE FROM packages WHERE id=?");

try {
	$stmt->execute(array($_DELETE["id"]));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error deleting from packages in " .
		"delete/package. Please contact webmaster.";
	die(json_encode($response));
}

die(json_encode($response));
?>
