<?php
	$servername = 'localhost';
	$username = 'tpm_users_get_user';
	$password = trim(file_get_contents(
		'/var/www/twinepm-credentials/users/tpm_users_get_user.txt'));

	if (!$password) {
		http_response_code(500);
		die('Internal server error.');
	}

	$database = 'twinepm';

	function tpmUsersGetWithId($id) {
		global $servername, $username, $password, $database;
		$conn = new \mysqli($servername, $username, $password, $database);

		if ($conn->connect_error) {
			http_response_code(503);
			die('Connection failed.');
		}

		$stmt = 'SELECT COUNT(id) FROM users WHERE id=?';
        $stmt = $conn->prepare($stmt);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->bind_result($value);
        $stmt->fetch();

        if (!$value) {
            http_response_code(404);
            die('Entry does not exist.');
        }	

		$allfields = 'name, description, homepage, createDate, ' .
			'lastModifyDate, id';
		$stmt = 'SELECT ' .	$allfields .
			' FROM users ' .
			' WHERE id=?';

		if (!($stmt = $conn->prepare($stmt))) {
			http_response_code(400);
			die('id argument not provided or valid.');
		}

		if (!$stmt->bind_param("i", $id)) {
			http_response_code(400);
		    die('id argument not valid.');
		}

		if (!$stmt->execute()) {
			http_response_code(503);
			die('Unknown transaction error.');
		}

		$value = $stmt->get_result();

		return json_encode($value->fetch_assoc());
	}

	function tpmUsersGetWithoutId($collection) {
		global $servername, $username, $password, $database;
		$conn = new \mysqli($servername, $username, $password, $database);
		
		if ($conn->connect_error) {
            http_response_code(503);
            die("Connection failed.");
        }

		$allfields = 'name, description, homepage, createDate, ' .
			'lastModifyDate, id';
			
		$stmt = 'SELECT ' .
			$allfields .
			' FROM ' .
			' users WHERE';

		$replacements = array();

		if (isset($collection['name'])) {
			$stmt .= ' name=? AND';
			array_push($replacements, $name);
		}

		if (isset($collection['description'])) {
			$stmt .= ' description LIKE ? AND';
			array_push($replacements, '%' . $collection["description"] . '%');
		}
			
		if (isset($collection['homepage'])) {
			$stmt .= ' homepage=? AND';
			array_push($replacements, $collection['homepage']);
		}

		if (isset($collection['createDate'])) {
			$stmt .= ' createDate=? AND';
			array_push($replacements, $collection['createDate']);
		}

		if (isset($collection['lastModifyDate'])) {
			$stmt .= ' lastModifyDate=? AND';
			array_push($replacements, $collection['lastModifyDate']);
		}

		if (count($replacements) === 0) {
			http_response_code(400);
			die('No required arguments provided.');
		}

		if (substr($stmt, -3) === 'AND') {
			$stmt = substr($stmt, 0, strlen($stmt) - 3);
		}

		if (substr($stmt, -6) === 'WHERE ') {
			$stmt = substr($stmt, 0, strlen($stmt) - 6);
		}

		if (!($stmt = $conn->prepare($stmt))) {
			http_response_code(503);
			die('Unknown error in transaction.');	
		}

		$stmt->bind_param(
			str_repeat('s', count($replacements)), ...$replacements);

		if (!$stmt->execute()) {
			http_repsonse_code(503);
			die('Unknown error in transaction.');
		}

		$result = $stmt->get_result();
		while ($row = $result->fetch_assoc()) {
			$rows[] = $row;
		}

		return json_encode($rows);	
	}
?>
