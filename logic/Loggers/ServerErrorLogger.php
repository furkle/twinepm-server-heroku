<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
class ServerErrorLogger extends AbstractLogger {
    const LEVEL = Logger::ERROR;
    const TYPE = "server_error";
}