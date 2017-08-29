<?php
namespace TwinePM\Persisters;

use \TwinePM\Responses;
interface IPersister {
    public static function persist(
        $value,
        array $context = null): Responses\IResponse;

    public static function unpersist(
        $value,
        array $context = null): Responses\IResponse;
}