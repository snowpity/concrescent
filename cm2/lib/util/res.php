<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/util.php';
require_once __DIR__.'/../../../vendor/autoload.php';

$log = \App\Log\LogLibrary::createSingleInstance();

$twig = new \Twig\Environment(
	new \Twig\Loader\FilesystemLoader(__DIR__.'/../../../templates'),
	['debug' => true]
);
$twig->addFunction(new \Twig\TwigFunction('theme_file_url', theme_file_url(...)));
$twig->addFunction(new \Twig\TwigFunction('resource_file_url', resource_file_url(...)));
$twig->addFunction(new \Twig\TwigFunction('get_site_url', get_site_url(...)));
$twig->addFilter(new \Twig\TwigFilter('price_string', price_string(...)));

function config_file_path($file) {
	return realpath(__DIR__ . '/../../config') . '/' . $file;
}

function config_file_url($file, $full) {
	return get_site_url($full) . '/config/' . $file;
}

function resource_file_path($file) {
	return realpath(__DIR__ . '/../res') . '/' . $file;
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
	return realpath(__DIR__ . '/../../' . theme_location()) . '/' . $file;
}

function theme_file_url($file, $full) {
	return get_site_url($full) . '/' . theme_location() . '/' . $file;
}
