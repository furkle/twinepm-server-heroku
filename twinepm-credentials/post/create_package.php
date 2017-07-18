<?php
$response = array("status" => 200);

require_once __DIR__ . "/../globals/token_to_userdata.php";
$userdata = tokenToUserdata($_COOKIE["twinepm_access_token"], false);
if (!$userdata) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error fetching userdata. Please contact " .
		"webmaster.";
	die(json_encode($response));
} else if (isset($userdata["error"])) {
	$status = isset($userdata["status"]) ? $userdata["status"] : 500;
	http_response_code($status);
	$response["status"] = $status;
	$response["error"] = $userdata["error"];
	die(json_encode($response));
} else if ($userdata["status"] !== 200) {
	http_response_code($userdata["status"]);
	$response["status"] = $userdata["status"];
	$response["error"] = "The status of the createPackage request was not " .
		"200, but no error was included.";
	die(json_encode($response));
}  else if (!isset($userdata["userdata"])) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Userdata was fetched, but did not contain a " .
		"userdata property.";
	die(json_encode($response));
} else if (!isset($userdata["userdata"]["id"])) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Userdata was fetched, but did not contain an id " .
		"property. Please contact webmaster.";
	die(json_encode($response));
}

if (!isset($_POST["name"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The name argument was not provided.";
	die(json_encode($response));
} else if (ctype_digit($_POST["name"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The name argument may not be only numerals.";
	die(json_encode($response));
} else if (!$_POST["name"]) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The name argument was empty.";
	die(json_encode($response));
}  else if (!isset($_POST["type"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The type argument was not provided.";
	die(json_encode($response));
} else if (!$_POST["type"]) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The type argument was empty.";
	die(json_encode($response));
} else if (!isset($_POST["version"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The version argument was not provided.";
	die(json_encode($response));
} else if (!$_POST["version"]) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The version argument was empty.";
	die(json_encode($response));
} else if (!isset($_POST["description"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The description argument was not provided.";
	die(json_encode($response));
} else if (!$_POST["description"]) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The description argument was empty.";
	die(json_encode($response));
}

$params = array(
	$_POST["name"],
	$_POST["type"],
	$_POST["version"],
	$_POST["description"],
	isset($_POST["homepage"]) ? $_POST["homepage"] : "",
	isset($_POST["js"]) ? $_POST["js"] : "",
	isset($_POST["css"]) ? $_POST["css"] : "",
	isset($_POST["keywords"]) ? $_POST["keywords"] : "",
	isset($_POST["tag"]) ? $_POST["tag"] : "",
	time(),
	time(),
	$userdata["userdata"]["id"],
	0
);

$dsn = "mysql:host=localhost;dbname=twinepm;charset=utf8;";
$username = "tpm_packages_post_user";
$password = trim(file_get_contents(__DIR__ . 
	"/../post/tpm_packages_post_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT COUNT(name) FROM packages WHERE name=?");
$stmt->execute(array($_POST["name"]));
$fetch = $stmt->fetch(PDO::FETCH_NUM);

if ($fetch[0] > 0) {
	http_response_code(409);
	$response["status"] = 409;
	$response["error"] = "There is already a package with this name.";
	die(json_encode($response));
}

$stmt = $db->prepare("INSERT INTO packages " .
	"(name, type, version, description, homepage, js, " .
		"css, keywords, tag, date_modified, date_created, author_id, " .
		"published) " .
	"VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

try {
	$stmt->execute($params);
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error fetching package from database. " .
		"Please contact webmaster.";
	die(json_encode($response));
}

die(json_encode($response));
?>
