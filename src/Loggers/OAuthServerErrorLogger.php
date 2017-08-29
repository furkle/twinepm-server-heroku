<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
class OAuthServerErrorLogger extends AbstractLogger {
    const LEVEL = Logger::ALERT;
    const TYPE = "oauth_server_error";
}