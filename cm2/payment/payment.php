<?php

use JetBrains\PhpStorm\NoReturn;

session_name('PHPSESSID_CMPAY');
session_start();

require_once __DIR__ .'/../lib/database/database.php';
require_once __DIR__ .'/../lib/database/payment.php';
require_once __DIR__ .'/../lib/database/mail.php';
require_once __DIR__ .'/../lib/util/res.php';
require_once __DIR__ .'/../lib/util/util.php';

$db = new cm_db();
$pdb = new cm_payment_db($db);
$mdb = new cm_mail_db($db);

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

function cm_payment_head($title) {
	echo '<!DOCTYPE HTML>';
	echo '<html>';
	echo '<head>';
	echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	echo '<title>' . htmlspecialchars($title) . '</title>';
	echo '<link rel="shortcut icon" href="' . htmlspecialchars(theme_file_url('favicon.ico', false)) . '">';
	echo '<link rel="stylesheet" href="' . htmlspecialchars(resource_file_url('cm.css', false)) . '">';
	echo '<link rel="stylesheet" href="' . htmlspecialchars(theme_file_url('theme.css', false)) . '">';
}

function cm_payment_body($title) {
	echo '</head>';
	echo '<body class="cm-payment">';
	echo '<header>';
	echo '<div class="pagename">' . htmlspecialchars($title) . '</div>';
	echo '</header>';
}

function cm_payment_tail() {
	echo '</body>';
	echo '</html>';
}

#[NoReturn]
function cm_payment_message($title, $text) {
	cm_payment_head($title);
	cm_payment_body($title);
	echo '<article>';
	echo '<div class="card">';
	echo '<div class="card-title">';
	echo htmlspecialchars($title);
	echo '</div>';
	echo '<div class="card-content">';
	echo safe_html_string($text, true);
	echo '</div>';
	echo '</div>';
	echo '</article>';
	cm_payment_tail();
	exit(0);
}
