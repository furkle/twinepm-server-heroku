<?php
$response = array(
	"status" => 200,
	"userdata" => null);

if (!isset($_GET["id"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The id argument was not provided.";
	die(json_encode($response));
} else if (!ctype_digit($_GET["id"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The id argument was provided, but was not an " .
		"integer.";
	die(json_encode($response));
}

$dsn = "mysql:host=localhost;dbname=twinepm;";

$id = (int)$_GET["id"];

$username = "tpm_userdata_get_user";
$password = trim(file_get_contents(__DIR__ .
	"/../get/tpm_userdata_get_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id, name, name_visible, " .
	"description, date_created, date_created_visible, email, " .
	"email_visible, date_style, time_style FROM userdata WHERE id=?");

try {
	$stmt->execute(array($id));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "An unknown error was encountered while querying " .
		"the userdata table. Please contact webmaster.";
	die(json_encode($response));
}

$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fetch) {
	http_response_code(404);
	$response["status"] = 404;
	$response["error"] = "Entry does not exist in user database in " .
		"profile get.";
	die(json_encode($response));
}

$response["userdata"] = array();

$nameVisible = (int)$fetch["name_visible"];
if ($nameVisible) {
	$response["userdata"]["name"] = $fetch["name"];
}

$dateCreatedVisible = (int)$fetch["date_created_visible"];
if ($dateCreatedVisible) {
	$response["userdata"]["date_created"] = $fetch["date_created"];
}

$response["userdata"]["description"] = $fetch["description"];

$emailVisible = (int)$fetch["email_visible"];
if ($emailVisible) {
	$response["userdata"]["email"] = $fetch["email"];
}

$response["userdata"]["id"] = (int)$fetch["id"];

$username = "tpm_packages_get_user";
$password = trim(file_get_contents(__DIR__ .
	"/../get/tpm_packages_get_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id, name, version, js, css, keywords, " .
	"date_created, date_modified, description, homepage, version, " .
	"type, tag FROM packages WHERE author_id=? " .
	"AND published=1");

try {
	$stmt->execute(array($id));
} catch (Exception $e) {
	$response["status"] = 500;
	$response["error"] = "Unknown error fetching packages in userdata get.";
	die(json_encode($response));
}

$fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

$packages = array();

foreach ($fetchAll as $row) {
	$packages[] = array(
		"id" => (int)$row["id"],
		"name" => $row["name"],
		"js" => $row["js"],
		"css" => $row["css"],
		"keywords" => $row["keywords"],
		"dateCreated" => (int)$row["date_created"],
		"dateModified" => (int)$row["date_modified"],
		"description" => $row["description"],
		"homepage" => $row["homepage"],
		"version" => $row["version"],
		"type" => $row["type"],
		"tag" => $row["tag"]
	);
}

$response["userdata"]["packages"] = $packages;

die(json_encode($response));
?>
