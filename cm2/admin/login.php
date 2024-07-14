<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/admin.php';
require_once __DIR__ .'/../lib/util/res.php';
global $twig, $log;

$page = $_GET['page'] ?? null;
$attempted = false;

$db = new cm_db();
$adb = new cm_admin_db($db);
if (isset($_POST['username']) && isset($_POST['password'])) {
	if ($adb->log_in($_POST['username'], $_POST['password'])) {

        $log->audit->info(
            'User logged in.',
            ['sub' => 'user','username' => $_POST['username']]
        );

		$adb->log_access();
		if ($page) {
			header('Location: ' . $page);
		} else {
			header('Location: index.php');
		}
		exit(0);
	}
	$attempted = true;

    $log->audit->notice(
        'Unsuccessful login attempt.',
        ['sub' => 'user', 'username' => $_POST['username']]
    );
}
$adb->log_out();

echo $twig->render('pages/admin/login.twig', [
	'page' => $page,
	'attempted' => $attempted,
]);
