<?php
$response = array("status" => 200);

require_once __DIR__ . "/../globals/token_to_userdata.php";
$currentUserdata = tokenToUserdata($_COOKIE["twinepm_access_token"], false);
if (!$currentUserdata) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error converting token to userdata in " .
		"post/package. Please contact webmaster.";
	die(json_encode($response));
} else if (isset($currentUserdata["error"])) {
	$status = isset($currentUserdata["status"]) ?
		$currentUserdata["status"] :
		500;
	http_response_code($status);
	$response["status"] = $currentUserdata["status"];
	$response["error"] = $currentUserdata["error"];
	die(json_encode($response));
} else if ($currentUserdata["status"] !== 200) {
	http_response_code($currentUserdata["status"]);
	$response["status"] = $currentUserdata["status"];
	$response["error"] = "The status received from the token to userdata " .
		"query was not 200, but no error was included.";
	die(json_encode($response));
}

$authorId = $currentUserdata["userdata"]["id"];
$packageId = $_POST["id"];

$dsn = "mysql:host=localhost;dbname=twinepm;charset=utf8;";

$username = "tpm_packages_post_user";
$password = trim(file_get_contents(__DIR__ .
	"/../post/tpm_packages_post_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT name, author_id FROM packages WHERE id=?");

try {
	$stmt->execute(array($packageId));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error fetching package from database. " .
		"Please contact webmaster.";
	die(json_encode($response));
}

$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$fetch) {
	http_response_code(404);
	$response["status"] = 404;
	$response["error"] = "There is no package bearing the id $packageId.";
	die(json_encode($response));
} else if ((int)$fetch["author_id"] !== (int)$authorId) {
	http_response_code(403);
	$response["status"] = 403;
	$response["error"] = "The package you are trying to modify is not owned " .
		"by you.";
	die(json_encode($response));
}

$packageOldName = $fetch["name"];

$queryStringFirst = "UPDATE packages SET ";
$queryStringLast = "WHERE id=?";
$queryArray = array();

if (isset($_POST["name"]) and $_POST["name"] !== $packageOldName) {
	$stmt = $db->prepare("SELECT COUNT(name) FROM packages WHERE name=?");

	try {
		$stmt->execute(array($_POST["name"]));
	} catch (Exception $e) {
		http_response_code(500);
		$response["status"] = 500;
		$response["error"] = "Unknown error fetching package metadata from " .
			"database. Please contact webmaster.";
		die(json_encode($response));
	}

	$fetch = $stmt->fetch(PDO::FETCH_NUM);
	if ($fetch[0]) {
		http_response_code(409);
		$response["status"] = 409;
		$response["error"] = "There is already a package using that name.";
		die(json_encode($response));
	}

	$queryStringFirst .= "name=?, ";
	$queryArray[] = $_POST["name"];
}

if (isset($_POST["js"])) {
	$queryStringFirst .= "js=?, ";
	$queryArray[] = $_POST["js"];
}

if (isset($_POST["css"])) {
	$queryStringFirst .= "css=?, ";
	$queryArray[] = $_POST["css"];
}

if (isset($_POST["keywords"])) {
	$queryStringFirst .= "keywords=?, ";
	$queryArray[] = $_POST["keywords"];
}

if (isset($_POST["description"])) {
	$queryStringFirst .= "description=?, ";
	$queryArray[] = $_POST["description"];
}

if (isset($_POST["homepage"])) {
	$queryStringFirst .= "homepage=?, ";
	$queryArray[] = $_POST["homepage"];
}

if (isset($_POST["version"])) {
	$queryStringFirst .= "version=?, ";
	$queryArray[] = $_POST["version"];
}

if (isset($_POST["type"])) {
	$queryStringFirst .= "type=?, ";
	$queryArray[] = $_POST["type"];
}

if (isset($_POST["tag"])) {
	$queryStringFirst .= "tag=?, ";
	$queryArray[] = $_POST["tag"];
}

if (isset($_POST["published"])) {
	if ($_POST["published"] === "true") {
		$queryArray[] = 1;
	} else if ($_POST["published"] === "false") {
		$queryArray[] = 0;
	} else {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The value of the published argument was " .
			"neither \"true\" nor \"false\".";
		die(json_encode($response));
	}

	$queryStringFirst .= "published=?, ";
}



if (count($queryArray) === 0) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "There were no fields provided for updating the " .
		"package.";
	die(json_encode($response));
}

$dateModified = time();

$queryStringFirst .= "date_modified=?, ";
$queryArray[] = $dateModified;

// delete comma and space
$queryStringFirst =
	substr($queryStringFirst, 0, strlen($queryStringFirst) - 2) . " ";
$queryString = $queryStringFirst . $queryStringLast;
$queryArray[] = $packageId;

$stmt = $db->prepare($queryString);

try {
	$stmt->execute($queryArray);
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error updating package entry. Please " .
		"contact webmaster.";
	die(json_encode($response));
}

$response["dateModified"] = $dateModified;

die(json_encode($response));
?>
