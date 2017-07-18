<?php
$dsn = 'mysql:host=localhost;dbname=twinepm;charset=utf8;';
$username = 'tpm_idtoname_user';
$password = trim(file_get_contents('/var/www/twinepm-credentials/get/' .
    'tpm_idtoname_user.txt'));

$db = new PDO($dsn, $username, $password);

function idToName($id) {
	global $db;
	$stmt = $db->prepare('SELECT name FROM users WHERE id=?');
	$stmt->execute(array($id));

	if ((int)$stmt->errorCode()) {
		return null;
	}

	$fetch = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$fetch or !isset($fetch['name']) or !$fetch['name']) {
		return null;
	}

	return $fetch['name'];
}
?>
