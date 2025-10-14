<?php

use App\Config\ConfigurationMapper;
use App\Kernel;
use Symfony\Component\HttpFoundation\Request;

include_once __DIR__ .'/../../../config/concrescent.php';

if (!isset($cm_config)) {
    die('Config file not found.');
}
require_once __DIR__.'/../../../vendor/autoload.php';

require_once __DIR__ .'/util.php';

$kernel = new Kernel(
    new ConfigurationMapper()
);

$log = $kernel->container->log;
$twig = $kernel->container->twig;

$request = new Request(
    $_GET,
    $_POST,
    $_SERVER,
    $_COOKIE,
    $_FILES,
    $_SERVER,
);

function resource_file_url($file): string
{
	return get_site_url(). '/lib/res/' . $file;
}

function resource_file_path($file): string
{
    global $kernel;
    return $kernel->resPath .'/'. $file;
}

function theme_file_path($file): string
{
    global $kernel;
	return $kernel->themePath .'/'. $file;
}


function get_domain_url(): string
{
    global $kernel;

    $siteOverride = $kernel->config->system->siteOverride ?? null;
    if ($siteOverride) {
        return $siteOverride;
    }

    $https = ($_SERVER['HTTPS'] ?? 'off') === 'on';
    $url = ($https ? 'https' : 'http') . '://' . $_SERVER['SERVER_NAME'];
    if ($_SERVER['SERVER_PORT'] != ($https ? '443' : '80')) {
        $url .= ':' . $_SERVER['SERVER_PORT'];
    }
    return $url;
}

function get_site_path(): string
{
    global $kernel;

    return $kernel->publicPath;
}

function get_site_url(): string
{
    return get_domain_url() . get_site_path();
}
