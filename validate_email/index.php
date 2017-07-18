<?php
	if ($_SERVER["REQUEST_METHOD"] !== "GET") {
		http_response_code(400);
		die("The only request method accepted is GET or OPTIONS.");
	} else if (isset($_GET["token"]) and
		$_GET["token"] and
		isset($_GET["userid"]) and
		$_GET["userid"])
	{
		require_once '/var/www/twinepm-credentials/login/validateEmail.php';
	} else {
		http_response_code(400);
		die('No token and/or ID argument was provided. Cannot validate.');
	}
?>
