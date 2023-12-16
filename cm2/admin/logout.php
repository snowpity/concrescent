<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once dirname(__FILE__).'/../lib/database/database.php';
require_once dirname(__FILE__).'/../lib/database/admin.php';
require_once dirname(__FILE__).'/../lib/util/res.php';
require_once '../../vendor/autoload.php';

$db = new cm_db();
$adb = new cm_admin_db($db);
$adb->log_access();
$adb->log_out();

$loader = new \Twig\Loader\FilesystemLoader(__DIR__);
$twig = new \Twig\Environment($loader, ['debug' => true]);

try {
    // Render the Twig template with the variables
    echo $twig->render('logout.twig', [
		'shortcutIcon' => theme_file_url('favicon.ico', false),
		'stylesheet1' => resource_file_url('cm.css', false),
		'stylesheet2' =>theme_file_url('theme.css', false),
	]);
} catch ( \Throwable $e) {
    echo '<pre>';
    var_dump($e);
    echo '</pre>';
}
