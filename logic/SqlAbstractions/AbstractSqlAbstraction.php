<?php
namespace TwinePM\SqlAbstractions;

use \TwinePM\Responses;
use \TwinePM\Errors;
use \PDO;
use \ReflectionClass;
abstract class AbstractSqlAbstraction implements ISqlAbstraction {
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
}