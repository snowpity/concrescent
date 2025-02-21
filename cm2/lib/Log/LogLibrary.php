<?php

namespace App\Log;

use Monolog\Formatter\JsonFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Monolog\Processor\WebProcessor;

readonly class LogLibrary
{
    public Logger $audit;
    public Logger $cloudflare;
    public Logger $system;

    private function __construct()
    {
        global $cm_config;
        $logDir = $cm_config['logging']['log_dir'];

        $debugStdoutHandler = new StreamHandler(
            'php://stdout',
            Level::Debug
        );

        {
            $this->audit = new Logger('audit');
            $this->audit->pushHandler($debugStdoutHandler);
            $this->audit->pushProcessor(new WebProcessor());
            $this->audit->pushProcessor(new PsrLogMessageProcessor());

            if ($logDir) {
                $logfileHandler = new StreamHandler(
                    $logDir . '/audit.log',
                    Level::Info
                );
                $logfileHandler->setFormatter(new JsonFormatter());
                $this->audit->pushHandler($logfileHandler);
            }
        }

        {
            $this->cloudflare = new Logger('cloudflare');
            $this->cloudflare->pushHandler($debugStdoutHandler);
            $this->cloudflare->pushProcessor(new WebProcessor());
            $this->cloudflare->pushProcessor(new PsrLogMessageProcessor());

            if ($logDir) {
                $this->cloudflare->pushHandler($logfileHandler);
                $logfileHandler = new StreamHandler(
                    $logDir . '/cloudflare.log',
                    Level::Info
                );
                $logfileHandler->setFormatter(new JsonFormatter());
            }
        }

        {
            $this->system = new Logger('system');
            $this->system->pushHandler($debugStdoutHandler);
            $this->system->pushProcessor(new WebProcessor());
            $this->system->pushProcessor(new PsrLogMessageProcessor());

            if ($logDir) {
                $this->system->pushHandler($logfileHandler);
                $logfileHandler = new StreamHandler(
                    $logDir . '/system.log',
                    Level::Info
                );
                $logfileHandler->setFormatter(new JsonFormatter());
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
