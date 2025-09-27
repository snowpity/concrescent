<?php

namespace App;

use App\Lib\Log\LogLibrary;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class Kernel
{
    public readonly array $config;
    public readonly bool $isAppDebug;

    public function __construct(
        string $configFile = '',
    ) {
        $this->isAppDebug = getenv('APP_DEBUG');

        /**
         * @see config/concrescent.php
         */
        $configFile = $configFile ?: $this->projectDir . '/config/concrescent.php';

        if (!file_exists($configFile)) {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log('Cannot initialize Kernel. Config file not found: ' . $configFile);
            die('Cannot initialize Kernel. Config file not found');
        }

        {
            include $configFile;
            $this->config = $cm_config;
        }

        if ($this->config['timezone']) {
            date_default_timezone_set($this->config['timezone']);
        }

        $logDir = $this->config['logging']['log_dir'];
        if (str_starts_with($logDir, '/')) {
            $this->logDir = $logDir;
        } else {
            $this->logDir = $this->projectDir . '/var/log/' . $logDir;
        }
    }

    private(set) string $projectDir {
        get => $this->projectDir = dirname(__DIR__);
    }

    private(set) string $configDir {
        get => $this->configDir = $this->projectDir.'/config';
    }

    private(set) string $cacheDir {
        get => $this->cacheDir = $this->projectDir.'/var/cache';
    }

    private(set) string $logDir {
        get => $this->logDir ??= $this->projectDir.'/var/log';
    }


    private(set) LogLibrary $log {
        get => $this->log = new LogLibrary($this->logDir);
    }

    private(set) Environment $twig {
        get {
            if (isset($this->twig)) {
                return $this->twig;
            }

            $this->twig = new Environment(
                new FilesystemLoader($this->projectDir.'/templates'),
                [
                    'debug' => $this->isAppDebug,
                    'strict_variables' => $this->isAppDebug,
                    'cache' => $this->cacheDir.'/twig',
                ],
            );
            $this->twig->addFunction(new TwigFunction('theme_file_url', theme_file_url(...)));
            $this->twig->addFunction(new TwigFunction('resource_file_url', resource_file_url(...)));
            $this->twig->addFunction(new TwigFunction('get_site_url', get_site_url(...)));
            $this->twig->addFilter(new TwigFilter('price_string', price_string(...)));
            $this->twig->addFilter(new TwigFilter('cm_status_label', cm_status_label(...)));

            return $this->twig;
        }
    }
}
