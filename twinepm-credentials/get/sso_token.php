<?php
$response = array("status" => 200);

require_once __DIR__ . '/../jsconnect/functions.jsconnect.php';

// 1. Get your client ID and secret here. These must match those in your jsConnect settings.
$clientID = "2085265443";
$secret = "f3ab34efe54dc28164b8a7aee7177430";

// 2. Grab the current user from your session management system or database here.
require_once __DIR__  . "/../globals/token_to_userdata.php";
$userdataObj = tokenToUserdata(false, "omitAntiCSRFToken");

// 3. Fill in the user information in a way that Vanilla can understand.
$user = array();

if (!isset($userdataObj["error"]) and $userdataObj["status"] === 200) {
	// CHANGE THESE FOUR LINES.
	$user["uniqueid"] = $userdataObj["userdata"]["id"];
	$user["name"] = $userdataObj["userdata"]["name"];
	$user["email"] = $userdataObj["userdata"]["email"];
	$user["photourl"] = "";
}

// 4. Generate the jsConnect string.

// This should be true unless you are testing.
// You can also use a hash name like md5, sha1 etc which must be the name as the connection settings in Vanilla.
$secure = "sha512";

$jsConnectObject = getJsConnectObject(
	$user,
	$_GET,
	$clientID,
	$secret,
	$secure);

die($_GET["callback"] . "(" . json_encode($jsConnectObject) . ")");
