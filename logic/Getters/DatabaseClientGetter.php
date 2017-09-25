<?php
namespace TwinePM\Getters;

use TwinePM\Exceptions\DatabaseClientConstructionFailedException;
use PDO;
class DatabaseClientGetter {
    public function get(
        string $dsn,
        string $username,
        string $password): PDO
    {
        try {
            return new PDO($dsn, $username, $password);
        } catch (Exception $e) {
            $errorCode = "PdoConstructionFailed";
            throw new DatabaseClientConstructionFailedException($errorCode);
        }
    }
}