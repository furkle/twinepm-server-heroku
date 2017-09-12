<?php
namespace TwinePM\Responses;

use \TwinePM\Errors\ErrorInfo;
class ErrorResponse extends Response {
    public function __construct(string $errorCode, array $errorData = null) {
        $errorArray = ErrorInfo::get($errorCode);

        $this->errorCode = $errorCode;
        $this->status = $errorArray["status"];
        $this->message = $errorArray["message"];
        $this->errorData = $errorData;
    }

    public function isError(): bool {
        return true;
    }
}