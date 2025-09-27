<?php

namespace App\Lib\Log;

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

    public function __construct(
        public readonly string $logDir = '',
    )
    {
        $debugStdoutHandler = new StreamHandler(
            'php://stdout',
            Level::Debug
        );

        {
            $this->audit = new Logger('audit');
            $this->audit->pushHandler($debugStdoutHandler);
            $this->audit->pushProcessor(new WebProcessor());
            $this->audit->pushProcessor(new PsrLogMessageProcessor());

            if ($this->logDir) {
                $logfileHandler = new StreamHandler(
                    $this->logDir . '/audit.log',
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

            if ($this->logDir) {
                $this->cloudflare->pushHandler($logfileHandler);
                $logfileHandler = new StreamHandler(
                    $this->logDir . '/cloudflare.log',
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

            if ($this->logDir) {
                $this->system->pushHandler($logfileHandler);
                $logfileHandler = new StreamHandler(
                    $this->logDir . '/system.log',
                    Level::Info
                );
                $logfileHandler->setFormatter(new JsonFormatter());
            }
        }
    }
}
