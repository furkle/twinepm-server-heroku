<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
class PermissionsErrorLogger extends AbstractLogger {
    const LEVEL = Logger::NOTICE;
    const TYPE = "permissions_error";
}