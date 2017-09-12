<?php
namespace TwinePM\Loggers;

use \TwinePM\Getters;
use \Monolog\Logger;
use \Monolog\Handler\HandlerInterface;
use \Monolog\Handler\StreamHandler;
use \Monolog\Processor\ProcessorInterface;
use \Monolog\Formatter\LineFormatter;
abstract class AbstractLogger implements ILogger {
    const TYPE = "not_provided";
    const LEVEL = Logger::ERROR;
    const ID_LENGTH = 6;
    const FILEPATH = __DIR__ . "/../../logs/log.txt";    

    protected $client;
    protected $handlers = [];
    protected $processors = [];
    protected $timezone = "UTC";

    public function getLoggerClient(): Logger {
        $channel = static::TYPE ? static::TYPE : self::TYPE;
        return new Logger(
            $channel,
            $this->getHandlers(),
            $this->getProcessors(),
            $this->getTimezone());
    }

    public function __construct() {
        if (!$this->handlers) {
            $output = "%channel%.%level_name%: %message%";
            $formatter = new LineFormatter($output);

            $filepath = static::FILEPATH;
            $streamHandler = new StreamHandler($filepath, Logger::DEBUG);
            $streamHandler->setFormatter($formatter);
            $this->pushHandler($streamHandler);
            
            $streamHandler = new StreamHandler("php://stdout", Logger::DEBUG);
            $streamHandler->setFormatter($formatter);
            $streamHandler->setFormatter($formatter);
            $this->pushHandler($streamHandler);
        }

        $this->client = $this->getLoggerClient();
    }

    final public static function getLogId(): string {
        if (!defined("LOG_ID")) {
            define("LOG_ID", bin2hex(random_bytes(static::ID_LENGTH)));
        }

        return LOG_ID;
    }

    public function log(array $source, array $context = null): void {
        if (!($this->client instanceof Logger)) {
            $this->client = $this->getLoggerClient();
        }

        $logArray = [];

        if (isset($context["logId"])) {
            $logArray["logId"] = $context["logId"];
        } else {
            $logArray["logId"] = static::getLogId();
        }

        $logArray = array_merge($logArray, $source);

        $this->client->log(static::LEVEL, json_encode($logArray));
    }

    public function getHandlers(): array {
        return $this->handlers;
    }

    public function pushHandler(HandlerInterface $handler): void {
        $this->handlers[] = $handler;
    }

    public function popHandler(): ?HandlerInterface {
        return array_pop($this->handler);
    }

    public function getProcessors(): array {
        return $this->processors;
    }

    public function pushProcessor(ProcessorInterface $processor): void {
        $this->processors[] = $processor;
    }

    public function popProcessor(): ProcessorInterface {
        return array_pop($this->processors);
    }

    public function getTimezone(): string {
        return $this->timezone;
    }
}