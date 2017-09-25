<?php
namespace TwinePM\Filters;

use \TwinePM\Responses;
interface IFilter {
    public static function filter(
        $value,
        array $context = null): Responses\IResponse;
}