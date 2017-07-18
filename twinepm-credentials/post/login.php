<?php
$response = array("status" => 200);

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "Only POST can be used to log in.";
	die(json_encode($response));
} else if (!isset($_POST["username"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The username argument was not specified.";
	die(json_encode($response));
} else if (!isset($_POST["password"])) {
	http_response_code(400);
	$response["status"] = 400;
	$response["error"] = "The password argument was not specified.";
	die(json_encode($response));
}

$dsn = "mysql:host=localhost;dbname=twinepm;";

$username = "tpm_passwords_get_user";
$password = trim(file_get_contents(__DIR__ .
	"/../get/tpm_passwords_get_user.txt"));

$db = new PDO($dsn, $username, $password);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$stmt = $db->prepare("SELECT id, name, password, validated FROM passwords " .
	"WHERE id=? OR name=?");

try {
	$stmt->execute(array($_POST["username"], $_POST["username"]));
} catch (Exception $e) {
	http_response_code(500);
	$response["status"] = 500;
	$response["error"] = "Unknown error validating credentials. Please " .
		"contact webmaster.";
	die(json_encode($response));
}

$fetch = $stmt->fetch(PDO::FETCH_ASSOC);

if (isset($fetch["validated"]) and !$fetch["validated"]) {
	http_response_code(400);
	$response["status"] = 400;
    $response["error"] = "Account not validated. Please check your e-mail " .
		"and follow the link therein to validate your account.";
	die(json_encode($response));
} else if ($fetch and password_verify($_POST["password"], $fetch["password"])) {
	require_once __DIR__ . "/../oauth/server.php";

	$_POST["grant_type"] = "client_credentials";
	$_POST["response_type"] = "code";
	$_POST["client_id"] = "tpmOAuth";
	$_POST["client_secret"] = "tpmOAuthSecret";

	$req = $server->handleTokenRequest(OAuth2\Request::createFromGlobals());

	$csrfToken = bin2hex(random_bytes(32));

	$token = array(
		"access_token" => $req->getParameter("access_token"),
		"token_type" => $req->getParameter("token_type"),
		"scope" => $req->getParameter("scope"),
		"expires_in" => $req->getParameter("expires_in"),
		"csrfToken" => $csrfToken
	);

	$username2 = "tpm_tokensanduserids_post_user";
    $password2 = trim(file_get_contents(__DIR__ .
		"/../post/tpm_tokensanduserids_post_user.txt"));

    $otherDB = new PDO($dsn, $username2, $password2);
	$otherDB->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$time = time();
    $stmt = $otherDB->prepare("INSERT INTO tokens_and_userids " .
        "(token, id, time_created) VALUES (?, ?, ?)");

	try {
    	$stmt->execute(array(json_encode($token), $fetch["id"], $time));
	} catch (Exception $e) {
        http_response_code(500);
        $response["status"] = 500;
        $response["error"] = "There was an unknown database error.";
        die(json_encode($response));
    }

	setcookie(
		"twinepm_access_token",
		json_encode($token),
		time() + (int)$token["expires_in"],
		"/twinepm",
		"furkleindustries.com",
		true,
		true);

	$response["csrfToken"] = $csrfToken;
	die(json_encode($response));
} else {
	http_response_code(403);
	$response["status"] = 403;
	$response["error"] = "Invalid credentials.";
	die(json_encode($response));
}
?>
