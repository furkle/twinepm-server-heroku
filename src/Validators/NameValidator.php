<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
class NameValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if ($value !== null) {
            if (!$value or gettype($value) !== "string") {
                $errorCode = "NameValidatorNameInvalid";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            } else if (ctype_digit($value)) {
                $errorCode = "NameValidatorNameOnlyNumbers";
                $error = new Responses\ErrorResponse($errorCode);
                return $error;
            }
        }

        $success = new Responses\Response();
        return $success;
    }
}