<?php
$response = array("status" => 200);

/* get DELETE vars */
$_DELETE = json_decode(file_get_contents("php://input"), true);

require_once __DIR__ . "/token_to_userdata.php";
$userdata = tokenToUserdata(false, false);
if (!$userdata) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "An unknown error occurred converting the token to " .
		"userdata in delete/userdata. Please contact webmaster.";
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
	http_response_code($userdata["status"]);
	$response["status"] = $userdata["status"];
	$response["error"] = "The status received from tokenToUserdata in " .
		"delete/userdata was not 200, but no error message was included.";
	die(json_encode($response));
} else if ($userdata["userdata"]["id"] !== $_DELETE["id"]) {
	http_response_code(403);
	$response["status"] = 403;
	$response["error"] = "You can't delete other people's profiles.";
	die(json_encode($response));
}

require_once __DIR__ . "/../globals/getDatabaseArgs.php";
$dbArgs = getDatabaseArgs();

require_once __DIR__ . "/../globals/makeTwinepmDSN.php";
$dsn = makeTwinepmDSN();

$id = (int)$_GET["id"];

$username = $dbArgs["user"];
$password = $dbArgs["pass"];

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$db->beginTransaction();

$stmt = $db->prepare("DELETE FROM packages WHERE author_id=?");
try {
	$stmt->execute(array($_DELETE["id"]));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error deleting entry from userdata " .
		"table. Please contact webmaster.";
	die(json_encode($response));
}

$stmt = $db->prepare("DELETE FROM userdata WHERE id=?");
try {
	$stmt->execute(array($_DELETE["id"]));
} catch (Exception $e) {
	$db->rollBack();

	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error deleting entries from packages " .
		"table. Please contact webmaster.";
	die(json_encode($response));
}

$stmt = $db->prepare("DELETE FROM passwords WHERE id=?");
try {
	$stmt->execute(array($_DELETE["id"]));
} catch (Exception $e) {
	$db->rollBack();

	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error deleting entry from credentials " .
		"table. Please contact webmaster.";
	die(json_encode($response));
}

$db->commit();

die(json_encode($response));
?>
