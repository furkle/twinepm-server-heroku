<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
class GenericErrorLogger extends AbstractLogger {
    const LEVEL = Logger::ERROR;
    const TYPE = "error";
}