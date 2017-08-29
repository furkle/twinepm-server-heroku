<?php
namespace TwinePM\Loggers;

use \Monolog\Logger;
interface ILogger {
    public static function getLogId(): string;
    public function log(array $source): void;
}