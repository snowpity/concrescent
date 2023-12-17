<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/../../lib/database/application.php';
require_once dirname(__FILE__).'/../../lib/database/attendee.php';
require_once dirname(__FILE__).'/../../lib/database/forms.php';
require_once dirname(__FILE__).'/../../lib/util/util.php';
require_once dirname(__FILE__).'/../../lib/util/res.php';
require_once dirname(__FILE__).'/../../lib/util/cmlists.php';
require_once dirname(__FILE__).'/../admin.php';

$context = (isset($_GET['c']) ? trim($_GET['c']) : null);
if (!$context) {
	header('Location: ../');
	exit(0);
}
$ctx_lc = strtolower($context);
$ctx_uc = strtoupper($context);
$ctx_info = (
	$cm_config['application_types'][$ctx_uc] ?? null
);
if (!$ctx_info) {
	header('Location: ../');
	exit(0);
}
$ctx_name = $ctx_info['nav_prefix'];
$ctx_name_lc = strtolower($ctx_name);

cm_admin_check_permission('applicants-'.$ctx_lc, array('||',
	'applicants-view-'.$ctx_lc,
	'applicants-edit-'.$ctx_lc
));

$read_only = isset($_GET['ro']);
$can_edit = $adb->user_has_permission($admin_user, 'applicants-edit-'.$ctx_lc) && !$read_only;

$apdb = new cm_application_db($db, $context);
$atdb = new cm_attendee_db($db);

if (isset($_GET['id'])) {
	$new = false;
	$id = (int)$_GET['id'];
	$item = $apdb->get_applicant($id, false, true);
} else if (isset($_GET['pid'])) {
	$new = true;
	$id = (int)$_GET['pid'];
	$item = array(
		'application-id' => $id,
		'application' => $apdb->get_application($id)
	);
	if (!$item['application']) {
		header('Location: ../');
		exit(0);
	}
} else {
	header('Location: ../');
	exit(0);
}
$submitted = $can_edit && isset($_POST['submit']);
$changed = false;

$action_url = 'badge-edit.php?c=' . $ctx_lc;
$action_url .= ($new ? '&pid=' : '&id=') . $id;
if ($read_only) $action_url .= '&ro';

$list_def = array(
	'loader' => 'server-side',
	'ajax-url' => get_site_url(false) . '/admin/application/' . $action_url,
	'entity-type' => 'attendee',
	'entity-type-pl' => 'attendees',
	'search-delay' => 500,
	'max-results' => 5,
	'qr' => 'auto',
	'columns' => array(
		array(
			'name' => 'ID',
			'key' => 'id-string',
			'type' => 'text'
		),
		array(
			'name' => 'Real Name',
			'key' => 'real-name',
			'type' => 'text'
		),
		array(
			'name' => 'Fandom Name',
			'key' => 'fandom-name',
			'type' => 'text'
		),
		array(
			'name' => 'Badge Type',
			'key' => 'badge-type-name',
			'type' => 'text'
		),
		array(
			'name' => 'Email Address',
			'key' => 'email-address',
			'type' => 'email-subbed'
		),
	),
	'sort-order' => array(~0),
	'row-key' => 'id',
	'name-key' => 'display-name',
	'row-actions' => array('select')
);
$list_def['select-function'] = <<<END
	function(id) {
		$('#attendee-id').val('A' + id);
		cmui.hideDialog();
	}
END;

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$time = microtime(true);
			$response = $atdb->cm_ldb->list_indexes($list_def);
			$response['rows'] = array();
			$name_map = $atdb->get_badge_type_name_map();
			$fdb = new cm_forms_db($db, 'attendee');
			foreach ($response['ids'] as $id) {
				$attendee = $atdb->get_attendee($id, false, $name_map, $fdb);
				$response['rows'][] = cm_list_make_row($list_def, $attendee);
			}
			$response['time'] = microtime(true) - $time;
			echo json_encode($response);
			break;
	}
	exit(0);
}

if ($submitted) {
	/* Basic Information */
	$item['first-name'] = trim($_POST['first-name']);
	$item['last-name'] = trim($_POST['last-name']);
	$item['fandom-name'] = trim($_POST['fandom-name']);
	$item['name-on-badge'] = trim($_POST['name-on-badge']);
	$item['date-of-birth'] = parse_date(trim($_POST['date-of-birth']));
	$attendee_id = preg_replace('/[^0-9]+/', '', $_POST['attendee-id']);
	$item['attendee-id'] = $attendee_id ? $attendee_id : null;
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

	/* Write Changes */
	if ($new) {
		$id = $apdb->create_applicant($item);
		$new = ($id === false);
		$changed = ($id !== false);
		if ($changed) {
			$action_url = 'badge-edit.php?c=' . $ctx_lc . '&id=' . $id;
			$list_def['ajax-url'] = get_site_url(false) . '/admin/application/' . $action_url;
		}
	} else {
		$changed = $apdb->update_applicant($item);
	}
	if ($changed) {
		if (isset($_POST['print']) && $_POST['print']) $apdb->applicant_printed($id, $_POST['print'] === 'reset');
		if (isset($_POST['checkin']) && $_POST['checkin']) $apdb->applicant_checked_in($id, $_POST['checkin'] === 'reset');
		$item = $apdb->get_applicant($id, false, true);
		if (isset($_POST['add-to-applicant-blacklist']) && $_POST['add-to-applicant-blacklist']) {
			$blacklist_entry = $item;
			$blacklist_entry['added-by'] = trim($_POST['add-to-blacklist-added-by']);
			$blacklist_entry['notes'] = trim($_POST['add-to-blacklist-notes']);
			$apdb->create_applicant_blacklist_entry($blacklist_entry);
		}
		if (isset($_POST['add-to-attendee-blacklist']) && $_POST['add-to-attendee-blacklist']) {
			$blacklist_entry = $item;
			$blacklist_entry['added-by'] = trim($_POST['add-to-blacklist-added-by']);
			$blacklist_entry['notes'] = trim($_POST['add-to-blacklist-notes']);
			$atdb->create_blacklist_entry($blacklist_entry);
		}
	}
}

$title = ($new ? 'Add ' : 'Edit ') . $ctx_name . ' Badge';
$name = $item['display-name'] ?? null;
$full_title = (!$new && $name) ? ($title . ' - ' . $name) : $title;

cm_admin_head($full_title);

?><style>
	.attendee-select-dialog {
		width: 800px;
		margin-left: -400px;
	}
</style><?php

cm_list_head($list_def);
echo '<script type="text/javascript" src="badge-edit.js"></script>';
cm_admin_body($title);
cm_admin_nav('applicants-' . $ctx_lc);

echo '<article>';
	if ($can_edit) {
		echo '<form action="' . $action_url . '" method="post" class="card cm-reg-edit">';
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
			if (($applicant_blacklisted = $apdb->is_applicant_blacklisted($item))) {
				echo '<div class="cm-error-box">';
					echo '<h1>This record matches an entry on the ' . $ctx_name_lc . ' badge blacklist.</h1>';
					echo '<p>Please contact an executive staff member before proceeding.</p>';
					if ($applicant_blacklisted['added-by']) {
						echo '<p>The point of contact for the matched entry is ';
						echo '<b>' . $applicant_blacklisted['added-by'] . '</b>.</p>';
					}
				echo '</div>';
			}
			if ($item['age'] && $item['age'] < 18) {
				echo '<p class="cm-note-box">This person is under 18.</p>';
			}
			if (($can_edit && $submitted) || $attendee_blacklisted || $applicant_blacklisted || ($item['age'] && $item['age'] < 18)) {
				echo '<hr>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';

				echo '<tr><td colspan="2"><h2>Personal Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label>Application ID</label></th>';
					echo '<td>';
						echo htmlspecialchars($item['application']['id-string']);
						echo ' &mdash; ';
						echo htmlspecialchars($item['application']['application-name']);
					echo '</td>';
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="attendee-id">Attendee ID</label></th>';
					$value = (isset($item['attendee-id']) && $item['attendee-id']) ? ('A' . $item['attendee-id']) : '';
					if ($can_edit) {
						echo '<td>';
							echo '<input type="text" id="attendee-id" name="attendee-id" value="' . $value . '" readonly>';
							echo '&nbsp;&nbsp;';
							echo '<button class="attendee-select-button">Select</button>';
							echo '<button class="attendee-clear-button">Clear</button>';
						echo '</td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

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

				if ($can_edit && !$new && (!$applicant_blacklisted || !$attendee_blacklisted)) {
					echo '<tr class="cm-add-to-blacklist">';
						echo '<th>&nbsp;</th>';
						echo '<td>';
							if (!$applicant_blacklisted) echo '<label><input type="checkbox" name="add-to-applicant-blacklist" value="1">Add to Badge Blacklist</label>';
							if (!$applicant_blacklisted && !$attendee_blacklisted) echo '<br>';
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
				echo '<input type="submit" name="submit" value="Save Changes">';
			echo '</div>';
		}
	if ($can_edit) {
		echo '</form>';
	} else {
		echo '</div>';
	}
echo '</article>';

cm_admin_dialogs();

echo '<div class="dialog attendee-select-dialog hidden">';
	echo '<div class="dialog-title">Select Attendee</div>';
	echo '<div class="dialog-content">';
		cm_list_search_box($list_def);
		cm_list_table($list_def);
	echo '</div>';
	echo '<div class="dialog-buttons">';
		echo '<button class="cancel-select-button">Cancel</button>';
	echo '</div>';
echo '</div>';

cm_list_dialogs($list_def);
cm_admin_tail();
