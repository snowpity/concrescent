<?php

require_once dirname(__FILE__).'/../lib/util/util.php';
require_once dirname(__FILE__).'/../lib/util/paypal.php';
require_once dirname(__FILE__).'/apply.php';

$site_url = get_site_url(true);

if (count($_GET) == 1) {
	if (!cm_app_cart_check_state('ready')) {
		header('Location: index.php?c=' . $ctx_lc);
		exit(0);
	}

	$total_price = 0;
	$app_id_map = array();
	foreach ($_SESSION['cart'] as $item) {
		$total_price += $item['price'];
		if (isset($app_id_map[$item['application-id']])) {
			$app_id_map[$item['application-id']] += $item['price'];
		} else {
			$app_id_map[$item['application-id']] = $item['price'];
		}
	}

	$group_uuid = $db->uuid();
	if ($total_price <= 0) {
		$payment_date = $db->now();
		foreach ($app_id_map as $id => $payment_price) {
			$apdb->update_payment_status($id, 'Completed', 'Free Ride', $group_uuid, $payment_price, $payment_date, 'Free Ride');
			$application = $apdb->get_application($id, false, true, $name_map, $fdb);
			$template = $mdb->get_mail_template('application-paid-' . $ctx_lc);
			foreach ($application['applicants'] as $applicant) {
				$mdb->send_mail($applicant['email-address'], $template, $applicant + $application);
			}
		}
		cm_app_cart_destroy();

		cm_app_message(
			'Payment Complete',
			'payment-complete',
			'Your '.$ctx_name_lc.' application has been confirmed and your payment, if required, has been accepted.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$application
		);
		exit(0);
	}

	/* if ($_SESSION['payment_method'] == 'paypal') */ {
		$paypal = new cm_paypal();
		$token = $paypal->get_token();

		$items = array();
		foreach ($_SESSION['cart'] as $item) {
			$items[] = $paypal->create_item($item['name'].' - '.$item['details'], $item['price']);
		}
		$total = $paypal->create_total($total_price);
		$txn = $paypal->create_transaction($items, $total, $group_uuid."::".$db->uuid());

		$payment = $paypal->create_payment_pp(
			$site_url.'/apply/checkout.php?c='.$ctx_lc.'&return',
			$site_url.'/apply/checkout.php?c='.$ctx_lc.'&cancel',
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
		$_SESSION['app_id_map'] = $app_id_map;
		$_SESSION['paypal_token'] = $token;
		$_SESSION['payment_id'] = $payment['id'];
		cm_app_cart_set_state('approval');
		header('Location: ' . $url);
		exit(0);
	}

	header('Location: index.php?c=' . $ctx_lc);
	exit(0);
}

if (isset($_GET['return'])) {
	if (!cm_app_cart_check_state('approval')) {
		header('Location: index.php?c=' . $ctx_lc);
		exit(0);
	}

	$total_price = $_SESSION['total_price'];
	$app_id_map = $_SESSION['app_id_map'];
	$token = $_SESSION['paypal_token'];
	$paypal = new cm_paypal($token);

	$payment_id = $_SESSION['payment_id'];
	$payer_id = $_GET['PayerID'] ?? null;
	$sale = $paypal->execute_payment($payment_id, $payer_id);
	$transaction_id = $paypal->get_transaction_id($sale);
	$details = json_encode($sale);

	$payment_date = $db->now();
	if ($transaction_id) {
		foreach ($app_id_map as $id => $payment_price) {
			$apdb->update_payment_status($id, 'Completed', 'PayPal', $transaction_id, $payment_price, $payment_date, $details);
			$application = $apdb->get_application($id, false, true, $name_map, $fdb);
			$template = $mdb->get_mail_template('application-paid-' . $ctx_lc);
			foreach ($application['applicants'] as $applicant) {
				$mdb->send_mail($applicant['email-address'], $template, $applicant + $application);
			}
		}
		cm_app_cart_destroy();

		cm_app_message(
			'Payment Complete',
			'payment-complete',
			'Your '.$ctx_name_lc.' application has been confirmed and your payment, if required, has been accepted.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$application
		);
		exit(0);
	} else {
		foreach ($app_id_map as $id => $payment_price) {
			$application = $apdb->get_application($id, false, true, $name_map, $fdb);
			$apdb->update_payment_status($id, 'Rejected', 'PayPal', $application['payment-group-uuid'], $payment_price, $payment_date, $details);
			$application = $apdb->get_application($id, false, true, $name_map, $fdb);
		}
		cm_app_cart_destroy();

		cm_app_message(
			'Payment Refused',
			'payment-refused',
			'PayPal has refused this transaction.<br><br>'.
			'PayPal says: [[payment-txn-msg]]<br><br>'.
			'Unfortunately, that is all we know. Please try again later.',
			array_merge($application, array('payment-txn-msg' => $sale['message']))
		);
		exit(0);
	}
}

if (isset($_GET['cancel'])) {
	if (!cm_app_cart_check_state('approval')) {
		header('Location: index.php?c=' . $ctx_lc);
		exit(0);
	}

	$total_price = $_SESSION['total_price'];
	$app_id_map = $_SESSION['app_id_map'];

	$payment_date = $db->now();
	foreach ($app_id_map as $id => $payment_price) {
		$application = $apdb->get_application($id, false, true, $name_map, $fdb);
		$apdb->update_payment_status($id, 'Cancelled', 'PayPal', $application['payment-group-uuid'], $payment_price, $payment_date, 'Cancelled');
		$application = $apdb->get_application($id, false, true, $name_map, $fdb);
	}
	cm_app_cart_destroy();

	cm_app_message(
		'Payment Cancelled',
		'payment-cancelled',
		'You have cancelled your payment.',
		$application
	);
	exit(0);
}

header('Location: index.php?c=' . $ctx_lc);
