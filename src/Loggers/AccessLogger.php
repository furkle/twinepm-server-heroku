<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
class AccessLogger extends AbstractLogger {
    const LEVEL = Logger::DEBUG;
    const TYPE = "access";
}