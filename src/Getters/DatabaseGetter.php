<?php
namespace TwinePM\Getters;

use \TwinePM\Miscellaneous\Miscellaneous;
use \TwinePM\Responses;
use \PDO;
class DatabaseGetter implements IGetter {
    public static function get(array $context = null): PDO {
        $dbArgs = DatabaseArgsGetter::get();
        $driver = "pgsql";
        $dbName = ltrim($dbArgs["path"], "/");
        $charset = "utf8";
        $dsn = Miscellaneous::makeDSN(
            $driver,
            $dbArgs["host"],
            $dbArgs["port"],
            $dbName,
            $charset);

        $username = $dbArgs["user"];
        $password = $dbArgs["pass"];

        $db = null;
        try {
            $db = new PDO($dsn, $username, $password);
        } catch (Exception $e) {
            $data = [ "exception" => $e, ];
            $errorCode = "DatabaseGetterDatabaseCreationFailed";
            $error = new Responses\ErrorResponse($errorCode);
            die(json_encode($error->getOutput()));
        }

        return $db;
    }
}