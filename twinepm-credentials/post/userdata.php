<?php
$response = array("status" => 200);

require_once __DIR__ . "/../globals/token_to_userdata.php";
$currentUserdata = tokenToUserdata($_COOKIE["twinepm_access_token"], false);
if (!$currentUserdata) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error converting token to userdata. Please " .
		"notify webmaster.";
	die(json_encode($response));
} else if (isset($currentUserData["error"])) {
	$status = isset($currentUserdata["status"]) ?
		$currentUserdata["status"] :
		500;
	http_response_code($status);
	$response["status"] = $status;
	$response["error"] = $currentUserdata["error"];
	die(json_encode($response));
} else if ($currentUserdata["status"] !== 200) {
	http_response_code($currentUserdata["status"]);
	$response["status"] = $currentUserdata["status"];
	$response["error"] = "The status received from tokenToUserdata in " .
		"post/userdata was not 200, but no error message was included.";
	die(json_encode($response));
}

$id = (int)$currentUserdata["userdata"]["id"];
$name = $currentUserdata["userdata"]["name"];

$dsn = "mysql:host=localhost;dbname=twinepm;charset=utf8;";
$username = "tpm_userdata_post_user";
$password = trim(file_get_contents(__DIR__ .
	"/tpm_userdata_post_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT COUNT(id) FROM userdata WHERE id=?");

try {
	$stmt->execute(array($id));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "An unknown error was encountered while querying " .
		"the userdata database in post/userdata.";
	die(json_encode($response));
}

$fetch = $stmt->fetch(PDO::FETCH_NUM);
if (!(int)$fetch[0]) {
	http_response_code(404);
	$response["status"] = 404;
	$response["error"] = "There is no user with the id $id.";
	die(json_encode($response));
}

$queryStringFirst = "UPDATE userdata SET ";
$queryStringLast = "WHERE id=?";
$queryArray = array();

if (isset($_POST["dateCreatedVisible"])) {
	if ($_POST["dateCreatedVisible"] === "true") {
		$queryArray[] = 1;
	} else if ($_POST["dateCreatedVisible"] === "false") {
		$queryArray[] = 0;
	} else {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The dateCreatedVisible argument was not " .
			"\"true\" or \"false\".";
		die(json_encode($response));
	}

	$queryStringFirst .= "date_created_visible=?, ";
}

if (isset($_POST["name"]) and $_POST["name"] !== $name) {
	$postedName = (bool)$_POST["name"] ? $_POST["name"] : null;

	if (ctype_digit($_POST["name"])) {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "A username cannot be made using only numbers.";
		die(json_encode($response));
	}

	$username2 = "tpm_passwords_post_user";
	$password2 = trim(file_get_contents(__DIR__ .
		"/../post/tpm_passwords_post_user.txt"));
	$db2 = new PDO($dsn, $username2, $password2);
	$db2->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$stmt = $db2->prepare("SELECT COUNT(name) FROM passwords WHERE name=?");

	try {
		$stmt->execute(array($_POST["name"]));
	} catch (Exception $e) {
		http_response_code(500);
		$response["status"] = 500;
		$response["error"] = "There was an unknown error while querying the " .
			"credentials table in post/userdata";
		die(json_encode($response));
	}


	$fetch = $stmt->fetch(PDO::FETCH_NUM);
	if ((int)$fetch[0]) {
		http_response_code(409);
		$response["status"] = 409;
		$response["error"] = "The username $_POST[name] is already in use.";
		die(json_encode($response));
	}

	$stmt = $db2->prepare("UPDATE passwords SET name=? WHERE id=?");

	try {
		$stmt->execute(array($postedName, $id));
	} catch (Exception $e) {
		http_response_code(500);
		$request["status"] = 500;
		$request["error"] = "Unknown failure updating name entry. Please " .
			"contact webmaster.";
		die(json_encode($request));
	}

	$queryStringFirst .= "name=?, ";
	$queryArray[] = $postedName;
}

if (isset($_POST["nameVisible"])) {
	if ($_POST["nameVisible"] === "true") {
		$queryArray[] = 1;
	} else if ($_POST["nameVisible"] === "false") {
		$queryArray[] = 0;
	} else {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The nameVisible argument must be either true " .
			"or false.";
		die(json_encode($response));
	}

	$queryStringFirst .= "name_visible=?, ";
}

if (isset($_POST["description"])) {
	$queryStringFirst .= "description=?, ";
	$queryArray[] = $_POST["description"];
}

if (isset($_POST["email"])) {
	$queryStringFirst .= "email=?, ";
	$queryArray[] = $_POST["email"];
}

if (isset($_POST["emailVisible"])) {
	if ($_POST["emailVisible"] === "true") {
		$queryArray[] = 1;
	} else if ($_POST["emailVisible"] === "false") {
		$queryArray[] = 0;
	} else {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The nameVisible argument must be either true " .
			"or false.";
		die(json_encode($response));
	}

	$queryStringFirst .= "email_visible=?, ";
}

if (isset($_POST["homepage"])) {
	$queryStringFirst .= "homepage=?, ";
	$queryArray[] = $_POST["homepage"];
}

if (isset($_POST["dateStyle"])) {
	if ($_POST["dateStyle"] === "mmdd" or $_POST["dateStyle"] === "ddmm") {
		$queryStringFirst .= "date_style=?, ";
		$queryArray[] = $_POST["dateStyle"];
	} else {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The dateStyle argument was provided, but was " .
			"neither \"mmdd\" nor \"ddmm\".";
		die(json_encode($response));
	}
}

if (isset($_POST["timeStyle"])) {
	if ($_POST["timeStyle"] === "12h" or $_POST["timeStyle"] === "24h") {
		$queryStringFirst .= "time_style=?, ";
		$queryArray[] = $_POST["timeStyle"];
	} else {
		http_response_code(400);
		$response["status"] = 400;
		$response["error"] = "The timeStyle argument was provided, but was " .
			"neither \"12h\" nor \"24h\".";
		die(json_encode($response));
	}
}

if (count($queryArray) === 0) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "No arguments were submitted to be updated.";
	die(json_encode($response));
}

// delete comma and space
$queryStringFirst =
	substr($queryStringFirst, 0, strlen($queryStringFirst) - 2) . " ";
$queryString = $queryStringFirst . $queryStringLast;
$queryArray[] = $id;

$stmt = $db->prepare($queryString);

try {
	$stmt->execute($queryArray);
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error updating userdata entry. Please " .
		"contact webmaster.";
	die(json_encode($response));
}

die(json_encode($response));
?>
