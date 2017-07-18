<?php
function verifyTokens($omitAntiCSRFToken) {
	$_DELETE = json_decode(file_get_contents("php://input"), true);

	$response = array("status" => 200);

	if (!isset($_COOKIE["twinepm_access_token"])) {
		$response["status"] = 403;
		$response["error"] = "You must be logged in to perform this action.";
		$response["errorCode"] = "no_access_cookie";
		return $response;
	}

	if ($omitAntiCSRFToken !== true and
		$omitAntiCSRFToken !== "omitCSRFToken")
	{
		if (!isset($_GET["csrfToken"]) and
			!isset($_POST["csrfToken"]) and
			!isset($_DELETE["csrfToken"]))
		{
			$response["status"] = 400;
			$response["error"] = "The csrfToken argument was not provided.";
			$response["errorCode"] = "no_anti_csrf_token";
			return $response;
		}
	}

	$token = json_decode($_COOKIE["twinepm_access_token"], true);

	if ($omitAntiCSRFToken !== true and
		$omitAntiCSRFToken !== "omitCSRFToken")
	{
		$antiCSRFToken = null;
		if (isset($_GET["csrfToken"])) {
			$antiCSRFToken = $_GET["csrfToken"];
		} else if (isset($_POST["csrfToken"])) {
			$antiCSRFToken = $_POST["csrfToken"];
		} else {
			$antiCSRFToken = $_DELETE["csrfToken"];
		}

		if ($token["csrfToken"] !== $antiCSRFToken) {
			$response["status"] = 403;
			$response["error"] = "The anti-CSRF token provided does not match.";
			$response["errorCode"] = "anti_csrf_mismatch";
			return $response;
		}
	}

	$temp = $_SERVER["REQUEST_METHOD"];
	$_SERVER["REQUEST_METHOD"] = "POST";
	
	$temp2 = null;
	if (isset($_SERVER["CONTENT_TYPE"])) {
		$temp2 = $_SERVER["CONTENT_TYPE"];
		$_SERVER["CONTENT_TYPE"] = "application/x-www-form-urlencoded";
	}
	
	$temp3 = $_POST;
	$_POST = array();

	if ($token) {
		foreach ($token as $key => $value) {
			if ($key === "access_token" or
				$key === "expires_in" or
				$key === "token_type" or
				$key === "scope" or
				$key === "refresh_token")
			{
				$_POST[$key] = $value;
			}
		}
	}

	require_once __DIR__ . "/../oauth/server.php";
	$req = OAuth2\Request::createFromGlobals();
	$res = new OAuth2\Response();
	if ($server->verifyResourceRequest($req, $res)) {
		$_SERVER["REQUEST_METHOD"] = $temp;
		$_SERVER["CONTENT_TYPE"] = $temp2;
		$_POST = $temp3;

		return $response;
	}

	$_SERVER["REQUEST_METHOD"] = $temp;
	$_SERVER["CONTENT_TYPE"] = $temp2;
	$_POST = $temp3;
	
	$response["status"] = 500;
	$response["error"] = "The token is invalid.";
	$response["errorCode"] = "generic_token_failure";
	return $response;
}
?>
