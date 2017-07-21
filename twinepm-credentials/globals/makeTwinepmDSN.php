<?php
require_once __DIR__ . "/../globals/getDatabaseArgs.php";
$dbArgs = getDatabaseArgs();

require_once __DIR__ . "/../globals/makeDSN.php";
$prefix = "pgsql";
$charset = "utf8";
$dsn = makeDSN(
    $prefix,
    $dbArgs["host"],
    $dbArgs["port"],
    ltrim($dbArgs["path"], "/"),
    $charset);

return $dsn;
?>
