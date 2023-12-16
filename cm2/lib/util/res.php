<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/util.php';
require_once __DIR__.'/../../../vendor/autoload.php';

$twig = new \Twig\Environment(
	new \Twig\Loader\FilesystemLoader(__DIR__.'/../../../templates'),
	['debug' => true]
);

function config_file_path($file) {
	return realpath(dirname(__FILE__) . '/../../config') . '/' . $file;
}

function config_file_url($file, $full) {
	return get_site_url($full) . '/config/' . $file;
}

function resource_file_path($file) {
	return realpath(dirname(__FILE__) . '/../res') . '/' . $file;
}

function resource_file_url($file, $full) {
	return get_site_url($full) . '/lib/res/' . $file;
}

function theme_location() {
	if (isset($_COOKIE['theme_location']) && $_COOKIE['theme_location']) {
		return $_COOKIE['theme_location'];
	} else {
		return $GLOBALS['cm_config']['theme']['location'];
	}
}

function theme_file_path($file) {
	return realpath(dirname(__FILE__) . '/../../' . theme_location()) . '/' . $file;
}

function theme_file_url($file, $full) {
	return get_site_url($full) . '/' . theme_location() . '/' . $file;
}
