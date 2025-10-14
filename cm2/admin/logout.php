<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../../src/lib/util/res.php';

global $twig;

$adb = $kernel->container->cm_admin_db;
$adb->log_access();
$adb->log_out();

echo $twig->render('pages/admin/logout.twig');
