<?php

namespace {
}

namespace App\Log {

    use Monolog\Logger;

    readonly class LogLibrary
    {
        public Logger $audit;

        private function __construct() {
            global $cm_config;
            $logDir = $cm_config['logging']['log_dir'];

            $debugStdoutHandler = new \Monolog\Handler\StreamHandler(
                'php://stdout',
                \Monolog\Level::Debug
            );
            $auditLogfileHandler = new \Monolog\Handler\StreamHandler(
                $logDir.'/audit.log',
                \Monolog\Level::Info
            );
            $auditLogfileHandler->setFormatter(new \Monolog\Formatter\JsonFormatter());

            $this->audit = new \Monolog\Logger('audit');
            $this->audit->pushHandler($auditLogfileHandler);
            $this->audit->pushHandler($debugStdoutHandler);
            $this->audit->pushProcessor(new \Monolog\Processor\WebProcessor());
        }

        public static function createSingleInstance(): LogLibrary {
            static $instance = null;
            if ($instance === null) {
                $instance = new LogLibrary();
            }
            return $instance;
        }
    }
}
