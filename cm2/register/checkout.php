<?php

require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/util/slack.php';
require_once __DIR__ .'/../lib/util/paypal.php';
require_once __DIR__ .'/register.php';

$site_url = get_site_url(true);

if (!$_GET) {
	if (!cm_reg_cart_check_state('ready')) {
		header('Location: index.php');
		exit(0);
	}

	$group_uuid = "";
	$transaction_id = $db->uuid();  //Generate an ID in case it's cash or something
	//pre-check if we have a group ID already
	for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
		$item = cm_reg_cart_get($i);
		if(isset($item['payment-group-uuid'] ) && $item['payment-group-uuid'] != $group_uuid)
		{
			if($group_uuid == "")
			{
				//Sweet, it's the first time we've seen it. Associate!
				$group_uuid = $item['payment-group-uuid'];
			}
			else {
				//Um, it doesn't match... :O
				//TODO: Write an error?
			}
		}
	}
	//Did we receive an existing group?
	if($group_uuid == "")
		$group_uuid = $db->uuid();

	$total_price = cm_reg_cart_total();
	$payment_date = $db->now();
	$attendee_ids = array();
	$blacklisted = false;
	$failedSave = false;
	for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
		$item = cm_reg_cart_get($i);
		$item['payment-group-uuid'] = $group_uuid;
		$item['payment-txn-id'] = $transaction_id;
		$item['payment-txn-amt'] = $total_price;
		if (isset($item['addons']) && $item['addons']) {
			for ($j = 0, $m = count($item['addons']); $j < $m; $j++) {
				if($item['addons'][$j]['payment-status'] == 'Incomplete')
				{
					$item['addons'][$j]['payment-txn-id'] = $transaction_id;
					$item['addons'][$j]['payment-txn-amt'] = $total_price;
					$item['addons'][$j]['payment-date'] = null;
				}
			}
		}

		if(isset($item['editing-badge']) && $item['editing-badge'] > 0 )
		{
			//First, find them in the attendees table
			$existingBadge = $atdb->get_attendee($item['editing-badge'], $item['uuid'] );
			//Update their status
					$shouldAddToTransaction = false;
					if($item['badge-type-id'] != $existingBadge['badge-type-id'])
						$shouldAddToTransaction = true;
					if(isset($item['editing-prior-addon-ids']) && isset($item['addon-ids'])
					&& count($item['editing-prior-addon-ids']) != count($item['addon-ids']))
						$shouldAddToTransaction = true;

					//Merge in the existing payment details
					$item['payment-type']     = ($existingBadge['payment-type'] ?? null);
					$item['payment-details']  = ($existingBadge['payment-details'] ?? null);
					if(!$shouldAddToTransaction)
					{
						$item['payment-promo-price']  = ($existingBadge['payment-promo-price'] ?? null);
						$item['payment-txn-id']  = ($existingBadge['payment-txn-id'] ?? null);
						$item['payment-status']  = ($existingBadge['payment-status'] ?? null);
						$item['payment-txn-amt'] = ($existingBadge['payment-txn-amt'] ?? null);
					}

					if (isset($item['addons']) && $item['addons']) {
						for ($j = 0, $m = count($item['addons']); $j < $m; $j++) {
							if($item['addons'][$j]['payment-status'] != 'Incomplete')
							{
								$item['addons'][$j]['payment-type'] = $existingBadge['addons'][$j]['payment-type'];
								$item['addons'][$j]['payment-txn-id'] = $existingBadge['addons'][$j]['payment-txn-id'];
								$item['addons'][$j]['payment-txn-amt'] = $existingBadge['addons'][$j]['payment-txn-amt'];
								$item['addons'][$j]['payment-date'] = $existingBadge['addons'][$j]['payment-date'];
								$item['addons'][$j]['payment-details'] = $existingBadge['addons'][$j]['payment-details'];
							}
						}
					}
					//Call an update
					$item['id'] = $item['editing-badge'];
					$atdb->update_attendee($item, $fdb);
					if($shouldAddToTransaction)
						$attendee_ids[$i] = $existingBadge['id'];
					$item['id'] = $existingBadge['id'];
		}
		else
		{
			$item['payment-type']     = $_SESSION['payment_method'] == 'cash' ? 'Cash' : ($_SESSION['payment_method'] == 'paypal' ? 'PayPal' : null);
			$item['payment-details']  = transaction_details_update("",$transaction_id,array('id' => $transaction_id, 'type' => $item['payment-type'] ));//Empty transaction info
			//Should we be pre-complete?
			if($item['payment-promo-price'] <= 0){
				$item['payment-status'] = 'Completed';
				$item['payment-date'] = $payment_date;
			}

			$newId = $atdb->create_attendee($item, $fdb);
			if($newId !== false)
			{
				$attendee_ids[$i] = $newId;
				if ($atdb->is_blacklisted($item)) $blacklisted = true;
				$item['id'] = $newId;

			} else {
					$failedSave = true;
			}
		}
		cm_reg_cart_set($i,$item);
	}

	if ($blacklisted) {
		foreach ($attendee_ids as $id) {
			$atdb->update_payment_status($id, 'Incomplete', 'Blacklisted', $group_uuid, 'Blacklisted');
			$attendee = $atdb->get_attendee($id, false, $name_map, $fdb);
		}
		cm_reg_cart_destroy();

		if ($contact_address) {
			$body = 'The following attendee registrations were just blacklisted:'."\r\n";
			foreach ($attendee_ids as $id) {
				$body .= "\r\n".$site_url.'/admin/attendee/edit.php?id='.$id;
			}
			mail(
				$contact_address, 'Blacklisted Attendee Registration',
				$body, 'From: '.$contact_address
			);
		}

		$slack = new cm_slack();
		if ($slack->get_hook_url('attendee-blacklisted')) {
			$body = 'The following attendee registrations were just blacklisted:';
			foreach ($attendee_ids as $id) {
				$body .= ' '.$slack->make_link($site_url.'/admin/attendee/edit.php?id='.$id, 'A'.$id);
			}
			$slack->post_message('attendee-blacklisted', $body);
		}

		cm_reg_message(
			'Could Not Complete Registration',
			'blacklisted',
			'We\'re sorry, there was an issue with your registration '.
			'and your registration could not be completed.<br><br>'.
			'If you think this is an error, please '.
			'<b><a href="mailto:[[contact-address]]">contact us</a></b>.',
			$attendee
		);
		exit(0);
	}

	if($failedSave)
	{
			cm_reg_message(
				'Could Not Complete Registration',
				'input-error',
				'We\'re sorry, there was an issue with your registration '.
				'and your registration could not be completed.<br><br>'.
				'You may be able to try again, or adjust your badge details.<br>'.
				'Click the button below to return to the Cart:',
				null
			);
			exit(0);
	}

	if ($total_price <= 0) {
		//TODO: Handle free add-ons?
		$attendee = array();

		for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
			$item = cm_reg_cart_get($i);
			$payment = array('id' => $transaction_id, 'type' => isset($item['editing-badge']) && $item['editing-badge'] > 0 ? 'Badge Edit' : 'Free Ride' );
			$newDetails = transaction_details_update($item['payment-details'],$transaction_id,$payment);
			$atdb->update_payment_status($item['id'], 'Completed', 'PayPal', $transaction_id, $newDetails);

			$attendee = $atdb->get_attendee($item['id'], false, $name_map, $fdb);
			$template = $mdb->get_mail_template('attendee-paid');
			$mdb->send_mail($attendee['email-address'], $template, $attendee);
		}
		cm_reg_cart_destroy();

		cm_reg_message(
			'Payment Complete',
			'payment-complete',
			'Your payment has been accepted.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$attendee
		);
	}

	if ($_SESSION['payment_method'] == 'cash') {
		foreach ($attendee_ids as $id) {
			//$atdb->update_payment_status($id, 'Incomplete', 'Cash', $group_uuid, 'Cash');
			$attendee = $atdb->get_attendee($id, false, $name_map, $fdb);
			$template = $mdb->get_mail_template('attendee-paid');
			$mdb->send_mail($attendee['email-address'], $template, $attendee);
		}
		cm_reg_cart_destroy();

		cm_reg_message(
			'Registration Complete',
			'registration-complete',
			'Your registration has been submitted. You will need to pay at the door.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$attendee
		);
	}

	if ($_SESSION['payment_method'] === 'paypal') {
		$paypal = new cm_paypal();
		$token = $paypal->get_token();

        /** @var float $salesTax */
        $salesTax = ($cm_config['payment']['sales_tax'] ?? 0);
        $salesTaxSubTotal = 0;

		$items = array();
		for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
			$item = cm_reg_cart_get($i);
			$badge_type_id = (int)$item['badge-type-id'];
			$badge_type_name = $name_map[$badge_type_id] ?? $badge_type_id;
			if(!(isset($item['editing-badge']) && $item['editing-badge'] > 0 && $item['badge-type-id'] == $item['editing-prior-id']) && $item['payment-promo-price'] > 0 ) {
                $salesTaxPart = $item['sales-tax'] ? ($item['payment-promo-price'] * $salesTax) : 0;
                $salesTaxSubTotal += $salesTaxPart;
				$items[] = $paypal->create_item(
                    $badge_type_name,
                    $item['payment-promo-price'],
                    $salesTaxPart
                );
            }
			if (isset($item['addons']) && $item['addons']) {
				foreach ($item['addons'] as $addon) {
					if($addon['payment-status'] == 'Incomplete') {
                        $salesTaxPart = $addon['sales-tax'] ? ($addon['price'] * $salesTax) : 0;
                        $salesTaxSubTotal += $salesTaxPart;
                        $items[] = $paypal->create_item(
                            $addon['name'],
                            $addon['price'],
                            $salesTaxPart
                        );
                    }
				}
			}
		}
		$total = $paypal->create_total($total_price, $salesTaxSubTotal);
		$txn = $paypal->create_transaction($items, $total,$group_uuid . '::' .$transaction_id);

		$payment = $paypal->create_payment_pp(
			$site_url.'/register/checkout.php?return&gid='.$group_uuid. "&tid=" . $transaction_id,
			$site_url.'/register/checkout.php?cancel&gid='.$group_uuid. "&tid=" . $transaction_id,
			$txn
		);

		$url = $paypal->get_payment_approval_url($payment);
		if (!$url) {
			//Update to explain the error in the badge details
					for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
						$item = cm_reg_cart_get($i);
						$payment['error'] = 'Failed to obtain approval URL';
							$newDetails = transaction_details_update($item['payment-details'],$transaction_id,$payment);
							//echo "New Details: $newDetails";
							//TODO: Revert to possible prior status (i.e. failed upgrade)
							$atdb->update_payment_status($item['id'], 'Rejected', 'PayPal', $transaction_id, $newDetails);
					}



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
		$payment['type'] = 'PayPal';


		for ($i = 0, $n = cm_reg_cart_count(); $i < $n; $i++) {
			$item = cm_reg_cart_get($i);
			$hasNeededAddon = false;
			if (isset($item['addons']) && $item['addons']) {
				foreach ($item['addons'] as $addon) {
					if($addon['payment-status'] == 'Incomplete')
					$hasNeededAddon = true;
				}
			}
			if(!($item['payment-promo-price'] <= 0 && isset($item['editing-badge']) && $item['editing-badge'] > 0 ) || $hasNeededAddon)
			{
				$newDetails = transaction_details_update($item['payment-details'],$transaction_id,$payment);
				//echo "New Details: $newDetails";
				$atdb->update_payment_status($item['id'], 'Incomplete', 'PayPal', $transaction_id, $newDetails);
			}
		}

		$_SESSION['group_uuid'] = $group_uuid;
		$_SESSION['transaction_id'] = $transaction_id;
		$_SESSION['attendee_ids'] = $attendee_ids;
		$_SESSION['paypal_token'] = $token;
		$_SESSION['payment_id'] = $payment['id'];
		cm_reg_cart_set_state('approval');
		header('Location: ' . $url);
		exit(0);
	}
	header('Location: index.php');
	exit(0);
}

if (isset($_GET['return'])) {
	//if (!cm_reg_cart_check_state('approval')) {
	//	header('Location: index.php');
	//	exit(0);
	//}

	$group_uuid = $_GET['gid'] ?? ($_SESSION['group_uuid'] ?? null);
	$transaction_id = $_GET['tid'] ?? ($_SESSION['transaction_id'] ?? null);
	$_SESSION['payment_id'] = $_SESSION['payment_id'] ?? null;

	$token = $_SESSION['paypal_token'] ?? null;
	$paypal = new cm_paypal($token);
	//Ensure we have a token
	$paypal->get_token();

	//check that the payment ID is the same
	$payment_id = $_GET['paymentId'] ?? null;

	//retrieve the badges associated
	$attendee_list = $atdb->list_attendees($group_uuid, NULL);
	$attendee_ids = [];
	$attendeeWithPaymentCompleted = null;
	foreach ($attendee_list as $attendee) {
		//Check that this attendee is the one we're actively targeting
		if(str_contains($attendee['payment-details'], $payment_id) || $attendee['payment-txn-id'] == $transaction_id)
		{
			if($attendee['payment-status'] === 'Incomplete') {
				$attendee_ids[] = $attendee['id'];
			} elseif($attendee['payment-status'] === 'Completed') {
				$attendeeWithPaymentCompleted = new class(
					$attendee['payment-group-uuid'],
					$attendee['payment-txn-id']
				) {
					public function __construct(public string $gid, public string $tid) {}
				};
			}
		}
	}

	if(empty($attendee_ids))
	{
		if ($attendeeWithPaymentCompleted) {
			header("Location: /register/review.php?gid=$attendeeWithPaymentCompleted->gid&tid=$attendeeWithPaymentCompleted->tid");
		}
		die("Error: Unexpectedly retrieved no badges");
	}

	$payer_id = $_GET['PayerID'] ?? null;

	//TODO: Maybe we should verify we got the attendees before executing payment????
	$sale = $paypal->execute_payment($payment_id, $payer_id);
	$ptransaction_id = $paypal->get_transaction_id($sale);
	//$details = transaction_details_update($sale);

	$sale['type'] = 'PayPal';

	if ($ptransaction_id) {
		foreach ($attendee_ids as $id) {
			$pay_status = ''; $pay_type = ''; $pay_txn = ''; $pay_details = '';
			$atdb->get_payment_status($id, $pay_status, $pay_type, $pay_txn, $pay_details);

			$newDetails = transaction_details_update($pay_details,$transaction_id,$sale);
			//Be doubly-shure we got the right person
			if($pay_status == 'Incomplete' && $pay_txn == $transaction_id)
			{
				$atdb->update_payment_status($id, 'Completed', 'PayPal', $transaction_id, $newDetails);
				$attendee = $atdb->get_attendee($id, false, $name_map, $fdb);
				$template = $mdb->get_mail_template('attendee-paid');
				$mdb->send_mail($attendee['email-address'], $template, $attendee);
			}
		}
		cm_reg_cart_destroy();

		cm_reg_message(
			'Payment Complete',
			'payment-complete',
			'Your payment has been accepted.<br><br>'.
			'You can <b><a href="[[review-link]]">review your order</a></b> at any time.',
			$attendee
		);
	} else {
		foreach ($attendee_ids as $id) {
			$pay_status = ''; $pay_type = ''; $pay_txn = ''; $pay_details = '';
			$atdb->get_payment_status($id, $pay_status, $pay_type, $pay_txn, $pay_details);
			$newDetails = transaction_details_update($pay_details,$transaction_id,$sale);

			$atdb->update_payment_status($id, 'Rejected', 'PayPal', $transaction_id, $newDetails);
			$attendee = $atdb->get_attendee($id, false, $name_map, $fdb);
		}
		cm_reg_cart_destroy();

		cm_reg_message(
			'Payment Refused',
			'payment-refused',
			'PayPal has refused this transaction.<br><br>'.
			'PayPal says: [[payment-txn-msg]]<br><br>'.
			'Unfortunately, that is all we know. Please try again later.',
			array_merge($attendee, array('payment-txn-msg' => $sale['message']))
		);
	}
}

if (isset($_GET['cancel'])) {
	if (!cm_reg_cart_check_state('approval')) {
		header('Location: index.php');
		exit(0);
	}

	$group_uuid = $_GET['gid'] ?? $_SESSION['group_uuid'];
	$attendee_ids = $_SESSION['attendee_ids'];

	foreach ($attendee_ids as $id) {
		$atdb->update_payment_status($id, 'Cancelled', 'PayPal', $group_uuid, 'Cancelled');
		$attendee = $atdb->get_attendee($id, false, $name_map, $fdb);
	}
	cm_reg_cart_destroy();

	cm_reg_message(
		'Payment Cancelled',
		'payment-cancelled',
		'You have cancelled your payment.',
		$attendee
	);
}

header('Location: index.php');
