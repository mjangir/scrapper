<?php

namespace Kacademy\Utils;

use Monolog\Logger as MonoLogger;
use Monolog\Handler\StreamHandler;

class Logger {

    protected $logDir = '../logs/request.log';
    protected $logger;

    public function __construct() {
        $logger = new MonoLogger('requestLogger');
        $logger->pushHandler(new StreamHandler($this->logDir, MonoLogger::WARNING));
        $logger->pushHandler(new StreamHandler($this->logDir, MonoLogger::INFO));
        $logger->pushHandler(new StreamHandler($this->logDir, MonoLogger::ERROR));
        $this->logger = $logger;
    }

    public function getLogger() {
        return $this->logger;
    }

}
