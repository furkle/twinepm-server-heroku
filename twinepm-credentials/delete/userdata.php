<?php
$response = array("status" => 200);

// get DELETE vars
$_DELETE = json_decode(file_get_contents("php://input"), true);

require_once __DIR__ . "/../globals/token_to_userdata.php";
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

$dsn = "mysql:host=localhost;dbname=twinepm;charset=utf8;";
$username = "tpm_packages_delete_user";
$password = trim(file_get_contents(__DIR__ . "/tpm_packages_delete_user.txt"));

$packagesDB = new PDO($dsn, $username, $password);
$packagesDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $packagesDB->prepare("DELETE FROM packages WHERE author_id=?");
$packagesDB->beginTransaction();

try {
	$stmt->execute(array($_DELETE["id"]));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error deleting entry from userdata " .
		"table. Please contact webmaster.";
	die(json_encode($response));
}

$username = "tpm_userdata_delete_user";
$password = trim(file_get_contents(__DIR__ . "/tpm_userdata_delete_user.txt"));

$userdataDB = new PDO($dsn, $username, $password);
$userdataDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $userdataDB->prepare("DELETE FROM userdata WHERE id=?");
$userdataDB->beginTransaction();

try {
	$stmt->execute(array($_DELETE["id"]));
} catch (Exception $e) {
	$packagesDB->rollBack();

	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error deleting entries from packages " .
		"table. Please contact webmaster.";
	die(json_encode($response));
}

$username = "tpm_passwords_delete_user";
$password = trim(file_get_contents(__DIR__ . "/tpm_passwords_delete_user.txt"));

$passwordsDB = new PDO($dsn, $username, $password);
$passwordsDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $passwordsDB->prepare("DELETE FROM passwords WHERE id=?");
$passwordsDB->beginTransaction();
try {
	$stmt->execute(array($_DELETE["id"]));
} catch (Exception $e) {
	$packagesDB->rollBack();
	$userdataDB->rollBack();

	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error deleting entry from credentials " .
		"table. Please contact webmaster.";
	die(json_encode($response));
}

$packagesDB->commit();
$userdataDB->commit();
$passwordsDB->commit();

die(json_encode($response));
?>
