<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
class IdValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (gettype($value) !== "integer" or $value < 0) {
            $errorCode = "IdValidatorIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $success = new Responses\Response();
        return $success;
    }
}