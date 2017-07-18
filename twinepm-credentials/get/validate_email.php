<?php
if (!isset($_GET["id"]) or !$_GET["id"]) {
	http_response_code(400);
	die("The id argument was not provided.");
} else if (!isset($_GET["token"]) or !$_GET["token"]) {
	http_response_code(400);
	die("The token argument was not provided.");
}

require_once __DIR__ . "/../globals/reap_unclaimed_reservations.php";
$reapResult = reapUnclaimedReservations();
if (isset($reapResult["error"])) {
	file_put_contents(__DIR__ . "/validation_reap_log.txt",
		$reapResult["error"] . " | " . time() . "\n",
		FILE_APPEND | LOCK_EX);
}

$dsn = "mysql:host=localhost;dbname=twinepm;";
$username = "tpm_emailvalidation_get_user";
$password = trim(file_get_contents(__DIR__ . 
	"/../get/tpm_emailvalidation_get_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT COUNT(id) FROM email_validation " .
	"WHERE id=? and token=?");

try {
	$stmt->execute(array((int)$_GET["id"], $_GET["token"]));
} catch (Exception $e) {
	http_response_code(500);
	die("An error was encountered while looking up the provided token.");
}

$fetch = $stmt->fetch(PDO::FETCH_NUM);
if (!(int)$fetch[0]) {
	http_response_code(404);
	die("No records can be found matching that name and token.");
}

$username = "tpm_passwords_post_user";
$password = trim(file_get_contents(__DIR__ . 
	"/../post/tpm_passwords_post_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("UPDATE passwords SET validated=1 WHERE id=?");
$db->beginTransaction();

try {
	$stmt->execute(array((int)$_GET["id"]));
} catch (Exception $e) {
	http_response_code(500);
	die("There was an error setting the validation flag.");
}

$username = "tpm_emailvalidation_delete_user";
$password = trim(file_get_contents(__DIR__ .
	"/../delete/tpm_emailvalidation_delete_user.txt"));

$emailVerificationDB = new PDO($dsn, $username, $password);
$emailVerificationDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $emailVerificationDB->prepare("DELETE FROM email_validation " .
	"WHERE id=?");
try {
	$stmt->execute(array($_GET["id"]));
} catch (Exception $e) {
	$db->rollBack();
	http_response_code(500);
	die("There was an error deleting from the email validation table.");
}

$db->commit();

die("Thank you for validating your e-mail. You may now log into TwinePM.\n" .
	"If you registered without a username, use the ID: <b>$_POST[id]</b>.");
?>
