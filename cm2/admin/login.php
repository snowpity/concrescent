<?php

session_name('PHPSESSID_CMADMIN');
session_start();

require_once __DIR__ .'/../../src/lib/util/res.php';

global $kernel, $request;

$auditLog = $kernel->container->log->audit;

$page = $request->query->get('page') ?: 'index.php';
$attempted = false;

$adb = $kernel->container->cm_admin_db;

$username = $request->request->get('username');
$password = $request->request->get('password');

if ($username && $password) {
	if ($adb->log_in($username, $password)) {

        $auditLog->info(
            'User logged in.',
            ['sub' => 'user','username' => $username]
        );

		$adb->log_access();

        header('Location: ' . $page);

		exit(0);
	}
	$attempted = true;

    $auditLog->notice(
        'Unsuccessful login attempt.',
        ['sub' => 'user', 'username' => $username]
    );
}
$adb->log_out();

echo $kernel->container->twig->render('pages/admin/login.twig', [
	'page' => $page,
	'attempted' => $attempted,
]);
