<?php

use App\Lib\Database\cm_admin_db;
use App\Lib\Database\cm_db;

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../../src/lib/util/res.php';

global $twig, $log, $request;

$page = $request->query->get('page') ?: 'index.php';
$attempted = false;

$db = new cm_db();
$adb = new cm_admin_db($db);

$username = $request->request->get('username');
$password = $request->request->get('password');

if ($username && $password) {
	if ($adb->log_in($username, $password)) {

        $log->audit->info(
            'User logged in.',
            ['sub' => 'user','username' => $username]
        );

		$adb->log_access();

        header('Location: ' . $page);

		exit(0);
	}
	$attempted = true;

    $log->audit->notice(
        'Unsuccessful login attempt.',
        ['sub' => 'user', 'username' => $username]
    );
}
$adb->log_out();

echo $twig->render('pages/admin/login.twig', [
	'page' => $page,
	'attempted' => $attempted,
]);
