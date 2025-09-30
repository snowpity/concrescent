<?php

use App\Lib\Database\cm_admin_db;
use App\Lib\Database\cm_db;

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../../src/lib/util/res.php';

global $twig;

$db = new cm_db();
$adb = new cm_admin_db($db);
$adb->log_access();
$adb->log_out();

echo $twig->render('pages/admin/logout.twig');
