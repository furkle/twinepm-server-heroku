<?php
function tokenToUserdata($getPackages, $omitAntiCSRFToken) {
	$response = array(
		"status" => 200,
		"userdata" => null);

	require_once __DIR__ . "/../globals/verify_tokens.php";
	$verification = verifyTokens($omitAntiCSRFToken);
	if (!$verification) {
		$response["status"] = 500;
		$response["error"] = "An unknown error was encountered while " .
			"verifying authentication tokens. Please contact webmaster.";
		return $response;
	} else if (isset($verification["error"])) {
		$response["status"] = isset($verification["status"]) ?
			$verification["status"] :
			500;
		$response["error"] = $verification["error"];
		if (isset($verification["errorCode"])) {
			$response["errorCode"] = $verification["errorCode"];
		}

		return $response;
	} else if ($verification["status"] !== 200) {
		$response["status"] = $verification["status"];
		$response["error"] = "An unknown error was encountered while " .
			"verifying authentication tokens. The status was not 200, " .
			"but no error was received.";
		return $response;
	}

	$dsn = "mysql:host=localhost;dbname=twinepm;";
	$username = "tpm_tokensanduserids_get_user";
	$password = trim(file_get_contents(__DIR__ . 
		"/../get/tpm_tokensanduserids_get_user.txt"));
	
	$db = new PDO($dsn, $username, $password);
	$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

	$stmt = $db->prepare("SELECT id FROM tokens_and_userids WHERE token=?");

	try {
		$stmt->execute(array($_COOKIE["twinepm_access_token"]));
	} catch (Exception $e) {
		$response["status"] = 500;
		$response["error"] = "Unknown error fetching userdata from database " .
			"in tokenToUserdata.";
		return $response;
	}


	$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$fetch or !isset($fetch["id"])) {
		$response["status"] = 500;
		$response["error"] = "Token does not exist in token->id lookup table " .
			"in tokenToUserdata.";
		return $response;
	}

	$id = $fetch["id"];
	
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
		$response["status"] = 500;
		$response["error"] = "Unknown error while selecting entries from the " .
			"userdata table. Please contact webmaster.";
		return $response;
	}

	$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$fetch) {
		$response["status"] = 404;
		$response["error"] = "Entry does not exist in user database in " .
			"tokenToUserdata.";
		return $response;
	}

	$response["userdata"] =  array();
	// not sure why i'm needing to cast to int here
	$response["userdata"]["nameVisible"] = (bool)(int)$fetch["name_visible"];
	$response["userdata"]["dateCreated"] = (int)$fetch["date_created"];
	$response["userdata"]["dateCreatedVisible"] =
		(bool)(int)$fetch["date_created_visible"];
	$response["userdata"]["description"] = $fetch["description"];
	$response["userdata"]["email"] = $fetch["email"];
	$response["userdata"]["emailVisible"] = (bool)(int)$fetch["email_visible"];
	$response["userdata"]["id"] = (int)$fetch["id"];
	$response["userdata"]["name"] = $fetch["name"];
	$response["userdata"]["dateStyle"] = $fetch["date_style"];
	$response["userdata"]["timeStyle"] = $fetch["time_style"];

	if (isset($getPackages) and
		($getPackages === true or $getPackages === "getPackages"))
	{
		$username = "tpm_packages_get_user";
		$password = trim(file_get_contents(__DIR__ .
			"/../get/tpm_packages_get_user.txt"));

		$db = new PDO($dsn, $username, $password);
		$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		$stmt = $db->prepare("SELECT id, name, version, js, css, keywords, " .
			"date_created, date_modified, description, homepage, version, " .
			"type, tag, published FROM packages WHERE author_id=?");

		try {
			$stmt->execute(array($id));
		} catch (Exception $e) {
			$response["status"] = 500;
			$response["error"] = "Unknown error fetching owned packages in " .
				"tokenToUserdata.";
			return $response;
		}

		$fetchAll = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$packages = array();

		for ($i = 0; $i < count($fetchAll); $i++) {
			$packages[] = array(
				"id" => (int)$fetchAll[$i]["id"],
				"name" => $fetchAll[$i]["name"],
				"js" => $fetchAll[$i]["js"],
				"css" => $fetchAll[$i]["css"],
				"keywords" => $fetchAll[$i]["keywords"],
				"dateCreated" => (int)$fetchAll[$i]["date_created"],
				"dateModified" => (int)$fetchAll[$i]["date_modified"],
				"description" => $fetchAll[$i]["description"],
				"homepage" => $fetchAll[$i]["homepage"],
				"version" => $fetchAll[$i]["version"],
				"type" => $fetchAll[$i]["type"],
				"tag" => $fetchAll[$i]["tag"],
				"published" => (bool)(int)$fetchAll[$i]["published"]
			);
		}

		$response["userdata"]["packages"] = $packages;
	}

	return $response;
}
?>
