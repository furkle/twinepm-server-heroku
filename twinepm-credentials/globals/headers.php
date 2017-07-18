<?php
$src = "*";
if (isset($_SERVER["HTTP_ORIGIN"])) {
    $src = $_SERVER["HTTP_ORIGIN"];
} else if (isset($_SERVER["HTTP_REFERER"])) {
    $src = $_SERVER["HTTP_REFERER"];
}

header("Access-Control-Allow-Origin: $src");
header("Access-Control-Allow-Credentials: true");
?>
