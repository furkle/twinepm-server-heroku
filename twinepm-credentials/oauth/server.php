<?php
require_once __DIR__ . "/../globals/getDatabaseArgs.php";
$dbArgs = getDatabaseArgs();

require_once __DIR__ . "/../globals/makeTwinepmDSN.php";
$dsn = makeTwinepmDSN();

$id = (int)$_GET["id"];

$username = $dbArgs["user"];
$password = $dbArgs["pass"];

require_once __DIR__ . '/oauth2-server-php/src/OAuth2/Autoloader.php';

OAuth2\Autoloader::register();

$storage = new OAuth2\Storage\Pdo(
	array('dsn' => $dsn,
		'username' => $username,
		'password' => $password)
);

$server = new OAuth2\Server($storage, array("access_lifetime" => 250000));

$server->addGrantType(new OAuth2\GrantType\ClientCredentials($storage));
?>
