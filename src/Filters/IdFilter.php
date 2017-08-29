<?php
namespace TwinePM\Filters;

use \TwinePM\Responses;
class IdFilter implements IFilter {
    public static function filter(
        $value,
        array $context = null): Responses\IResponse
    {
        $type = gettype($value);
        if (($type !== "string" and $type !== "integer") or
            ($type === "string" and !ctype_digit($value)) or
            ($type === "integer" and $value < 0))
        {
            $errorCode = "IdFilterIdInvalid";
            $error = new Responses\ErrorResponse($errorCode);
            return $error;   
        }

        $success = new Responses\Response();
        $success->filtered = (int)$value;
        return $success;
    }
}
?>