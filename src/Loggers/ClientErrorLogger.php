<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
class ClientErrorLogger extends AbstractLogger {
    const LEVEL = Logger::INFO;
    const TYPE = "client_error";
}