<?php
namespace TwinePM\Loggers;

use \TwinePM\Responses\ErrorResponse;
class LoggerRouter {
    public static function route(string $errorCode): void {
        $error = new ErrorResponse($errorCode);
        $logger = new GenericErrorLogger();
        $logger->log($error->toArray());
    }
} 