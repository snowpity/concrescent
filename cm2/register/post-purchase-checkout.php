<?php

require_once dirname(__FILE__).'/../lib/util/util.php';
require_once dirname(__FILE__).'/../lib/util/paypal.php';
require_once dirname(__FILE__).'/register.php';

function merge_post_purchase_changes(
	&$old_item, &$new_item, $payment_status, $payment_uuid, $payment_type,
	$payment_txn_id, $payment_txn_amt, $payment_date, $payment_details
) {
	$old_item['first-name'] = $new_item['first-name'];
	$old_item['last-name'] = $new_item['last-name'];
	$old_item['fandom-name'] = $new_item['fandom-name'];
	$old_item['name-on-badge'] = $new_item['name-on-badge'];

	if (isset($new_item['new-badge-type'])) {
		$bt = $new_item['new-badge-type'];
		if (isset($bt['price-diff'])) {
			$old_item['badge-type-id'] = $bt['id'];
			$old_item['payment-status'] = $payment_status;
			$old_item['payment-badge-price'] = $bt['price'];
			$old_item['payment-promo-code'] = null;
			$old_item['payment-promo-price'] = $bt['price'];
			$old_item['payment-group-uuid'] = $payment_uuid;
			$old_item['payment-type'] .= ', ' . $payment_type;
			$old_item['payment-txn-id'] .= ', ' . $payment_txn_id;
			$old_item['payment-txn-amt'] += $payment_txn_amt;
			$old_item['payment-date'] = $payment_date;
			$old_item['payment-details'] .= "\n\n" . $payment_details;
		}
	}

	if (isset($new_item['new-addons'])) {
		foreach ($new_item['new-addons'] as $addon) {
			$addon['payment-price'] = $addon['price'];
			$addon['payment-status'] = $payment_status;
			$addon['payment-type'] = $payment_type;
			$addon['payment-txn-id'] = $payment_txn_id;
			$addon['payment-txn-amt'] = $payment_txn_amt;
			$addon['payment-date'] = $payment_date;
			$addon['payment-details'] = $payment_details;
			if (isset($old_item['addons'])) {
				$old_item['addons'][] = $addon;
			} else {
				$old_item['addons'] = array($addon);
			}
		}
	}
}

function create_post_purchase_paypal_items(&$paypal, &$new_item) {
	$items = array();

	if (isset($new_item['new-badge-type'])) {
		$bt = $new_item['new-badge-type'];
		if (isset($bt['price-diff'])) {
			$items[] = $paypal->create_item($bt['name'], $bt['price-diff']);
		}
	}

	if (isset($new_item['new-addons'])) {
		foreach ($new_item['new-addons'] as $addon) {
			$items[] = $paypal->create_item($addon['name'], $addon['price']);
		}
	}

	return $items;
}

$site_url = get_site_url(true);

if (!$_GET) {
	if (!cm_reg_post_edit_check_state('ready')) {
		header('Location: index.php');
		exit(0);
	}

	$item = cm_reg_post_edit_get();
	$total_price = cm_reg_post_edit_total();

	$group_uuid = $db->uuid();
	if ($total_price <= 0) {
		$payment_date = $db->now();

		$attendee = $atdb->get_attendee($item['id'], false, $name_map, $fdb);
		merge_post_purchase_changes(
			$attendee, $item, 'Completed', $group_uuid, 'Free Ride',
			$group_uuid, 0, $payment_date, 'Free Ride'
		);
		$atdb->update_attendee($attendee);

		$attendee = $atdb->get_attendee($item['id'], false, $name_map, $fdb);
		$template = $mdb->get_mail_template('attendee-paid');
		$mdb->send_mail($attendee['email-address'], $template, $attendee);
		cm_reg_post_edit_destroy();

		cm_reg_message(
			'Changes Saved',
			'post-purchase-changes-saved',
			'Your changes have been saved.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$attendee
		);
		exit(0);
	}

	if ($_SESSION['payment_method'] == 'paypal') {
		$paypal = new cm_paypal();
		$token = $paypal->get_token();

		$items = create_post_purchase_paypal_items($paypal, $item);
		$total = $paypal->create_total($total_price);
		$txn = $paypal->create_transaction($items, $total, $group_uuid."::".$db->uuid());

		$payment = $paypal->create_payment_pp(
			$site_url.'/register/post-purchase-checkout.php?return',
			$site_url.'/register/post-purchase-checkout.php?cancel',
			$txn
		);
		$url = $paypal->get_payment_approval_url($payment);
		if (!$url) {
			cm_reg_message(
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

		$_SESSION['paypal_token'] = $token;
		$_SESSION['payment_id'] = $payment['id'];
		cm_reg_post_edit_set_state('approval');
		header('Location: ' . $url);
		exit(0);
	}

	header('Location: index.php');
	exit(0);
}

if (isset($_GET['return'])) {
	if (!cm_reg_post_edit_check_state('approval')) {
		header('Location: index.php');
		exit(0);
	}

	$token = $_SESSION['paypal_token'];
	$paypal = new cm_paypal($token);

	$payment_id = $_SESSION['payment_id'];
	$payer_id = isset($_GET['PayerID']) ? $_GET['PayerID'] : null;
	$sale = $paypal->execute_payment($payment_id, $payer_id);
	$transaction_id = $paypal->get_transaction_id($sale);
	$details = json_encode($sale);

	if ($transaction_id) {
		$item = cm_reg_post_edit_get();
		$total_price = cm_reg_post_edit_total();
		$group_uuid = $db->uuid();
		$payment_date = $db->now();

		$attendee = $atdb->get_attendee($item['id'], false, $name_map, $fdb);
		merge_post_purchase_changes(
			$attendee, $item, 'Completed', $group_uuid, 'PayPal',
			$transaction_id, $total_price, $payment_date, $details
		);
		$atdb->update_attendee($attendee);

		$attendee = $atdb->get_attendee($item['id'], false, $name_map, $fdb);
		$template = $mdb->get_mail_template('attendee-paid');
		$mdb->send_mail($attendee['email-address'], $template, $attendee);
		cm_reg_post_edit_destroy();

		cm_reg_message(
			'Payment Complete',
			'payment-complete',
			'Your payment has been accepted.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$attendee
		);
		exit(0);
	} else {
		$item = cm_reg_post_edit_get();
		$attendee = $atdb->get_attendee($item['id'], false, $name_map, $fdb);
		cm_reg_post_edit_destroy();

		cm_reg_message(
			'Payment Refused',
			'payment-refused',
			'PayPal has refused this transaction.<br><br>'.
			'PayPal says: [[payment-txn-msg]]<br><br>'.
			'Unfortunately, that is all we know. Please try again later.',
			array_merge($attendee, array('payment-txn-msg' => $sale['message']))
		);
		exit(0);
	}
}

if (isset($_GET['cancel'])) {
	if (!cm_reg_post_edit_check_state('approval')) {
		header('Location: index.php');
		exit(0);
	}

	$item = cm_reg_post_edit_get();
	$attendee = $atdb->get_attendee($item['id'], false, $name_map, $fdb);
	cm_reg_post_edit_destroy();

	cm_reg_message(
		'Payment Cancelled',
		'payment-cancelled',
		'You have cancelled your payment.',
		$attendee
	);
	exit(0);
}

header('Location: index.php');