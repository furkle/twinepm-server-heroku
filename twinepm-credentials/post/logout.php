<?php
$response = array("status" => 200);

setcookie(
	'twinepm_access_token',
	'',
	1,
	'/twinepm',
	'www.furkleindustries.com',
	true,
	true);

require_once __DIR__ . "/../globals/verify_tokens.php";
$verification = verifyTokens();
if (!$verification) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error verifying tokens. Please contact " .
		"webmaster.";
	die(json_encode($response));
} else if (isset($verification["error"])) {
	$status = isset($verification["status"]) ?
		$verification["status"] :
		500;
	http_response_code($status);
	$response["status"] = $status;
	$response["error"] = $verification["error"];
	if (isset($verification["errorCode"])) {
		$response["errorCode"] = $verification["errorCode"];
	}


	die(json_encode($response));
} else if ($verification["status"] !== 200) {
	$status = isset($verification["status"]) ?
		$verification["status"] :
		500;
	http_response_code($status);
	$response["status"] = $status;
	$response["error"] = "The verification did not have a status of 200, but " .
		"there was no error included.";
}

$dsn = "mysql:host=localhost;dbname=twinepm;";
$username = "tpm_tokensanduserids_delete_user";
$password = trim(file_get_contents(__DIR__ .
	"/../delete/tpm_tokensanduserids_delete_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("DELETE FROM tokens_and_userids WHERE token=?");

try {
	$stmt->execute(array($_COOKIE["twinepm_access_token"]));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "An unknown error was encountered while trying to " .
		"delete from the tokens/user database. Please contact webmaster.";
	die(json_encode($response));
}

die(json_encode(array("status" => 200)));
?>
