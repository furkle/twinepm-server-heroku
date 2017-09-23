<?php
namespace TwinePM\Validators;

use \TwinePM\Responses;
use \TwinePM\SqlAbstractions\Accounts\Account;
class HttpResponseCodeValidator implements IValidator {
    public static function validate(
        $value,
        array $context = null): Responses\IResponse
    {
        if (gettype($value) !== "integer" or
            $value < 100 or
            $value > 599)
        {
            $errorCode = "HttpResponseCodeValidatorValueInvalid";
            return new Responses\ErrorResponse($errorCode);
        }

        return new Responses\SuccessResponse();
    }
}