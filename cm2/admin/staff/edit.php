<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/../../lib/database/staff.php';
require_once dirname(__FILE__).'/../../lib/database/forms.php';
require_once dirname(__FILE__).'/../../lib/database/attendee.php';
require_once dirname(__FILE__).'/../../lib/database/mail.php';
require_once dirname(__FILE__).'/../../lib/database/misc.php';
require_once dirname(__FILE__).'/../../lib/util/util.php';
require_once dirname(__FILE__).'/../../lib/util/res.php';
require_once dirname(__FILE__).'/../../lib/util/cmforms.php';
require_once dirname(__FILE__).'/../../lib/util/slack.php';
require_once dirname(__FILE__).'/../admin.php';

cm_admin_check_permission('staff', array('||', 'staff-view', 'staff-review', 'staff-edit'));
$can_edit = $adb->user_has_permission($admin_user, 'staff-edit') && !isset($_GET['ro']);
$can_review = $adb->user_has_permission($admin_user, 'staff-review') && !isset($_GET['ro']);
$review_mode = isset($_GET['review']);
$can_edit_info = $review_mode ? false : $can_edit;
$can_edit_status = $review_mode ? $can_review : $can_edit;
$can_submit = $can_edit_info || $can_edit_status;

$sdb = new cm_staff_db($db);
$name_map = $sdb->get_badge_type_name_map();
$dept_map = $sdb->get_department_map();
$pos_map = $sdb->get_position_map();

$fdb = new cm_forms_db($db, 'staff');
$questions = $fdb->list_questions();

$atdb = new cm_attendee_db($db);
$mdb = new cm_mail_db($db);
$midb = new cm_misc_db($db);
$domain = $midb->getval('mail-default-domain', $_SERVER['SERVER_NAME']);

$new = !isset($_GET['id']);
$id = $new ? -1 : (int)$_GET['id'];
$item = $new ? array() : $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
$submitted = $can_submit && isset($_POST['submit']);
$changed = false;

if ($submitted) {
	if ($can_edit_info) {
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

		/* Payment Information */
		if (
			$new
			|| (        $item['payment-status'     ] !=        $_POST['payment-status'     ] )
			|| ( (float)$item['payment-badge-price'] != (float)$_POST['payment-badge-price'] )
			|| (        $item['payment-type'       ] !=        $_POST['payment-type'       ] )
			|| (        $item['payment-txn-id'     ] !=        $_POST['payment-txn-id'     ] )
			|| ( (float)$item['payment-txn-amt'    ] != (float)$_POST['payment-txn-amt'    ] )
			|| (        $item['payment-details'    ] !=        $_POST['payment-details'    ] )
		) {
			$item['payment-status'] = trim($_POST['payment-status']);
			$item['payment-badge-price'] = (float)$_POST['payment-badge-price'];
			$item['payment-type'] = trim($_POST['payment-type']);
			$item['payment-txn-id'] = trim($_POST['payment-txn-id']);
			$item['payment-txn-amt'] = (float)$_POST['payment-txn-amt'];
			$item['payment-details'] = $_POST['payment-details'];
			$item['payment-group-uuid'] = $db->uuid();
			$item['payment-date'] = $db->now();
		}

		/* Custom Questions */
		$item['form-answers'] = array();
		foreach ($questions as $question) {
			if ($question['active']) {
				$answer = cm_form_posted_answer($question['question-id'], $question['type'],$_POST);
				$item['form-answers'][$question['question-id']] = $answer;
			}
		}
	}

	if ($can_edit_status) {
		/* Application Information */
		$item['application-status'] = trim($_POST['application-status']);
		$item['mail-alias-1'] = trim($_POST['mail-alias-1']);
		$item['mail-alias-2'] = trim($_POST['mail-alias-2']);
		$item['mailbox-type'] = trim($_POST['mailbox-type']);
		$item['notes'] = $_POST['notes'];

		/* Assigned Department */
		$positions = array();
		foreach ($_POST as $k => $v) {
			if (substr($k, 0, 21) === 'assigned-position-id-') {
				$key = substr($k, 21);
				if (isset($positions[$key])) {
					$positions[$key]['position-id'] = (int)$v;
				} else {
					$positions[$key] = array('position-id' => (int)$v);
				}
			}
			if (substr($k, 0, 23) === 'assigned-position-name-') {
				$key = substr($k, 23);
				if (isset($positions[$key])) {
					$positions[$key]['position-name'] = trim($v);
				} else {
					$positions[$key] = array('position-name' => trim($v));
				}
			}
			if (substr($k, 0, 23) === 'assigned-department-id-') {
				$key = substr($k, 23);
				if (isset($positions[$key])) {
					$positions[$key]['department-id'] = (int)$v;
				} else {
					$positions[$key] = array('department-id' => (int)$v);
				}
			}
			if (substr($k, 0, 25) === 'assigned-department-name-') {
				$key = substr($k, 25);
				if (isset($positions[$key])) {
					$positions[$key]['department-name'] = trim($v);
				} else {
					$positions[$key] = array('department-name' => trim($v));
				}
			}
		}
		$item['assigned-positions'] = array_values($positions);
	}

	/* Write Changes */
	if ($new) {
		$id = $sdb->create_staff_member($item, $dept_map, $pos_map, $fdb);
		$new = ($id === false);
		$changed = ($id !== false);
	} else {
		$changed = $sdb->update_staff_member($item, $dept_map, $pos_map, $fdb);
	}
	if ($changed) {
		if ($can_edit_info) {
			if (isset($_POST['print']) && $_POST['print']) $sdb->staff_printed($id, $_POST['print'] === 'reset');
			if (isset($_POST['checkin']) && $_POST['checkin']) $sdb->staff_checked_in($id, $_POST['checkin'] === 'reset');
		}
		$item = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);
		if ($can_edit_info) {
			if (isset($_POST['add-to-staff-blacklist']) && $_POST['add-to-staff-blacklist']) {
				$blacklist_entry = $item;
				$blacklist_entry['added-by'] = trim($_POST['add-to-blacklist-added-by']);
				$blacklist_entry['notes'] = trim($_POST['add-to-blacklist-notes']);
				$sdb->create_blacklist_entry($blacklist_entry);
			}
			if (isset($_POST['add-to-attendee-blacklist']) && $_POST['add-to-attendee-blacklist']) {
				$blacklist_entry = $item;
				$blacklist_entry['added-by'] = trim($_POST['add-to-blacklist-added-by']);
				$blacklist_entry['notes'] = trim($_POST['add-to-blacklist-notes']);
				$atdb->create_blacklist_entry($blacklist_entry);
			}
			if (isset($_POST['resend-payment-email']) && $_POST['resend-payment-email']) {
				$template = $mdb->get_mail_template('staff-paid');
				$mdb->send_mail($item['email-address'], $template, $item);
			}
		}
		if ($can_edit_status) {
			if (isset($_POST['resend-application-email']) && $_POST['resend-application-email']) {
				$application_status = strtolower($item['application-status']);
				$template_name = 'staff-' . $application_status;
				$template = $mdb->get_mail_template($template_name);
				$mdb->send_mail($item['email-address'], $template, $item);

				$slack = new cm_slack();
				if ($slack->get_hook_url($template_name)) {
					$body = 'The staff application for ';
					$body .= $slack->make_link(
						get_site_url(true).'/admin/staff/edit.php?review&id='.$id,
						$item['display-name'].' ('.$item['id-string'].')'
					);
					$body .= ' has been '.$application_status;
					if ($admin_user['name']) {
						$body .= ' by '.$admin_user['name'];
					} else if ($admin_user['username']) {
						$body .= ' by '.$admin_user['username'];
					}
					if (
						$application_status == 'accepted' &&
						isset($item['assigned-positions']) &&
						$item['assigned-positions']
					) {
						if (count($item['assigned-positions']) == 1) {
							$body .= ' for the following position: ';
							$body .= $item['assigned-positions'][0]['position-name-h'];
						} else {
							$body .= ' for the following positions:';
							foreach ($item['assigned-positions'] as $position) {
								$body .= "\n".$position['position-name-h'];
							}
						}
					} else {
						$body .= '.';
					}
					$slack->post_message($template_name, $body);
				}
			}
		}
	}
}

$name = $item['display-name'] ?? null;

cm_admin_head($new ? 'Add Staff Application' : (
	$name
	? (($review_mode ? 'Review Staff Application' : 'Edit Staff Application') . ' - ' . $name)
	: ($review_mode ? 'Review Staff Application' : 'Edit Staff Application')
));

?><style>
	.cm-reg-edit .card-title,
	.cm-reg-edit .card-content {
		position: relative;
	}
	.cm-reg-edit .mode-switch {
		position: absolute;
		top: 7px;
		right: 7px;
	}
	.cm-reg-edit .mode-switch .button {
		margin: 0;
	}
	.cm-reg-edit .card-title .mode-switch {
		display: flex;
		align-items: center;
		top: 0;
		bottom: 0;
	}
	.ea-position-row {
		white-space: nowrap;
	}
	.ea-position-row + .ea-position-row {
		margin-top: 4px;
	}
	.ea-position-row select {
		width: 150px;
		margin-right: 4px;
	}
	.ea-position-row input[type=text] {
		width: 140px;
		margin-right: 4px;
	}
</style><?php

echo '<script type="text/javascript">cm_assigned_positions = ('.json_encode($item['assigned-positions'] ?? array()).');</script>';
echo '<script type="text/javascript" src="edit.js"></script>';

cm_admin_body($new ? 'Add Staff Application' : (
	$review_mode ? 'Review Staff Application' : 'Edit Staff Application'
));

cm_admin_nav('staff');

function echo_mode_switch() {
	global $can_edit, $can_review, $review_mode, $new, $id;
	if ($can_edit && $review_mode && !$new) {
		echo '<div class="mode-switch">';
			echo '<a href="edit.php?id=' . $id . '" class="button">';
				echo 'Switch to Edit Mode';
			echo '</a>';
		echo '</div>';
	}
	if ($can_review && !$review_mode && !$new) {
		echo '<div class="mode-switch">';
			echo '<a href="edit.php?review&id=' . $id . '" class="button">';
				echo 'Switch to Review Mode';
			echo '</a>';
		echo '</div>';
	}
}

echo '<article>';
	if ($can_submit) {
		$url = (
			$review_mode
			? ($new ? 'edit.php?review' : ('edit.php?review&id=' . $id))
			: ($new ? 'edit.php' : ('edit.php?id=' . $id))
		);
		echo '<form action="' . $url . '" method="post" class="card cm-reg-edit">';
	} else {
		echo '<div class="card cm-reg-edit">';
	}
		if ($name) {
			echo '<div class="card-title">';
			echo_mode_switch();
			echo htmlspecialchars($name);
			echo '</div>';
		}
		echo '<div class="card-content">';
			if (!$name) echo_mode_switch();
			if ($can_submit && $submitted) {
				if ($changed) {
					echo '<p class="cm-success-box">Changes saved.</p>';
				} else {
					echo '<p class="cm-error-box">Save failed. Please try again.</p>';
				}
			}
			if (($attendee_blacklisted = $atdb->is_blacklisted($item))) {
				echo '<div class="cm-error-box">';
					echo '<h1>This record matches an entry on the attendee blacklist.</h1>';
					echo '<p>Please contact an executive staff member before proceeding.</p>';
					if ($attendee_blacklisted['added-by']) {
						echo '<p>The point of contact for the matched entry is ';
						echo '<b>' . $attendee_blacklisted['added-by'] . '</b>.</p>';
					}
				echo '</div>';
			}
			if (($staff_blacklisted = $sdb->is_blacklisted($item))) {
				echo '<div class="cm-error-box">';
					echo '<h1>This record matches an entry on the staff blacklist.</h1>';
					echo '<p>Please contact an executive staff member before proceeding.</p>';
					if ($staff_blacklisted['added-by']) {
						echo '<p>The point of contact for the matched entry is ';
						echo '<b>' . $staff_blacklisted['added-by'] . '</b>.</p>';
					}
				echo '</div>';
			}
			if ($item['age'] && $item['age'] < 18) {
				echo '<p class="cm-note-box">This person is under 18.</p>';
			}
			if (($can_submit && $submitted) || $attendee_blacklisted || $staff_blacklisted || ($item['age'] && $item['age'] < 18)) {
				echo '<hr>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';

				echo '<tr><td colspan="2"><h2>Personal Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="first-name">First Name</label></th>';
					$value = isset($item['first-name']) ? htmlspecialchars($item['first-name']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="first-name" name="first-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="last-name">Last Name</label></th>';
					$value = isset($item['last-name']) ? htmlspecialchars($item['last-name']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="last-name" name="last-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="fandom-name">Fandom Name</label></th>';
					$value = isset($item['fandom-name']) ? htmlspecialchars($item['fandom-name']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="fandom-name" name="fandom-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="name-on-badge">Name on Badge</label></th>';
					$value = isset($item['name-on-badge']) ? htmlspecialchars($item['name-on-badge']) : '';
					if ($can_edit_info) {
						echo '<td>';
							echo '<select id="name-on-badge" name="name-on-badge">';
								foreach ($sdb->names_on_badge as $nob) {
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
					if ($can_edit_info) {
						echo '<td><input type="date" id="date-of-birth" name="date-of-birth" value="' . $value . '">';
						if (!ua('Chrome')) echo ' (YYYY-MM-DD)'; echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="badge-type-id">Badge Type</label></th>';
					if ($can_edit_info) {
						$value = isset($item['badge-type-id']) ? htmlspecialchars($item['badge-type-id']) : '';
						echo '<td>';
							echo '<select id="badge-type-id" name="badge-type-id">';
								$badge_types = $sdb->list_badge_types();
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

				if ($can_edit_info && !$new && (!$staff_blacklisted || !$attendee_blacklisted)) {
					echo '<tr class="cm-add-to-blacklist">';
						echo '<th>&nbsp;</th>';
						echo '<td>';
							if (!$staff_blacklisted) echo '<label><input type="checkbox" name="add-to-staff-blacklist" value="1">Add to Staff Blacklist</label>';
							if (!$staff_blacklisted && !$attendee_blacklisted) echo '<br>';
							if (!$attendee_blacklisted) echo '<label><input type="checkbox" name="add-to-attendee-blacklist" value="1">Add to Attendee Blacklist</label>';
						echo '</td>';
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
				echo '<tr><td colspan="2"><h2>Contact Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="email-address">Email Address</label></th>';
					$value = isset($item['email-address']) ? htmlspecialchars($item['email-address']) : '';
					if ($can_edit_info) {
						echo '<td><input type="email" id="email-address" name="email-address" value="' . $value . '"></td>';
					} else {
						echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th>&nbsp;</th>';
					$value = $item['subscribed'] ?? true;
					if ($can_edit_info) {
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
					if ($can_edit_info) {
						echo '<td><input type="text" id="phone-number" name="phone-number" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				if (!$review_mode || $cm_config['review_mode']['show_address']) {

					echo '<tr>';
						echo '<th><label for="address-1">Street Address</label></th>';
						$value = isset($item['address-1']) ? htmlspecialchars($item['address-1']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="address-1" name="address-1" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th>&nbsp;</th>';
						$value = isset($item['address-2']) ? htmlspecialchars($item['address-2']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="address-2" name="address-2" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="city">City</label></th>';
						$value = isset($item['city']) ? htmlspecialchars($item['city']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="city" name="city" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="state">State or Province</label></th>';
						$value = isset($item['state']) ? htmlspecialchars($item['state']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="state" name="state" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="zip-code">ZIP or Postal Code</label></th>';
						$value = isset($item['zip-code']) ? htmlspecialchars($item['zip-code']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="zip-code" name="zip-code" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="country">Country</label></th>';
						$value = isset($item['country']) ? htmlspecialchars($item['country']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="country" name="country" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

				}

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
							echo '<tr><td colspan="2"><h2>Staff Information</h2></td></tr>';
						}
						$answer = (
							isset($item['form-answers']) &&
							isset($item['form-answers'][$question['question-id']]) ?
							$item['form-answers'][$question['question-id']] :
							array()
						);
						echo cm_form_review_row($question, $answer, $can_edit_info);
						$first = false;
					}
				}

				if (!$review_mode || $cm_config['review_mode']['show_ice']) {

					echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
					echo '<tr><td colspan="2"><h2>Emergency Contact Information</h2></td></tr>';

					echo '<tr>';
						echo '<th><label for="ice-name">Emergency Contact Name</label></th>';
						$value = isset($item['ice-name']) ? htmlspecialchars($item['ice-name']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="ice-name" name="ice-name" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="ice-relationship">Emergency Contact Relationship</label></th>';
						$value = isset($item['ice-relationship']) ? htmlspecialchars($item['ice-relationship']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="ice-relationship" name="ice-relationship" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="ice-email-address">Emergency Contact Email Address</label></th>';
						$value = isset($item['ice-email-address']) ? htmlspecialchars($item['ice-email-address']) : '';
						if ($can_edit_info) {
							echo '<td><input type="email" id="ice-email-address" name="ice-email-address" value="' . $value . '"></td>';
						} else {
							echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="ice-phone-number">Emergency Contact Phone Number</label></th>';
						$value = isset($item['ice-phone-number']) ? htmlspecialchars($item['ice-phone-number']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="ice-phone-number" name="ice-phone-number" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Application Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="application-status">Application Status</label></th>';
					$value = isset($item['application-status']) ? htmlspecialchars($item['application-status']) : '';
					if ($can_edit_status) {
						echo '<td>';
							echo '<select id="application-status" name="application-status">';
								foreach ($sdb->application_statuses as $as) {
									$has = htmlspecialchars($as);
									echo '<option value="' . $has;
									echo ($value == $has) ? '" selected>' : '">';
									echo $has . '</option>';
								}
							echo '</select>';
						echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				if ($can_edit_status) {
					echo '<tr>';
						echo '<th>Assigned Position</th>';
						echo '<td id="ea-positions">';
							echo '<div class="ea-position-row">';
								echo 'Loading...';
							echo '</div>';
						echo '</td>';
					echo '</tr>';
					echo '<tr class="hidden">';
						echo '<th>&nbsp;</th>';
						echo '<td id="ea-position-none">';
							echo '<div class="ea-position-row">';
								echo 'None';
							echo '</div>';
						echo '</td>';
					echo '</tr>';
					echo '<tr class="hidden">';
						echo '<th>&nbsp;</th>';
						echo '<td id="ea-position-template">';
							echo '<div class="ea-position-row">';
								echo '<select class="ea-department-id">';
									foreach ($dept_map as $dept) {
										echo (
											'<option value="' . htmlspecialchars($dept['id']) . '">' .
											htmlspecialchars($dept['name']) .
											'</option>'
										);
									}
									echo '<option value="" selected>Other</option>';
								echo '</select>';
								echo '<input type="text" class="ea-department-name">';
								echo '<select class="ea-position-id">';
									foreach ($pos_map as $pos) {
										echo (
											'<option value="' . htmlspecialchars($pos['id']) . '"' .
											' data-parent-id="' . htmlspecialchars($pos['parent-id']) . '">' .
											htmlspecialchars($pos['name']) .
											'</option>'
										);
									}
									echo '<option value="" selected>Other</option>';
								echo '</select>';
								echo '<input type="text" class="ea-position-name">';
								echo '<button class="up-button">&#x2191;</button>';
								echo '<button class="down-button">&#x2193;</button>';
								echo '<button class="delete-button">Delete</button>';
							echo '</div>';
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<th>&nbsp;</th>';
						echo '<td id="ea-position-add">';
							echo '<button class="add-button">Add</button>';
						echo '</td>';
					echo '</tr>';
				} else {
					echo '<tr>';
						echo '<th>Assigned Position</th>';
						echo '<td>';
							if (isset($item['assigned-positions']) && $item['assigned-positions']) {
								foreach ($item['assigned-positions'] as $position) {
									echo '<div>' . htmlspecialchars($position['position-name-h']) . '</div>';
								}
							} else {
								echo '<div>None</div>';
							}
						echo '</td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th><label for="mail-alias-1">Primary Email Alias</label></th>';
					$value = isset($item['mail-alias-1']) ? htmlspecialchars($item['mail-alias-1']) : '';
					if ($can_edit_status) {
						echo '<td>';
							echo '<input type="email" id="mail-alias-1" name="mail-alias-1" value="' . $value . '">';
							echo '<span class="cm-mail-alias-admonishment">(This is for a <b>mail alias</b> at <b>' . htmlspecialchars($domain) . '</b>, <b>NOT</b> an external address.)</span>';
						echo '</td>';
					} else {
						echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="mail-alias-2">Secondary Email Alias</label></th>';
					$value = isset($item['mail-alias-2']) ? htmlspecialchars($item['mail-alias-2']) : '';
					if ($can_edit_status) {
						echo '<td>';
							echo '<input type="email" id="mail-alias-2" name="mail-alias-2" value="' . $value . '">';
							echo '<span class="cm-mail-alias-admonishment">(This is for a <b>mail alias</b> at <b>' . htmlspecialchars($domain) . '</b>, <b>NOT</b> an external address.)</span>';
						echo '</td>';
					} else {
						echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="mailbox-type">Mailbox Type</label></th>';
					$value = isset($item['mailbox-type']) ? htmlspecialchars($item['mailbox-type']) : '';
					if ($can_edit_status) {
						echo '<td>';
							echo '<select id="mailbox-type" name="mailbox-type">';
								if (!$value) $value = 'Forwarding Only';
								foreach ($sdb->mailbox_types as $mxt) {
									$hmxt = htmlspecialchars($mxt);
									echo '<option value="' . $hmxt;
									echo ($value == $hmxt) ? '" selected>' : '">';
									echo $hmxt . '</option>';
								}
							echo '</select>';
						echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="notes">Notes</label></th>';
					if ($can_edit_status) {
						$value = isset($item['notes']) ? htmlspecialchars($item['notes']) : '';
						echo '<td><textarea id="notes" name="notes">' . $value . '</textarea></td>';
					} else {
						$value = isset($item['notes']) ? paragraph_string($item['notes']) : '';
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				if ($can_edit_status) {
					echo '<tr>';
						echo '<th>&nbsp;</th>';
						echo '<td><label><input type="checkbox" name="resend-application-email" value="1">';
						echo (($review_mode || $new) ? 'Send' : 'Resend') . ' Application Status Email';
						echo '</label></td>';
					echo '</tr>';
				}

				if (!$review_mode) {

					echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
					echo '<tr><td colspan="2"><h2>Payment Information</h2></td></tr>';

					echo '<tr>';
						echo '<th><label for="payment-status">Payment Status</label></th>';
						$value = isset($item['payment-status']) ? htmlspecialchars($item['payment-status']) : '';
						if ($can_edit_info) {
							echo '<td>';
								echo '<select id="payment-status" name="payment-status">';
									foreach ($sdb->payment_statuses as $ps) {
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
						if ($can_edit_info) {
							$value = isset($item['payment-badge-price']) ? htmlspecialchars($item['payment-badge-price']) : '';
							echo '<td><input type="number" id="payment-badge-price" name="payment-badge-price" value="' . $value . '" min="0" step="0.01"></td>';
						} else {
							$value = isset($item['payment-badge-price']) ? htmlspecialchars(price_string($item['payment-badge-price'])) : '';
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
						if ($can_edit_info) {
							echo '<td><input type="text" id="payment-type" name="payment-type" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="payment-txn-id">Payment Transaction ID</label></th>';
						$value = isset($item['payment-txn-id']) ? htmlspecialchars($item['payment-txn-id']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="payment-txn-id" name="payment-txn-id" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="payment-txn-amt">Payment Transaction Amount</label></th>';
						if ($can_edit_info) {
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
						if ($can_edit_info) {
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

					if ($can_edit_info) {
						echo '<tr>';
							echo '<th>&nbsp;</th>';
							echo '<td><label><input type="checkbox" name="resend-payment-email" value="1">';
							echo ($new ? 'Send' : 'Resend') . ' Registration Completed Email';
							echo '</label></td>';
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
						if ($can_edit_info) {
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
								if ($can_edit_info) {
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
						if ($can_edit_info) {
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
								if ($can_edit_info) {
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

				}

			echo '</table>';
		echo '</div>';
		if ($can_submit) {
			echo '<div class="card-buttons">';
				echo '<input type="submit" name="submit" value="Save Changes">';
			echo '</div>';
		}
	if ($can_submit) {
		echo '</form>';
	} else {
		echo '</div>';
	}
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
