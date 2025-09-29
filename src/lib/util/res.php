<?php

use App\Kernel;

include_once __DIR__ .'/../../../config/concrescent.php';

if (!isset($cm_config)) {
    die('Config file not found.');
}
require_once __DIR__.'/../../../vendor/autoload.php';

require_once __DIR__ .'/util.php';

$kernel = new Kernel();

$log = $kernel->log;
$twig = $kernel->twig;

function resource_file_url($file) {
	return get_site_url(). '/lib/res/' . $file;
}

function resource_file_path($file) {
    global $kernel;
    return $kernel->resPath .'/'. $file;
}

function theme_file_path($file) {
    global $kernel;
	return $kernel->themePath .'/'. $file;
}


function get_domain_url(): string
{
    global $kernel;

    $siteOverride = $kernel->config['site-override'] ?? null;
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
