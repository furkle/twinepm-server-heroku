<?php
	$response = array("status" => 200);

	require_once __DIR__ . "/../globals/token_to_userdata.php";
	$userdataObj = tokenToUserdata("getPackages", false);
	if (isset($userdataObj["error"])) {
		http_response_code($userdataObj["status"]);
		$response["status"] = $userdataObj["status"];
		$response["error"] = $userdataObj["error"];	
		if (isset($userdataObj["errorCode"])) {
			$response["errorCode"] = $userdataObj["errorCode"];
		}
	} else {
		$response["userdata"] = $userdataObj["userdata"];
	}

	die(json_encode($response));
?>
