<?php

require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../../lib/database/misc.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/res.php';
require_once __DIR__ .'/../../lib/util/cmforms.php';
require_once __DIR__ .'/../admin.php';
require_once __DIR__ .'/../../../vendor/autoload.php';

global $log;

cm_admin_check_permission('attendees', array('||', 'attendees-view', 'attendees-edit'));
$can_edit = $adb->user_has_permission($admin_user, 'attendees-edit') && !isset($_GET['ro']);

$atdb = new cm_attendee_db($db);
$name_map = $atdb->get_badge_type_name_map();
$all_addons = $atdb->list_addons(false, false, false, $name_map);

$fdb = new cm_forms_db($db, 'attendee');
$questions = $fdb->list_questions();

$miscDb = new cm_misc_db($db);
$taskSponsorPublishable = new \App\Task\SponsorPublishableTask(
    $miscDb,
    new \App\Hook\CloudflareApi(
        $log
    ),
    $log,
);

$new = !isset($_GET['id']);
$id = $new ? -1 : (int)$_GET['id'];
$item = $new ? [
	'age' => 0,
	'addon-ids' => [],
] : $atdb->get_attendee($id, false, $name_map, $fdb);
$submitted = $can_edit && isset($_POST['submit']);
$changed = false;
$errorMessage = '';

if ($submitted) {
	/* Basic Information */
	$item['first-name'] = trim($_POST['first-name']);
	$item['last-name'] = trim($_POST['last-name']);
	$item['fandom-name'] = trim($_POST['fandom-name']);
	$item['name-on-badge'] = trim($_POST['name-on-badge']);
	$item['date-of-birth'] = parse_date(trim($_POST['date-of-birth']));
	$item['badge-type-id'] = (int)$_POST['badge-type-id'];
	$item['email-address'] = trim($_POST['email-address']);
	$item['subscribed'] = isset($_POST['subscribed']) && $_POST['subscribed'];
	$item['phone-number'] = trim($_POST['phone-number']);
	$item['address-1'] = trim($_POST['address-1']);
	$item['address-2'] = trim($_POST['address-2']);
	$item['city'] = trim($_POST['city']);
	$item['state'] = trim($_POST['state']);
	$item['zip-code'] = trim($_POST['zip-code']);
	$item['country'] = trim($_POST['country']);
	$item['ice-name'] = trim($_POST['ice-name']);
	$item['ice-relationship'] = trim($_POST['ice-relationship']);
	$item['ice-email-address'] = trim($_POST['ice-email-address']);
	$item['ice-phone-number'] = trim($_POST['ice-phone-number']);
	$item['notes'] = $_POST['notes'];

	/* Payment Information */
	if (
		$new
		|| (        $item['payment-status'     ] !=        $_POST['payment-status'     ] )
		|| ( (float)$item['payment-badge-price'] != (float)$_POST['payment-badge-price'] )
		|| (        $item['payment-promo-code' ] !=        $_POST['payment-promo-code' ] )
		|| ( (float)$item['payment-promo-price'] != (float)$_POST['payment-promo-price'] )
		|| (        $item['payment-type'       ] !=        $_POST['payment-type'       ] )
		|| (        $item['payment-txn-id'     ] !=        $_POST['payment-txn-id'     ] )
		|| ( (float)$item['payment-txn-amt'    ] != (float)$_POST['payment-txn-amt'    ] )
		|| (        $item['payment-details'    ] !=        $_POST['payment-details'    ] )
	) {
        $item['payment-group-uuid'] = $db->uuid();
        $item['payment-date'] = $db->now();
		$item['payment-status'] = trim($_POST['payment-status']);
		$item['payment-badge-price'] = (float)$_POST['payment-badge-price'];
		$item['payment-promo-code'] = trim($_POST['payment-promo-code']);
		$item['payment-promo-price'] = (float)$_POST['payment-promo-price'];
		$item['payment-type'] = trim($_POST['payment-type']);
		$item['payment-txn-id'] = trim($_POST['payment-txn-id']) ?: $item['payment-group-uuid'];
		$item['payment-txn-amt'] = (float)$_POST['payment-txn-amt'];
		$item['payment-details'] = $_POST['payment-details'];
	}

	/* Addons */
	$new_addons = array();
	foreach ($all_addons as $addon) {
		$k = 'addon-' . $addon['id'];
		if (isset($_POST[$k]) && $_POST[$k]) {
			$payment_info = array();
			if (isset($item['addons']) && $item['addons']) {
				foreach ($item['addons'] as $pi) {
					if ($pi['addon-id'] == $addon['id']) {
						$payment_info = $pi;
					}
				}
			}
			if (
				!$payment_info
				|| (        $payment_info['payment-status' ] !=        $_POST[$k.'-payment-status' ] )
				|| ( (float)$payment_info['payment-price'  ] != (float)$_POST[$k.'-payment-price'  ] )
				|| (        $payment_info['payment-type'   ] !=        $_POST[$k.'-payment-type'   ] )
				|| (        $payment_info['payment-txn-id' ] !=        $_POST[$k.'-payment-txn-id' ] )
				|| ( (float)$payment_info['payment-txn-amt'] != (float)$_POST[$k.'-payment-txn-amt'] )
				|| (        $payment_info['payment-details'] !=        $_POST[$k.'-payment-details'] )
			) {
				$payment_info['payment-status'] = trim($_POST[$k.'-payment-status']);
				$payment_info['payment-price'] = (float)$_POST[$k.'-payment-price'];
				$payment_info['payment-type'] = trim($_POST[$k.'-payment-type']);
				$payment_info['payment-txn-id'] = trim($_POST[$k.'-payment-txn-id']);
				$payment_info['payment-txn-amt'] = (float)$_POST[$k.'-payment-txn-amt'];
				$payment_info['payment-details'] = $_POST[$k.'-payment-details'];
				$payment_info['payment-date'] = $db->now();
			}
			$payment_info['attendee-id'] = $id;
			$payment_info['addon-id'] = $addon['id'];
			$new_addons[] = $payment_info;
		}
	}
	$item['addons'] = $new_addons;

	/* Custom Questions */
	$item['form-answers'] = array();
	foreach ($questions as $question) {
		if ($question['active']) {
			$answer = cm_form_posted_answer($question['question-id'], $question['type'],$_POST);
			$item['form-answers'][$question['question-id']] = $answer;
		}
	}

	/* Write Changes */
	if ($new) {
		try {
			$id = $atdb->create_attendee($item, $fdb);
			$new = ($id === false);
			$changed = ($id !== false);
		} catch (PDOException|InvalidArgumentException $e) {
			$errorMessage = $e->getMessage();
		}
	} else {
		$changed = $atdb->update_attendee($item, $fdb);
	}
	if ($changed) {
		if (isset($_POST['print']) && $_POST['print']) $atdb->attendee_printed($id, $_POST['print'] === 'reset');
		if (isset($_POST['checkin']) && $_POST['checkin']) $atdb->attendee_checked_in($id, $_POST['checkin'] === 'reset');
		$item = $atdb->get_attendee($id, false, $name_map, $fdb);
		if (isset($_POST['add-to-blacklist']) && $_POST['add-to-blacklist']) {
			$blacklist_entry = $item;
			$blacklist_entry['added-by'] = trim($_POST['add-to-blacklist-added-by']);
			$blacklist_entry['notes'] = trim($_POST['add-to-blacklist-notes']);
			$atdb->create_blacklist_entry($blacklist_entry);
		}
		if (isset($_POST['resend-email']) && $_POST['resend-email']) {
			$mdb = new cm_mail_db($db);
			$template = $mdb->get_mail_template('attendee-paid');
			$mdb->send_mail($item['email-address'], $template, $item);
		}

        $taskSponsorPublishable->onAttendeeManualUpdate();
	}
}

$name = $item['display-name'] ?? null;
cm_admin_head($new ? 'Add Attendee' : ($name ? ('Edit Attendee - ' . $name) : 'Edit Attendee'));
echo '<script type="text/javascript" src="edit.js"></script>';
cm_admin_body($new ? 'Add Attendee' : 'Edit Attendee');
cm_admin_nav('attendees');

echo '<article>';
	if ($can_edit) {
		$url = $new ? 'edit.php' : ('edit.php?id=' . $id);
		echo '<form action="' . $url . '" method="post" class="card cm-reg-edit">';
	} else {
		echo '<div class="card cm-reg-edit">';
	}
		if ($name) {
			echo '<div class="card-title">' . htmlspecialchars($name) . '</div>';
		}
		echo '<div class="card-content">';
			if ($can_edit && $submitted) {
				if ($changed) {
					echo '<p class="cm-success-box">Changes saved.</p>';
				} else {
					echo "<p class=\"cm-error-box\">Save failed. Please try again. $errorMessage</p>";
				}
			}
			if (($blacklisted = $atdb->is_blacklisted($item))) {
				echo '<div class="cm-error-box">';
					echo '<h1>This record matches an entry on the attendee blacklist.</h1>';
					echo '<p>Please contact an executive staff member before proceeding.</p>';
					if ($blacklisted['added-by']) {
						echo '<p>The point of contact for the matched entry is ';
						echo '<b>' . $blacklisted['added-by'] . '</b>.</p>';
					}
				echo '</div>';
			}
			if ($item['age'] && $item['age'] < 18) {
				echo '<p class="cm-note-box">This person is under 18.</p>';
			}
			if (($can_edit && $submitted) || $blacklisted || ($item['age'] && $item['age'] < 18)) {
				echo '<hr>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';

				echo '<tr><td colspan="2"><h2>Personal Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="first-name">First Name</label></th>';
					$value = isset($item['first-name']) ? htmlspecialchars($item['first-name']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="first-name" name="first-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="last-name">Last Name</label></th>';
					$value = isset($item['last-name']) ? htmlspecialchars($item['last-name']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="last-name" name="last-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="fandom-name">Fandom Name</label></th>';
					$value = isset($item['fandom-name']) ? htmlspecialchars($item['fandom-name']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="fandom-name" name="fandom-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="name-on-badge">Name on Badge</label></th>';
					$value = isset($item['name-on-badge']) ? htmlspecialchars($item['name-on-badge']) : '';
					if ($can_edit) {
						echo '<td>';
							echo '<select id="name-on-badge" name="name-on-badge">';
								foreach ($atdb->names_on_badge as $nob) {
									$hnob = htmlspecialchars($nob);
									echo '<option value="' . $hnob;
									echo ($value == $hnob) ? '" selected>' : '">';
									echo $hnob . '</option>';
								}
							echo '</select>';
						echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="date-of-birth">Date of Birth</label></th>';
					$value = isset($item['date-of-birth']) ? htmlspecialchars($item['date-of-birth']) : '';
					if ($can_edit) {
						echo '<td><input type="date" id="date-of-birth" name="date-of-birth" value="' . $value . '">';
						if (!ua('Chrome')) echo ' (YYYY-MM-DD)'; echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="badge-type-id">Badge Type</label></th>';
					if ($can_edit) {
						$value = isset($item['badge-type-id']) ? htmlspecialchars($item['badge-type-id']) : '';
						echo '<td>';
							echo '<select id="badge-type-id" name="badge-type-id">';
								$badge_types = $atdb->list_badge_types();
								foreach ($badge_types as $bt) {
									$btid = htmlspecialchars($bt['id']);
									$btname = htmlspecialchars($bt['name']);
									$btprice = htmlspecialchars(price_string($bt['price']));
									echo '<option value="' . $btid;
									echo ($value == $btid) ? '" selected>' : '">';
									echo $btname . ' &mdash; ' . $btprice . '</option>';
								}
							echo '</select>';
						echo '</td>';
					} else {
						$value = isset($item['badge-type-name']) ? htmlspecialchars($item['badge-type-name']) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				if ($can_edit && !$new && !$blacklisted) {
					echo '<tr class="cm-add-to-blacklist">';
						echo '<th>&nbsp;</th>';
						echo '<td><label><input type="checkbox" name="add-to-blacklist" value="1">Add to Blacklist</label></td>';
					echo '</tr>';
					echo '<tr class="cm-add-to-blacklist-fields hidden">';
						echo '<th>Added/Approved By</th>';
						echo '<td><input type="text" id="add-to-blacklist-added-by" name="add-to-blacklist-added-by"></td>';
					echo '</tr>';
					echo '<tr class="cm-add-to-blacklist-fields hidden">';
						echo '<th>Notes</th>';
						echo '<td><textarea id="add-to-blacklist-notes" name="add-to-blacklist-notes"></textarea></td>';
					echo '</tr>';
				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Choose Your Addons</h2></td></tr>';
				foreach ($all_addons as $addon) {
					$checked = in_array($addon['id'], $item['addon-ids']);
					echo '<tr><td colspan="2"><div class="cm-reg-addon" id="cm-reg-addon-' . $addon['id'] . '">';
					echo (($can_edit || $checked) ? '<div>' : '<div class="disabled">') . '<label>';
					echo '<input type="checkbox" id="addon-' . $addon['id'] . '" name="addon-' . $addon['id'] . '" value="1"';
					if ($checked) echo ' checked'; if (!$can_edit) echo ' disabled'; echo '>';
					echo htmlspecialchars($addon['name']) . ' &mdash; ' . htmlspecialchars(price_string($addon['price']));
					echo '</label></div>';
					echo '</div></td></tr>';

					$trtag = '<tr class="addon-' . $addon['id'] . '-details' . ($checked ? '">' : ' hidden">');
					$payment_info = array();
					if ($checked) {
						foreach ($item['addons'] as $pi) {
							if ($pi['addon-id'] == $addon['id']) {
								$payment_info = $pi;
							}
						}
					}

					echo $trtag;
						echo '<th><label for="addon-' . $addon['id'] . '-payment-status">Payment Status</label></th>';
						$value = isset($payment_info['payment-status']) ? htmlspecialchars($payment_info['payment-status']) : '';
						if ($can_edit) {
							echo '<td>';
								echo '<select id="addon-' . $addon['id'] . '-payment-status" name="addon-' . $addon['id'] . '-payment-status">';
									foreach ($atdb->payment_statuses as $ps) {
										$hps = htmlspecialchars($ps);
										echo '<option value="' . $hps;
										echo ($value == $hps) ? '" selected>' : '">';
										echo $hps . '</option>';
									}
								echo '</select>';
							echo '</td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo $trtag;
						echo '<th><label for="addon-' . $addon['id'] . '-payment-price">Payment Price</label></th>';
						if ($can_edit) {
							$value = isset($payment_info['payment-price']) ? htmlspecialchars($payment_info['payment-price']) : '';
							echo '<td><input type="number" id="addon-' . $addon['id'] . '-payment-price" name="addon-' . $addon['id'] . '-payment-price" value="' . $value . '" min="0" step="0.01"></td>';
						} else {
							$value = isset($payment_info['payment-price']) ? htmlspecialchars(price_string($payment_info['payment-price'])) : '';
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo $trtag;
						echo '<th><label for="addon-' . $addon['id'] . '-payment-type">Payment Type</label></th>';
						$value = isset($payment_info['payment-type']) ? htmlspecialchars($payment_info['payment-type']) : '';
						if ($can_edit) {
							echo '<td><input type="text" id="addon-' . $addon['id'] . '-payment-type" name="addon-' . $addon['id'] . '-payment-type" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo $trtag;
						echo '<th><label for="addon-' . $addon['id'] . '-payment-txn-id">Payment Transaction ID</label></th>';
						$value = isset($payment_info['payment-txn-id']) ? htmlspecialchars($payment_info['payment-txn-id']) : '';
						if ($can_edit) {
							echo '<td><input type="text" id="addon-' . $addon['id'] . '-payment-txn-id" name="addon-' . $addon['id'] . '-payment-txn-id" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo $trtag;
						echo '<th><label for="addon-' . $addon['id'] . '-payment-txn-amt">Payment Transaction Amount</label></th>';
						if ($can_edit) {
							$value = isset($payment_info['payment-txn-amt']) ? htmlspecialchars($payment_info['payment-txn-amt']) : '';
							echo '<td><input type="number" id="addon-' . $addon['id'] . '-payment-txn-amt" name="addon-' . $addon['id'] . '-payment-txn-amt" value="' . $value . '" min="0" step="0.01"></td>';
						} else {
							$value = isset($payment_info['payment-txn-amt']) ? htmlspecialchars(price_string($payment_info['payment-txn-amt'])) : '';
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					$value = isset($payment_info['payment-date']) ? htmlspecialchars($payment_info['payment-date']) : '';
					if ($value) {
						echo $trtag;
							echo '<th><label>Payment Date</label></th>';
							echo '<td>' . $value . '</td>';
						echo '</tr>';
					}

					echo $trtag;
						echo '<th><label for="addon-' . $addon['id'] . '-payment-details">Payment Details</label></th>';
						if ($can_edit) {
							$value = isset($payment_info['payment-details']) ? htmlspecialchars($payment_info['payment-details']) : '';
							echo '<td><textarea id="addon-' . $addon['id'] . '-payment-details" name="addon-' . $addon['id'] . '-payment-details">' . $value . '</textarea></td>';
						} else {
							$value = isset($payment_info['payment-details']) ? paragraph_string($payment_info['payment-details']) : '';
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';
				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Contact Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="email-address">Email Address</label></th>';
					$value = isset($item['email-address']) ? htmlspecialchars($item['email-address']) : '';
					if ($can_edit) {
						echo '<td><input type="email" id="email-address" name="email-address" value="' . $value . '"></td>';
					} else {
						echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th>&nbsp;</th>';
					$value = $item['subscribed'] ?? true;
					if ($can_edit) {
						echo '<td><label>';
							echo '<input type="checkbox" name="subscribed" value="1"' . ($value ? ' checked>' : '>');
							echo 'You may contact me with promotional emails.';
						echo '</label></td>';
					} else {
						echo '<td>' . ($value ? 'You may contact me with promotional emails.' : 'You <b>MAY NOT</b> contact me with promotional emails.') . '</td>';
					}
				echo '</tr>';

				$value = isset($item['unsubscribe-link']) ? htmlspecialchars($item['unsubscribe-link']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Unsubscribe Link</label></th>';
						echo '<td><a href="' . $value . '" target="_blank">' . $value . '</a></td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th><label for="phone-number">Phone Number</label></th>';
					$value = isset($item['phone-number']) ? htmlspecialchars($item['phone-number']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="phone-number" name="phone-number" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="address-1">Street Address</label></th>';
					$value = isset($item['address-1']) ? htmlspecialchars($item['address-1']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="address-1" name="address-1" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th>&nbsp;</th>';
					$value = isset($item['address-2']) ? htmlspecialchars($item['address-2']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="address-2" name="address-2" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="city">City</label></th>';
					$value = isset($item['city']) ? htmlspecialchars($item['city']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="city" name="city" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="state">State or Province</label></th>';
					$value = isset($item['state']) ? htmlspecialchars($item['state']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="state" name="state" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="zip-code">ZIP or Postal Code</label></th>';
					$value = isset($item['zip-code']) ? htmlspecialchars($item['zip-code']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="zip-code" name="zip-code" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="country">Country</label></th>';
					$value = isset($item['country']) ? htmlspecialchars($item['country']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="country" name="country" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				$first = true;
				function my_question_is_visible($question) {
					switch ($question['type']) {
						case 'h1': case 'h2': case 'h3':
						case 'p': case 'q':
							return $question['active'] && $question['title'];
						default:
							return $question['active'];
					}
				}
				foreach ($questions as $question) {
					if (my_question_is_visible($question)) {
						if ($first) {
							echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
							echo '<tr><td colspan="2"><h2>Additional Information</h2></td></tr>';
						}
						$answer = (
							isset($item['form-answers']) &&
							isset($item['form-answers'][$question['question-id']]) ?
							$item['form-answers'][$question['question-id']] :
							array()
						);
						echo cm_form_review_row($question, $answer, $can_edit);
						$first = false;
					}
				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Emergency Contact Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="ice-name">Emergency Contact Name</label></th>';
					$value = isset($item['ice-name']) ? htmlspecialchars($item['ice-name']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="ice-name" name="ice-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="ice-relationship">Emergency Contact Relationship</label></th>';
					$value = isset($item['ice-relationship']) ? htmlspecialchars($item['ice-relationship']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="ice-relationship" name="ice-relationship" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="ice-email-address">Emergency Contact Email Address</label></th>';
					$value = isset($item['ice-email-address']) ? htmlspecialchars($item['ice-email-address']) : '';
					if ($can_edit) {
						echo '<td><input type="email" id="ice-email-address" name="ice-email-address" value="' . $value . '"></td>';
					} else {
						echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="ice-phone-number">Emergency Contact Phone Number</label></th>';
					$value = isset($item['ice-phone-number']) ? htmlspecialchars($item['ice-phone-number']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="ice-phone-number" name="ice-phone-number" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Payment Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="payment-status">Payment Status</label></th>';
					$value = isset($item['payment-status']) ? htmlspecialchars($item['payment-status']) : '';
					if ($can_edit) {
						echo '<td>';
							echo '<select id="payment-status" name="payment-status">';
								foreach ($atdb->payment_statuses as $ps) {
									$hps = htmlspecialchars($ps);
									echo '<option value="' . $hps;
									echo ($value == $hps) ? '" selected>' : '">';
									echo $hps . '</option>';
								}
							echo '</select>';
						echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-badge-price">Payment Badge Price</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-badge-price']) ? htmlspecialchars($item['payment-badge-price']) : '';
						echo '<td><input type="number" id="payment-badge-price" name="payment-badge-price" value="' . $value . '" min="0" step="0.01"></td>';
					} else {
						$value = isset($item['payment-badge-price']) ? htmlspecialchars(price_string($item['payment-badge-price'])) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-promo-code">Payment Promo Code</label></th>';
					$value = isset($item['payment-promo-code']) ? htmlspecialchars($item['payment-promo-code']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="payment-promo-code" name="payment-promo-code" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-promo-price">Payment Promo Price</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-promo-price']) ? htmlspecialchars($item['payment-promo-price']) : '';
						echo '<td><input type="number" id="payment-promo-price" name="payment-promo-price" value="' . $value . '" min="0" step="0.01"></td>';
					} else {
						$value = isset($item['payment-promo-price']) ? htmlspecialchars(price_string($item['payment-promo-price'])) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				$value = isset($item['payment-group-uuid']) ? htmlspecialchars($item['payment-group-uuid']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Payment Group UUID</label></th>';
						echo '<td><tt>' . $value . '</tt></td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th><label for="payment-type">Payment Type</label></th>';
					$value = isset($item['payment-type']) ? htmlspecialchars($item['payment-type']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="payment-type" name="payment-type" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-txn-id">Payment Transaction ID</label></th>';
					$value = isset($item['payment-txn-id']) ? htmlspecialchars($item['payment-txn-id']) : '';
					if ($can_edit) {
						echo '<td><input type="text" id="payment-txn-id" name="payment-txn-id" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="payment-txn-amt">Payment Transaction Amount</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-txn-amt']) ? htmlspecialchars($item['payment-txn-amt']) : '';
						echo '<td><input type="number" id="payment-txn-amt" name="payment-txn-amt" value="' . $value . '" min="0" step="0.01"></td>';
					} else {
						$value = isset($item['payment-txn-amt']) ? htmlspecialchars(price_string($item['payment-txn-amt'])) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				$value = isset($item['payment-date']) ? htmlspecialchars($item['payment-date']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Payment Date</label></th>';
						echo '<td>' . $value . '</td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th><label for="payment-details">Payment Details</label></th>';
					if ($can_edit) {
						$value = isset($item['payment-details']) ? htmlspecialchars($item['payment-details']) : '';
						echo '<td><textarea id="payment-details" name="payment-details">' . $value . '</textarea></td>';
					} else {
						$value = isset($item['payment-details']) ? paragraph_string($item['payment-details']) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				$value = isset($item['review-link']) ? htmlspecialchars($item['review-link']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Review Order Link</label></th>';
						echo '<td><a href="' . $value . '" target="_blank">' . $value . '</a></td>';
					echo '</tr>';
				}

				if ($can_edit) {
					echo '<tr>';
						echo '<th>&nbsp;</th>';
						echo '<td><label><input type="checkbox" name="resend-email" value="1">';
						echo ($new ? 'Send' : 'Resend') . ' Registration Completed Email';
						echo '</label></td>';
					echo '</tr>';
				}

				if (!$new && $adb->user_has_permission($admin_user, 'attendees-refund') ) {
					echo '<tr>';
						echo '<th>Refund</th>';
						echo '<td><a href="refund.php?id=' . $item['id'] . '" >Initiate refund</a></td>';
					echo '</tr>';
				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Record Information</h2></td></tr>';

				$value = isset($item['id-string']) ? htmlspecialchars($item['id-string']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>ID Number</label></th>';
						echo '<td>' . $value . '</td>';
					echo '</tr>';
				}

				$value = isset($item['uuid']) ? htmlspecialchars($item['uuid']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>UUID</label></th>';
						echo '<td><tt>' . $value . '</tt></td>';
					echo '</tr>';
				}

				$value = isset($item['qr-data']) ? htmlspecialchars($item['qr-data']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>QR Code</label></th>';
						$qr_url = htmlspecialchars(resource_file_url('barcode.php', false) . '?s=qr&w=150&h=150&d=');
						echo '<td><img src="' . $qr_url . $value . '" width="150" height="150"></td>';
					echo '</tr>';
				}

				$value = isset($item['date-created']) ? htmlspecialchars($item['date-created']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Date Created</label></th>';
						echo '<td>' . $value . '</td>';
					echo '</tr>';
				}

				$value = isset($item['date-modified']) ? htmlspecialchars($item['date-modified']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Date Modified</label></th>';
						echo '<td>' . $value . '</td>';
					echo '</tr>';
				}

				if ($new) {
					if ($can_edit) {
						echo '<tr>';
							echo '<th>&nbsp;</th>';
							echo '<td><label><input type="checkbox" name="print" value="1">Mark Printed</label></td>';
						echo '</tr>';
					}
				} else {
					$count = isset($item['print-count']) ? htmlspecialchars($item['print-count']) : '';
					$first = isset($item['print-first-time']) ? htmlspecialchars($item['print-first-time']) : '';
					$last = isset($item['print-last-time']) ? htmlspecialchars($item['print-last-time']) : '';
					echo '<tr>';
						echo '<th><label>Printed</label></th>';
						echo '<td>';
							if ($count) {
								echo $count . (($count == 1) ? ' time' : ' times');
								echo '&nbsp;&nbsp;&nbsp;&nbsp;(First: ' . $first . ')';
								echo '&nbsp;&nbsp;&nbsp;&nbsp;(Last: ' . $last . ')';
							} else {
								echo 'never';
							}
							if ($can_edit) {
								echo '<br>';
								echo '<label><input type="radio" name="print" value="" checked>Keep</label>';
								echo '&nbsp;&nbsp;';
								echo '<label><input type="radio" name="print" value="1">Mark</label>';
								echo '&nbsp;&nbsp;';
								echo '<label><input type="radio" name="print" value="reset">Reset</label>';
							}
						echo '</td>';
					echo '</tr>';
				}

				if ($new) {
					if ($can_edit) {
						echo '<tr>';
							echo '<th>&nbsp;</th>';
							echo '<td><label><input type="checkbox" name="checkin" value="1">Mark Checked In</label></td>';
						echo '</tr>';
					}
				} else {
					$count = isset($item['checkin-count']) ? htmlspecialchars($item['checkin-count']) : '';
					$first = isset($item['checkin-first-time']) ? htmlspecialchars($item['checkin-first-time']) : '';
					$last = isset($item['checkin-last-time']) ? htmlspecialchars($item['checkin-last-time']) : '';
					echo '<tr>';
						echo '<th><label>Checked In</label></th>';
						echo '<td>';
							if ($count) {
								echo $count . (($count == 1) ? ' time' : ' times');
								echo '&nbsp;&nbsp;&nbsp;&nbsp;(First: ' . $first . ')';
								echo '&nbsp;&nbsp;&nbsp;&nbsp;(Last: ' . $last . ')';
							} else {
								echo 'never';
							}
							if ($can_edit) {
								echo '<br>';
								echo '<label><input type="radio" name="checkin" value="" checked>Keep</label>';
								echo '&nbsp;&nbsp;';
								echo '<label><input type="radio" name="checkin" value="1">Mark</label>';
								echo '&nbsp;&nbsp;';
								echo '<label><input type="radio" name="checkin" value="reset">Reset</label>';
							}
						echo '</td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th><label for="notes">Notes</label></th>';
					if ($can_edit) {
						$value = isset($item['notes']) ? htmlspecialchars($item['notes']) : '';
						echo '<td><textarea id="notes" name="notes">' . $value . '</textarea></td>';
					} else {
						$value = isset($item['notes']) ? paragraph_string($item['notes']) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

			echo '</table>';
		echo '</div>';
		if ($can_edit) {
			echo '<div class="card-buttons">';
				echo '<input type="submit" name="submit" value="'.($new ? 'Create new' : 'Save Changes').'">';
			echo '</div>';
		}
	if ($can_edit) {
		echo '</form>';
	} else {
		echo '</div>';
	}
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
