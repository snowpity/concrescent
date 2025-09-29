<?php

use App\Kernel;

include_once __DIR__ .'/../../../config/concrescent.php';

if (!isset($cm_config)) {
    die('Config file not found.');
}

require_once __DIR__ .'/util.php';
require_once __DIR__.'/../../../vendor/autoload.php';

$kernel = new Kernel();

$log = $kernel->log;
$twig = $kernel->twig;


function resource_file_url($file, $full) {
	return get_site_url($full) . '/lib/res/' . $file;
}

function theme_file_url($file, $full) {
	return get_site_url($full) . '/' . $GLOBALS['cm_config']['theme']['location'] . '/' . $file;
}
