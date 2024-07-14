<?php

namespace {
}

namespace App\Log {

    use Monolog\Logger;

    readonly class LogLibrary
    {
        public Logger $audit;
        public Logger $cloudflare;
        public Logger $system;

        private function __construct()
        {
            global $cm_config;
            $logDir = $cm_config['logging']['log_dir'];

            $debugStdoutHandler = new \Monolog\Handler\StreamHandler(
                'php://stdout',
                \Monolog\Level::Debug
            );

            {
                $this->audit = new \Monolog\Logger('audit');
                $this->audit->pushHandler($debugStdoutHandler);
                $this->audit->pushProcessor(new \Monolog\Processor\WebProcessor());
                $this->audit->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());

                if ($logDir) {
                    $logfileHandler = new \Monolog\Handler\StreamHandler(
                        $logDir . '/audit.log',
                        \Monolog\Level::Info
                    );
                    $logfileHandler->setFormatter(new \Monolog\Formatter\JsonFormatter());
                    $this->audit->pushHandler($logfileHandler);
                }
            }

            {
                $this->cloudflare = new \Monolog\Logger('cloudflare');
                $this->cloudflare->pushHandler($debugStdoutHandler);
                $this->cloudflare->pushProcessor(new \Monolog\Processor\WebProcessor());
                $this->cloudflare->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());

                if ($logDir) {
                    $this->cloudflare->pushHandler($logfileHandler);
                    $logfileHandler = new \Monolog\Handler\StreamHandler(
                        $logDir . '/cloudflare.log',
                        \Monolog\Level::Info
                    );
                    $logfileHandler->setFormatter(new \Monolog\Formatter\JsonFormatter());
                }
            }

            {
                $this->system = new \Monolog\Logger('system');
                $this->system->pushHandler($debugStdoutHandler);
                $this->system->pushProcessor(new \Monolog\Processor\WebProcessor());
                $this->system->pushProcessor(new \Monolog\Processor\PsrLogMessageProcessor());

                if ($logDir) {
                    $this->system->pushHandler($logfileHandler);
                    $logfileHandler = new \Monolog\Handler\StreamHandler(
                        $logDir . '/system.log',
                        \Monolog\Level::Info
                    );
                    $logfileHandler->setFormatter(new \Monolog\Formatter\JsonFormatter());
                }
            }
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
