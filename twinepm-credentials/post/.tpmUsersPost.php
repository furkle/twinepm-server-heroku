<?php
	$servername = 'localhost';
	$username = 'tpm_users_post_user';
	$password = trim(file_get_contents(
		'/var/www/twinepm-credentials/users/tpm_users_post_user.txt'));

	if (!$password) {
		http_response_code(500);
		die('Internal server error.');
	}

	$database = 'twinepm';

	function tpmUsersCreate($collection) {
		if (!$collection) {
			http_response_code(503);
			die('Unknown error in transaction.');
		}		

		global $servername, $username, $password, $database;
		$conn = new \mysqli($servername, $username, $password, $database);
		
		if ($conn->connect_error) {
            http_response_code(503);
            die('Connection failed.');
        }
	
		$stmt = 'INSERT INTO users (name, description, homepage, ' .
			'createDate, lastModifyDate, password) ' .
			'VALUES (?, ?, ?, ' . time() . ', ' . time() . ', ?)';

		if (!($stmt = $conn->prepare($stmt))) {
			http_response_code(503);
			die('Unknown error in transaction.');
		}
		
		$replacements = array(
			isset($collection['name']) ?
				$collection['name'] : null,
			isset($collection['description']) ?
				$collection['description'] : null,
			isset($collection['homepage']) ? $collection['homepage'] : null,
			deriveKey(isset($collection['password']) ?
				$collection['password'] : null)
		);

		if (in_array(null, $replacements, true)) {
			http_response_code(400);
			die('One or more arguments was not included.');
		}

		$stmt->bind_param(
			str_repeat('s', count($replacements)), ...$replacements);

		if (!$stmt->execute()) {
			if ($stmt->error_list[0] && $stmt->errno === 2031) {
				http_response_code(400);
				die('The data for one or more columns has not been supplied.');
			} else {
				http_response_code(503);
				die('Unknown error in transaction.');
			}
		}

		return $conn->insert_id;
	}

	function tpmUsersEdit($collection) {
		if (!$collection) {
			http_response_code(503);
			die('Unknown error in transaction.');
		}

		$type = null;
		$value = null;
		if (isset($collection['id']) && $collection['id']) {
			$type = 'id';
			$value = $collection['id'];
		} else if (isset($collection['name']) && $collection['name']) {
			$type = 'name';
			$value = $collection['name'];
		} else {
			http_response_code(400);
			die('Neither an id nor name argument were provided.');
		}

		global $servername, $username, $password, $database;
		$conn = new \mysqli($servername, $username, $password, $database);
		
		$stmt = 'UPDATE users SET';

		$replacements = array();
	
		/*if (isset($collection['name'])) {
			$stmt .= ' name=?, ';
			array_push($replacements, $collection['name']);
		}*/

		if (isset($collection['description'])) {
			$stmt .= ' description=?, ';
			array_push($replacements, $collection['description']);
		}

		if (isset($collection['homepage'])) {
			$stmt .= ' homepage=?, ';
			array_push($replacements, $collection['homepage']);
		}

		if (substr($stmt, -3) === 'SET') {
			http_response_code(400);
			die('No arguments were provided.');
		} else if (substr($stmt, -2) === ', ') {
			$stmt = substr($stmt, 0, strlen($stmt) - 2);
		}

		$stmt .= ', lastModifyDate=' . time()  . ' WHERE id=?';
		array_push($replacements, $collections['id']);

		if (!($stmt = $conn->prepare($stmt))) {
			http_response_code(503);
			die('Unknown error in transaction.');
		}

		$stmt->bind_param(
			str_repeat('s', count($replacements)), ...$replacements);
		
		if (!$stmt->execute()) {
            if ($stmt->error_list[0] && $stmt->errno === 2031) {
				http_response_code(400);
                die('The data for one or more columns has not been supplied.');
            } else {
				http_response_code(503);
                die('Unknown error in transaction.');
            }
        }

		return $collection['id'];
	}
?>
