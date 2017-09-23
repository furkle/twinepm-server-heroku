<?php
namespace TwinePM\Getters;

use \TwinePM\Miscellaneous\Miscellaneous;
class DsnGetter implements IGetter {
    public static function get(array $dbArgs): string {
        $driver = "pgsql";
        /* Ignored for PGSQL. */
        $charset = "utf8";
        $dsn = Miscellaneous::makeDSN(
            $driver,
            $dbArgs["host"],
            $dbArgs["port"],
            ltrim($dbArgs["path"], "/"),
            $charset);

        return $dsn;
    }
}