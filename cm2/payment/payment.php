<?php

use JetBrains\PhpStorm\NoReturn;

session_name('PHPSESSID_CMPAY');
session_start();

require_once __DIR__ .'/../../src/lib/util/res.php';

$db = $kernel->container->cm_db;
$pdb = $kernel->container->cm_payment_db;
$mdb = $kernel->container->cm_mail_db;

function cm_payment_cart_set_state($state, $cart = null) {
	if ($cart) $_SESSION['cart'] = $cart;
	if (!isset($_SESSION['cart'])) $_SESSION['cart'] = array();
	$_SESSION['cart_hash'] = md5(serialize($_SESSION['cart']));
	$_SESSION['cart_state'] = $state;
}

function cm_payment_cart_check_state($expected_state) {
	if (!isset($_SESSION['cart'])) return false;
	if (!isset($_SESSION['cart_hash'])) return false;
	if (!isset($_SESSION['cart_state'])) return false;
	$expected_hash = md5(serialize($_SESSION['cart']));
	if ($_SESSION['cart_hash'] != $expected_hash) return false;
	if ($_SESSION['cart_state'] != $expected_state) return false;
	return true;
}

function cm_payment_cart_destroy() {
	unset($_SESSION['cart']);
	unset($_SESSION['cart_hash']);
	unset($_SESSION['cart_state']);
	session_destroy();
}

#[NoReturn]
function cm_payment_message($title, $text) {
    global $twig;

    echo $twig->render('pages/payment/message.twig', [
        'title' => $title,
        'text' => safe_html_string($text, true),
    ]);

	exit(0);
}
