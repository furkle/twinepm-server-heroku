<?php
$dsn = 'mysql:dbname=twinepm;host=localhost';
$username = 'tpm_oauth_user';
$password = trim(file_get_contents(__DIR__ . "/tpm_oauth_user.txt"));

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
