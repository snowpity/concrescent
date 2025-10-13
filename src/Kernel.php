<?php

namespace App;

use App\Config\Configuration;
use App\Config\ConfigurationMapper;
use App\Lib\Log\LogLibrary;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\TwigFilter;
use Twig\TwigFunction;

final class Kernel
{
    /**
     * @see config/concrescent.php
     */
    public readonly Configuration $config;
    public readonly bool $isAppDebug;

    public function __construct(
        private readonly ConfigurationMapper $configMapper,
    ) {
        $this->isAppDebug = getenv('APP_DEBUG');

        /**
         * @see config/concrescent.php
         */
        $configFile = $this->projectDir . '/config/concrescent.php';

        if (!file_exists($configFile)) {
            /** @noinspection ForgottenDebugOutputInspection */
            error_log('Cannot initialize Kernel. Config file not found: ' . $configFile);
            die('Cannot initialize Kernel. Config file not found');
        }

        {
            include $configFile;
            $this->config = $this->configMapper->mapToConfiguration($cm_config);
        }

        if ($this->config->system->timezone) {
            date_default_timezone_set($this->config->system->timezone);
        }

        $logDir = $this->config->system->logDir;
        if (str_starts_with($logDir, '/')) {
            $this->logDir = $logDir;
        } else {
            $this->logDir = $this->projectDir . '/var/log/' . $logDir;
        }
    }

    private(set) string $projectDir { get => $this->projectDir = dirname(__DIR__); }
    private(set) string $configDir { get => $this->configDir = $this->projectDir.'/config'; }
    private(set) string $cacheDir { get => $this->cacheDir = $this->projectDir.'/var/cache'; }
    private(set) string $logDir { get => $this->logDir ??= $this->projectDir.'/var/log'; }
    private(set) string $publicDir { get => $this->publicDir ??= $this->projectDir.'/cm2'; }
    private(set) string $themeDir { get => $this->themeDir ??= $this->publicDir.'/'.$this->config->system->themeLocation; }
    private(set) string $resDir { get => $this->resDir ??= $this->publicDir.'/lib/res'; }
    private(set) string $publicPath { get => $this->publicPath ??= ''; }
    private(set) string $themePath { get => $this->themePath ??= $this->publicPath.'/'.$this->config->system->themeLocation; }
    private(set) string $resPath { get => $this->resPath ??= $this->publicPath.'/lib/res'; }


    protected(set) LogLibrary $log {
        get => $this->log = new LogLibrary($this->logDir);
    }

    protected(set) Environment $twig {
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
            $this->twig->addFunction(new TwigFunction('theme_file_path', theme_file_path(...)));
            $this->twig->addFunction(new TwigFunction('resource_file_url', resource_file_url(...)));
            $this->twig->addFunction(new TwigFunction('resource_file_path', resource_file_path(...)));
            $this->twig->addFunction(new TwigFunction('get_site_url', get_site_url(...)));
            $this->twig->addFunction(new TwigFunction('get_site_path', get_site_path(...)));
            $this->twig->addFilter(new TwigFilter('price_string', price_string(...)));
            $this->twig->addFilter(new TwigFilter('cm_status_label', cm_status_label(...)));

            return $this->twig;
        }
    }
}
