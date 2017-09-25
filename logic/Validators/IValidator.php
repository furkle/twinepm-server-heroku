<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
interface IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse;
}