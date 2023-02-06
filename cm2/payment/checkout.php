<?php

require_once dirname(__FILE__).'/../lib/util/util.php';
require_once dirname(__FILE__).'/../lib/util/paypal.php';
require_once dirname(__FILE__).'/payment.php';

$site_url = get_site_url(true);

if (!$_GET) {
	if (!cm_payment_cart_check_state('ready')) {
		header('Location: index.php');
		exit(0);
	}

	$group_id = $db->uuid();
	$item = $_SESSION['cart'];
	$price = (float)$item['payment-price'];

	if ($price <= 0) {
		$pdb->update_payment_status($item['id'], 'Completed', 'Free Ride', $group_id, $price, $db->now(), 'Free Ride');
		$item = $pdb->get_payment($item['id']);
		$template_name = 'payment-completed-' . $item['mail-template'];
		$template = $mdb->get_mail_template($template_name);
		$mdb->send_mail($item['email-address'], $template, $item);
		cm_payment_cart_destroy();

		cm_payment_message(
			'Payment Complete',
			'Your payment has been accepted.<br><br>'.
			'You can <b><a href="'.$item['review-link'].'">'.
			'review your order</a></b> at any time.'
		);
		exit(0);
	}

	/* if ($_SESSION['payment_method'] == 'paypal') */ {
		$paypal = new cm_paypal();
		$token = $paypal->get_token();

		$items = array($paypal->create_item($item['payment-name'], $price));
		$total = $paypal->create_total($price);
		$txn = $paypal->create_transaction($items, $total, $group_id."::".$db->uuid());

		$payment = $paypal->create_payment_pp(
			$site_url.'/payment/checkout.php?return',
			$site_url.'/payment/checkout.php?cancel',
			$txn
		);
		$url = $paypal->get_payment_approval_url($payment);
		if (!$url) {
			$template_name = 'payment-completed-' . $item['mail-template'];
			$contact_address = $mdb->get_contact_address($template_name);
			cm_payment_message(
				'Communication Failure',
				'Failed to communicate with PayPal.<br><br>'.
				'If you are the site administrator, check the '.
				'config file and/or your version of OpenSSL.<br><br>'.
				'If you are not the site administrator, please '.
				'<b><a href="mailto:'.$contact_address.'">contact us</a></b> '.
				'and report this error.'
			);
			exit(0);
		}

		$_SESSION['paypal_token'] = $token;
		$_SESSION['payment_id'] = $payment['id'];
		cm_payment_cart_set_state('approval');
		header('Location: ' . $url);
		exit(0);
	}

	header('Location: index.php');
	exit(0);
}

if (isset($_GET['return'])) {
	if (!cm_payment_cart_check_state('approval')) {
		header('Location: index.php');
		exit(0);
	}

	$item = $_SESSION['cart'];
	$price = (float)$item['payment-price'];
	$token = $_SESSION['paypal_token'];
	$paypal = new cm_paypal($token);

	$payment_id = $_SESSION['payment_id'];
	$payer_id = isset($_GET['PayerID']) ? $_GET['PayerID'] : null;
	$sale = $paypal->execute_payment($payment_id, $payer_id);
	$transaction_id = $paypal->get_transaction_id($sale);
	$details = json_encode($sale);

	$payment_date = $db->now();
	if ($transaction_id) {
		$pdb->update_payment_status($item['id'], 'Completed', 'PayPal', $transaction_id, $price, $payment_date, $details);
		$item = $pdb->get_payment($item['id']);
		$template_name = 'payment-completed-' . $item['mail-template'];
		$template = $mdb->get_mail_template($template_name);
		$mdb->send_mail($item['email-address'], $template, $item);
		cm_payment_cart_destroy();

		cm_payment_message(
			'Payment Complete',
			'Your payment has been accepted.<br><br>'.
			'You can <b><a href="'.$item['review-link'].'">'.
			'review your order</a></b> at any time.'
		);
		exit(0);
	} else {
		$pdb->update_payment_status($item['id'], 'Rejected', 'PayPal', $db->uuid(), $price, $payment_date, $details);
		cm_payment_cart_destroy();

		cm_payment_message(
			'Payment Refused',
			'PayPal has refused this transaction.<br><br>'.
			'PayPal says: '.$sale['message'].'<br><br>'.
			'Unfortunately, that is all we know. Please try again later.'
		);
		exit(0);
	}
}

if (isset($_GET['cancel'])) {
	if (!cm_payment_cart_check_state('approval')) {
		header('Location: index.php');
		exit(0);
	}

	$item = $_SESSION['cart'];
	$price = (float)$item['payment-price'];

	$pdb->update_payment_status($item['id'], 'Cancelled', 'PayPal', $db->uuid(), $price, $db->now(), 'Cancelled');
	cm_payment_cart_destroy();

	cm_payment_message(
		'Payment Cancelled',
		'You have cancelled your payment.'
	);
	exit(0);
}

header('Location: index.php');