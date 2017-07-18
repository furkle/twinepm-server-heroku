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

require_once __DIR__ . "/../globals/getDatabaseArgs.php";
$dbArgs = getDatabaseArgs();

require_once __DIR__ . "/../globals/makeDSN.php";
$prefix = "pgsql";
$charset = "utf8";
$dsn = makeDSN(
	$prefix,
	$dbArgs["host"],
	$dbArgs["port"],
	$dbArgs["dbname"],
	$charset);

$id = (int)$_GET["id"];

$username = $dbArgs["user"];
$password = $dbArgs["password"]

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id, name, version, js, css, keywords, " .
	"date_created, date_modified, description, homepage, version, " .
	"type, tag FROM packages WHERE id=? " .
	"AND published=1");

try {
	$stmt->execute(array($id));
} catch (Exception $e) {
	$response["status"] = 500;
	$response["error"] = "Unknown error fetching packages in profile get.";
	return $response;
}

$fetch = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$fetch) {
	http_response_code(404);
	$response["status"] = 404;
	$response["error"] = "There is no package with the id $id, or it is not " .
		"currently published.";
	die(json_encode($response));
}

$package = array(
	"id" => (int)$fetch["id"],
	"name" => $fetch["name"],
	"js" => $fetch["js"],
	"css" => $fetch["css"],
	"keywords" => $fetch["keywords"],
	"dateCreated" => (int)$fetch["date_created"],
	"dateModified" => (int)$fetch["date_modified"],
	"description" => $fetch["description"],
	"homepage" => $fetch["homepage"],
	"version" => $fetch["version"],
	"type" => $fetch["type"],
	"tag" => $fetch["tag"],
);

$response["package"] = $package;

die(json_encode($response));
?>
