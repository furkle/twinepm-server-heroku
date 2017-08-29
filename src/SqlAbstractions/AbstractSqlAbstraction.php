<?php
namespace TwinePM\SqlAbstractions;

use \TwinePM\Responses;
use \TwinePM\Errors;
use \PDO;
use \ReflectionClass;
abstract class AbstractSqlAbstraction implements ISqlAbstraction {
    const FORBIDDEN_ARRAY_KEYS = [
        "errorCode",
        "errorData",
        "database",
    ];

    public function toArray(): array {
        $reflect = new ReflectionClass($this);
        $props = $reflect->getProperties();
        $array = [];
        $yesStrict = true;
        foreach ($props as $value) {
            $key = $value->name;
            if (!in_array($key, static::FORBIDDEN_ARRAY_KEYS, $yesStrict)) {
                $array[$key] = $this->{$key};
            }
        }

        return $array;
    }

    public function getDatabase(): PDO {
        return $this->database;
    }

    public function isError(): bool {
        return $this->errorCode or $this->errorData;
    }

    public function getError(): ?Responses\ErrorResponse {
        if (!$this->isError()) {
            return null;
        }

        $errorCode = $this->errorCode ? $this->errorCode : "NoCodeProvided";
        $errorData = $this->errorData;
        $error = new Responses\ErrorResponse($errorCode, $errorData);
        return $error;
    }
}