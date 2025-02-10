<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/payment.php';

$uid = isset($_GET['uid']) ? trim($_GET['uid']) : null;
if (!$uid) {
	header('Location: index.php');
	exit(0);
}
$item = $pdb->get_payment(null, $uid);
if (!$item) {
	header('Location: index.php');
	exit(0);
}

if ($item['payment-status'] !== 'Completed' && isset($_POST['submit'])) {
	cm_payment_cart_set_state('ready', $item);
	header('Location: checkout.php');
	exit(0);
}

$title = ($item['payment-status'] === 'Completed') ? 'Review Order' : 'Make a Payment';
$template_name = 'payment-requested-' . $item['mail-template'];
$contact_address = $mdb->get_contact_address($template_name);

global $cm_config;
$salesTax = ($cm_config['payment']['sales_tax'] ?? 0);

$salesTaxAmount = $item['sales-tax'] ? $item['payment-price'] * $salesTax : 0;

global $twig;
echo $twig->render('pages/payment/review.twig', [
    'title' => $title,
    'uid' => $uid,
    'item' => $item,
    'salesTaxAmount' => $salesTaxAmount,
    'total' => $item['payment-price'] + $salesTaxAmount,
    'paymentCompleted' => $item['payment-status'] === 'Completed',
    'contact_address' => $contact_address,
]);
