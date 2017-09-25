<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
class SqlErrorLogger extends AbstractLogger {
    const LEVEL = Logger::EMERGENCY;
    const TYPE = "sql_error";
}