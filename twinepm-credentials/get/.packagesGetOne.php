<?php
if (!isset($_GET['id']) or !$_GET['id']) {
	http_response_code(400);
	die('The id argument was not provided.');
}

$dsn = 'mysql:host=localhost;dbname=twinepm;charset=utf8;';
$username = 'tpm_packages_get_user';
$password = trim(file_get_contents('/var/www/twinepm-credentials/get/' .
	'tpm_packages_get_user.txt'));

$db = new PDO($dsn, $username, $password);

$stmt = $db->prepare('SELECT id, keywords, description, js, css, name, ' .
	'authorid, homepage, datecreated, datemodified, version, type ' .
	'FROM packages WHERE id=?');
$stmt->execute(array($_GET['id']));
if ((int)$stmt->errorCode()) {
	http_response_code(503);
	die('Could not fetch entry.');
}

$fetch = $stmt->fetch(PDO::FETCH_ASSOC);

require_once '/var/www/twinepm-credentials/get/idToName.php';
$name = idToName($fetch['authorid']);
$fetch['authorname'] = $name;

echo json_encode($fetch);
?>
