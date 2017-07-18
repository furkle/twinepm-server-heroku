<?php
$dsn = 'mysql:host=localhost;dbname=twinepm;charset=utf8;';

$username = 'tpm_packages_get_user';
$password = trim(file_get_contents('/var/www/twinepm-credentials/get/' .
    'tpm_packages_get_user.txt'));

$db = new PDO($dsn, $username, $password);

$obj = array();

$where = '';
$props = array();
// allow * to collect all
if (isset($_GET['term']) and $_GET['term'] and trim($_GET['term']) !== '*') {
	$where = 'WHERE (LOWER(name) LIKE ? OR ' .
		'LOWER(description) LIKE ? OR ' .
		'LOWER(keywords) LIKE ? OR ' .
		'LOWER(type) LIKE ?) ';
	$term = strtolower($_GET['term']) . '%';
	for ($i = 0; $i < 4; $i++) {
		array_push($props, $term);
	}
}

$str =
	'SELECT id, name, description, authorid, type ' .
	'FROM packages ' .
	$where;

$stmt = $db->prepare($str);
$stmt->execute($props);

if ((int)$stmt->errorCode()) {
	http_response_code(503);
	die('Could not fetch content. Please contact webmaster.');
}

$fetch = $stmt->fetchAll(PDO::FETCH_ASSOC);
require_once '/var/www/twinepm-credentials/get/idToName.php';
foreach ($fetch as $key => $value) {
	$fetch[$key]['authorname'] = idToName($fetch[$key]['authorid']);
}

echo json_encode($fetch);
?>
