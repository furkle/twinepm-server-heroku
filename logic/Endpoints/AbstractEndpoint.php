<?php
namespace TwinePM\Endpoints;

use \TwinePM\Responses;
use \TwinePM\Errors\ErrorInfo;
abstract class AbstractEndpoint implements IEndpoint {
    const LOOKUP = [];

    public static function convertServerErrorToClientError(
        Responses\IResponse $serverError): Responses\ErrorResponse
    {
        $fallback = ErrorInfo::get("NoCodeProvided")["name"];
        $serverErrorCode = isset($serverError->errorCode) ?
            $serverError->errorCode : $fallback;
        $clientErrorCode = isset(static::LOOKUP[$serverErrorCode]) ?
            static::LOOKUP[$serverErrorCode] : $serverErrorCode;

        $errorData = isset($serverError->errorData) ?
            $serverError->errorData : null;

        $clientError = new Responses\ErrorResponse(
            $clientErrorCode,
            $errorData);

        return $clientError;
    }

    public static function getOptionsObject(): array {
        return [
            "warning" => "The OPTIONS object is not configured for this " .
                "endpoint yet. Please bother the maintainer about it.",
        ];
    }
}