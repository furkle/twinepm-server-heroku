<?php
$response = array("status" => 200);

if (!isset($_POST["name"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The name argument was not provided.";
	die(json_encode($response));
} else if (!isset($_POST["password"]) or !$_POST["password"]) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The password argument was not provided.";
	die(json_encode($response));
} else if (!isset($_POST["email"]) or !$_POST["email"]) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The email argument was not provided.";
	die(json_encode($response));
}

$dsn = "mysql:host=localhost;dbname=twinepm;";

$username = "tpm_passwords_post_user";
$password = trim(file_get_contents(__DIR__ .
	"/../post/tpm_passwords_post_user.txt"));

$passwordsDB = new PDO($dsn, $username, $password);
$passwordsDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $passwordsDB->prepare("SELECT COUNT(*) FROM passwords " .
	"WHERE LCASE(name)=?");

try {
	$stmt->execute(array(strtolower($_POST["name"])));
} catch (Exception $e) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "An unknown error was encountered ascertaining " .
		"whether the username you selected is already taken. Please " .
		"contact webmaster.";
	die(json_encode($response));
}

$fetch = $stmt->fetch(PDO::FETCH_NUM);
if ((int)$fetch[0]) {
	http_response_code(409);
	$response["status"] = 409;
	$response["error"] = "The username you selected is already taken.";
	die(json_encode($response));
}

$name = $_POST["name"] ? $_POST["name"] : null;

$stmt = $passwordsDB->prepare("INSERT INTO passwords (name, password) " .
	"VALUES (?, ?)");

try {
	$stmt->execute(array(
		$name,
		password_hash($_POST["password"], PASSWORD_DEFAULT)
	));
} catch (Exception $e) {
    http_response_code(500);
    $response["status"] = 500;
    $response["error"] = "Unknown error creating credentials entry. Please " . 
        "contact webmaster.";
    die(json_encode($response));
}

$id = (int)$passwordsDB->lastInsertId();

$username = "tpm_userdata_post_user";
$password = trim(file_get_contents(__DIR__ .
	"/../post/tpm_userdata_post_user.txt"));

$userdataDB = new PDO($dsn, $username, $password);
$userdataDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $userdataDB->prepare("INSERT INTO userdata " .
    "(id, name, name_visible, description, email, email_visible, homepage, " .
		"date_created, date_created_visible, date_style, time_style) " .
	"VALUES (?, ?, 0, '', ?, 0, '', ?, 0, 'mmdd', '12h')");

try {
	$stmt->execute(array($id, $name, $_POST["email"], time()));
} catch (Exception $e) {
	$passwordsDB->rollBack();

    http_response_code(500);
	$response["status"] = 500;
    $response["error"] = "Unknown error adding userdata. Please contact " .
		"webmaster.";
	die(json_encode($response));
}

$username = "tpm_emailvalidation_post_user";
$password = trim(file_get_contents(__DIR__ .
	"/../post/tpm_emailvalidation_post_user.txt"));
$emailValidationDB = new PDO($dsn, $username, $password);

$stmt = $emailValidationDB->prepare("INSERT INTO email_validation " .
	"(id, token, time_reserved) VALUES (?, ?, ?)");
$token = bin2hex(random_bytes(32));
$stmt->execute(array($id, $token, time()));

if ((int)$stmt->errorCode()) {
	$userdataDB->rollBack();
	$passwordsDB->rollBack();

	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error adding e-mail to validation " .
		"table. Please contact webmaster.";
	die(json_encode($response));
}

mail($_POST["email"],
	"Validate TwinePM E-mail",
	"Please follow this link to activate your account: " .
		"https://furkleindustries.com/twinepm/login/" .
			"validateEmail.php?token=$token&id=$id",
	"From: no-reply@furkleindustries.com");

die(json_encode($response));
?>
