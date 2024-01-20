<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/admin.php';
require_once __DIR__ .'/../lib/util/res.php';
global $twig;

$db = new cm_db();
$adb = new cm_admin_db($db);
$adb->log_access();
$adb->log_out();

echo $twig->render('pages/admin/logout.twig');
