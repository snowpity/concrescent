<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/util/paypal.php';
require_once __DIR__ .'/staff.php';

$site_url = get_site_url(true);

if (!$_GET) {
	if (!cm_app_cart_check_state('ready')) {
		header('Location: index.php');
		exit(0);
	}

	$total_price = 0;
	$staff_ids = array();
	foreach ($_SESSION['cart'] as $item) {
		$total_price += (float)$item['payment-badge-price'];
		$staff_ids[] = $item['id'];
	}

	$group_uuid = $db->uuid();
	if ($total_price <= 0) {
		$payment_date = $db->now();
		foreach ($staff_ids as $id) {
			$sdb->update_payment_status($id, 'Completed', 'Free Ride', $group_uuid, $total_price, $payment_date, 'Free Ride');
			$staff_member = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
			$template = $mdb->get_mail_template('staff-paid');
			$mdb->send_mail($staff_member['email-address'], $template, $staff_member);
		}
		cm_app_cart_destroy();

		cm_app_message(
			'Payment Complete',
			'payment-complete',
			'Your staff application has been confirmed and your payment, if required, has been accepted.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$staff_member
		);
		exit(0);
	}

	/* if ($_SESSION['payment_method'] == 'paypal') */ {
		$paypal = new cm_paypal();
		$token = $paypal->get_token();

		$items = array();
		foreach ($_SESSION['cart'] as $item) {
			$badge_type_id = (int)$item['badge-type-id'];
			$badge_type_name = $name_map[$badge_type_id] ?? $badge_type_id;
			$items[] = $paypal->create_item($badge_type_name, $item['payment-badge-price']);
		}
		$total = $paypal->create_total($total_price);
		$txn = $paypal->create_transaction($items, $total, $group_uuid."::".$db->uuid());

		$payment = $paypal->create_payment_pp(
			$site_url.'/staff/checkout.php?return',
			$site_url.'/staff/checkout.php?cancel',
			$txn
		);
		$url = $paypal->get_payment_approval_url($payment);
		if (!$url) {
			cm_app_message(
				'Communication Failure',
				'communication-failure',
				'Failed to communicate with PayPal.<br><br>'.
				'If you are the site administrator, check the '.
				'config file and/or your version of OpenSSL.<br><br>'.
				'If you are not the site administrator, please '.
				'<b><a href="mailto:[[contact-address]]">contact us</a></b> '.
				'and report this error.'
			);
			exit(0);
		}

		$_SESSION['total_price'] = $total_price;
		$_SESSION['staff_ids'] = $staff_ids;
		$_SESSION['paypal_token'] = $token;
		$_SESSION['payment_id'] = $payment['id'];
		cm_app_cart_set_state('approval');
		header('Location: ' . $url);
		exit(0);
	}

	header('Location: index.php');
	exit(0);
}

if (isset($_GET['return'])) {
	if (!cm_app_cart_check_state('approval')) {
		header('Location: index.php');
		exit(0);
	}

	$total_price = $_SESSION['total_price'];
	$staff_ids = $_SESSION['staff_ids'];
	$token = $_SESSION['paypal_token'];
	$paypal = new cm_paypal($token);

	$payment_id = $_SESSION['payment_id'];
	$payer_id = $_GET['PayerID'] ?? null;
	$sale = $paypal->execute_payment($payment_id, $payer_id);
	$transaction_id = $paypal->get_transaction_id($sale);
	$details = json_encode($sale);

	$payment_date = $db->now();
	if ($transaction_id) {
		foreach ($staff_ids as $id) {
			$sdb->update_payment_status($id, 'Completed', 'PayPal', $transaction_id, $total_price, $payment_date, $details);
			$staff_member = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
			$template = $mdb->get_mail_template('staff-paid');
			$mdb->send_mail($staff_member['email-address'], $template, $staff_member);
		}
		cm_app_cart_destroy();

		cm_app_message(
			'Payment Complete',
			'payment-complete',
			'Your staff application has been confirmed and your payment, if required, has been accepted.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$staff_member
		);
		exit(0);
	} else {
		foreach ($staff_ids as $id) {
			$staff_member = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
			$sdb->update_payment_status($id, 'Rejected', 'PayPal', $staff_member['payment-group-uuid'], $total_price, $payment_date, $details);
			$staff_member = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
		}
		cm_app_cart_destroy();

		cm_app_message(
			'Payment Refused',
			'payment-refused',
			'PayPal has refused this transaction.<br><br>'.
			'PayPal says: [[payment-txn-msg]]<br><br>'.
			'Unfortunately, that is all we know. Please try again later.',
			array_merge($staff_member, array('payment-txn-msg' => $sale['message']))
		);
		exit(0);
	}
}

if (isset($_GET['cancel'])) {
	if (!cm_app_cart_check_state('approval')) {
		header('Location: index.php');
		exit(0);
	}

	$total_price = $_SESSION['total_price'];
	$staff_ids = $_SESSION['staff_ids'];

	$payment_date = $db->now();
	foreach ($staff_ids as $id) {
		$staff_member = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
		$sdb->update_payment_status($id, 'Cancelled', 'PayPal', $staff_member['payment-group-uuid'], $total_price, $payment_date, 'Cancelled');
		$staff_member = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
	}
	cm_app_cart_destroy();

	cm_app_message(
		'Payment Cancelled',
		'payment-cancelled',
		'You have cancelled your payment.',
		$staff_member
	);
	exit(0);
}

header('Location: index.php');
