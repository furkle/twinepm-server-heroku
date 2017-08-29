<?php
namespace TwinePM\Transformers;

use \TwinePM\Responses;
use \TwinePM\Errors;
class PasswordToHashTransformer implements ITransformer {
    public static function transform(
        $value,
        array $context = null): Responses\IResponse
    {
        if (!$value or gettype($value) !== "string") {
            $errorCode = Errors\ErrorInfo::PASSWORD_TO_HASH_PASSWORD_INVALID;
            $error = new Responses\ErrorResponse($errorCode);
            return $error;
        }

        $hash = password_hash($value, PASSWORD_DEFAULT);

        $success = new Responses\Response();
        $success->transformed = $hash;
        return $success;
    }
}