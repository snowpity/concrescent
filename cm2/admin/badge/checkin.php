<?php

require_once __DIR__ .'/../../lib/database/badge-artwork.php';
require_once __DIR__ .'/../../lib/database/badge-holder.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('badge-checkin', 'badge-checkin');

$badb = new cm_badge_artwork_db($db);
$bhdb = new cm_badge_holder_db($db);

$columns = array(
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
	array(
		'name' => 'Application Status',
		'key' => 'application-status',
		'type' => 'status-label'
	),
	array(
		'name' => 'Payment Status',
		'key' => 'payment-status',
		'type' => 'status-label'
	),
	array(
		'name' => 'P',
		'key' => 'print-count',
		'type' => 'numeric'
	),
	array(
		'name' => 'C',
		'key' => 'checkin-count',
		'type' => 'numeric'
	),
);
$list_def = array(
	'loader' => 'server-side',
	'ajax-url' => get_site_url(false) . '/admin/badge/checkin.php',
	'entity-type' => 'badge holder',
	'entity-type-pl' => 'badge holders',
	'search-criteria' => 'name, badge type, contact info, or transaction ID',
	'search-delay' => 500,
	'max-results' => 5,
	'qr' => 'auto',
	'columns' => $columns,
	'sort-order' => array(~0),
	'row-key' => 'badge-holder-id-string',
	'name-key' => 'display-name',
	'row-actions' => array('select'),
	'table-actions' => array('add'),
	'add-label' => 'New Attendee',
	'select-label' => 'Start Check-In'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$time = microtime(true);
			$response = $bhdb->list_indexes($list_def);
			$response['rows'] = array();
			foreach ($response['ids'] as $id) {
				$badge_holder = $bhdb->get_badge_holder($id['context'], $id['context-id']);
				$badge_holder['badge-holder-id-string'] = $id['context'] . '-' . $id['context-id'];
				$response['rows'][] = cm_list_make_row($list_def, $badge_holder);
			}
			$response['time'] = microtime(true) - $time;
			echo json_encode($response);
			break;
	}
	exit(0);
}

if (isset($_POST['action'])) {
	header('Content-type: text/plain');
	switch ($_POST['action']) {

		case 'create-attendee':
			$item = array();
			$errors = array();

			$item['first-name'] = trim($_POST['first-name']);
			if (!$item['first-name']) $errors['first-name'] = 'First name is required.';
			$item['last-name'] = trim($_POST['last-name']);
			if (!$item['last-name']) $errors['last-name'] = 'Last name is required.';

			$item['fandom-name'] = trim($_POST['fandom-name']);
			$item['name-on-badge'] = $item['fandom-name'] ? trim($_POST['name-on-badge']) : 'Real Name Only';
			if (!in_array($item['name-on-badge'], $bhdb->cm_atdb->names_on_badge)) {
				$errors['name-on-badge'] = 'Name on badge is required.';
			}

			$item['date-of-birth'] = parse_date(trim($_POST['date-of-birth']));
			if (!$item['date-of-birth']) $errors['date-of-birth'] = 'Date of birth is required.';
			$item['badge-type-id'] = (int)$_POST['badge-type-id'];
			$found_badge_type = false;
			$badge_types = $bhdb->cm_atdb->list_badge_types(true, true);
			foreach ($badge_types as $badge_type) {
				if ($badge_type['id'] == $item['badge-type-id']) {
					$found_badge_type = $badge_type;
					if ($item['date-of-birth'] && (
						($badge_type['min-birthdate'] && $item['date-of-birth'] < $badge_type['min-birthdate']) ||
						($badge_type['max-birthdate'] && $item['date-of-birth'] > $badge_type['max-birthdate'])
					)) $errors['badge-type-id'] = 'The badge you selected is not applicable.';
				}
			}
			if (!$found_badge_type) {
				$errors['badge-type-id'] = 'The badge you selected is not available.';
			}

			$item['email-address'] = trim($_POST['email-address']);
			if (!$item['email-address']) $errors['email-address'] = 'Email address is required.';
			$item['subscribed'] = isset($_POST['subscribed']) && $_POST['subscribed'];
			$item['phone-number'] = trim($_POST['phone-number']);
			if (!$item['phone-number']) $errors['phone-number'] = 'Phone number is required.';
			else if (strlen($item['phone-number']) < 7) $errors['phone-number'] = 'Phone number is too short.';
			$item['notes'] = trim($_POST['notes']);

			$payment_price = $found_badge_type ? $found_badge_type['price'] : 0;
			$group_uuid = $db->uuid();
			$payment_date = $db->now();
			$item['payment-status'] = 'Incomplete';
			$item['payment-badge-price'] = $payment_price;
			$item['payment-promo-code'] = null;
			$item['payment-promo-price'] = $payment_price;
			$item['payment-group-uuid'] = $group_uuid;
			$item['payment-txn-id'] = $group_uuid;
			$item['payment-txn-amt'] = $payment_price;
			$item['payment-date'] = $payment_date;

			if ($errors) {
				$response = array('ok' => false, 'errors' => $errors);
			} else {
				$id = $bhdb->cm_atdb->create_attendee($item);
				if ($id) {
					$response = array('ok' => true, 'context' => 'attendee', 'context-id' => $id);
				} else {
					$response = array('ok' => false);
				}
			}
			echo json_encode($response);
			break;

		case 'get-badge-holder':
			$context = trim($_POST['context']);
			$context_id = (int)$_POST['context-id'];
			$holder = $bhdb->get_badge_holder($context, $context_id);
			$blacklisted = $holder ? $bhdb->is_blacklisted($context, $holder) : false;
			$response = array(
				'ok' => !!$holder,
				'context' => $context,
				'context-id' => $context_id,
				'holder' => $holder,
				'blacklisted' => $blacklisted
			);
			echo json_encode($response);
			break;

		case 'complete-payment':
			$context = trim($_POST['context']);
			$context_id = (int)$_POST['context-id'];
			$item = $bhdb->get_badge_holder($context, $context_id);
			$errors = array();

			$found_badge_type = false;
			if ($item['badge-type-id'] != (int)$_POST['badge-type-id']) {
				$item['badge-type-id'] = (int)$_POST['badge-type-id'];
				$badge_types = $bhdb->cm_atdb->list_badge_types(true, true);
				foreach ($badge_types as $badge_type) {
					if ($badge_type['id'] == $item['badge-type-id']) {
						$found_badge_type = $badge_type;
						if ($item['date-of-birth'] && (
							($badge_type['min-birthdate'] && $item['date-of-birth'] < $badge_type['min-birthdate']) ||
							($badge_type['max-birthdate'] && $item['date-of-birth'] > $badge_type['max-birthdate'])
						)) $errors['badge-type-id'] = 'The badge you selected is not applicable.';
					}
				}
				if (!$found_badge_type) {
					$errors['badge-type-id'] = 'The badge you selected is not available.';
				}
			} else {
				$badge_types = $bhdb->cm_atdb->list_badge_types();
				foreach ($badge_types as $badge_type) {
					if ($badge_type['id'] == $item['badge-type-id']) {
						$found_badge_type = $badge_type;
					}
				}
			}

			$payment_price = $found_badge_type ? $found_badge_type['price'] : 0;
			$group_uuid = $db->uuid();
			$payment_date = $db->now();
			$item['payment-status'] = 'Completed';
			$item['payment-badge-price'] = $payment_price;
			$item['payment-promo-code'] = null;
			$item['payment-promo-price'] = $payment_price;
			$item['payment-group-uuid'] = $group_uuid;
			$item['payment-type'] = 'Live';
			$item['payment-txn-id'] = $group_uuid;
			$item['payment-txn-amt'] = $payment_price;
			$item['payment-date'] = $payment_date;
			$item['payment-details'] = 'Paid in-person at the event.';

			if ($errors) {
				$response = array('ok' => false, 'errors' => $errors);
			} else {
				$ok = $bhdb->update_badge_holder($context, $context_id, $item);
				$response = array('ok' => $ok, 'context' => $context, 'context-id' => $context_id);
			}
			echo json_encode($response);
			break;

		case 'update-info':
			$context = trim($_POST['context']);
			$context_id = (int)$_POST['context-id'];
			$item = $bhdb->get_badge_holder($context, $context_id);
			$errors = array();

			$item['first-name'] = trim($_POST['first-name']);
			if (!$item['first-name']) $errors['first-name'] = 'First name is required.';
			$item['last-name'] = trim($_POST['last-name']);
			if (!$item['last-name']) $errors['last-name'] = 'Last name is required.';

			$item['fandom-name'] = trim($_POST['fandom-name']);
			$item['name-on-badge'] = $item['fandom-name'] ? trim($_POST['name-on-badge']) : 'Real Name Only';
			if (!in_array($item['name-on-badge'], $bhdb->cm_atdb->names_on_badge)) {
				$errors['name-on-badge'] = 'Name on badge is required.';
			}

			$item['date-of-birth'] = parse_date(trim($_POST['date-of-birth']));
			if (!$item['date-of-birth']) $errors['date-of-birth'] = 'Date of birth is required.';
			$item['notes'] = trim($_POST['notes']);

			if ($errors) {
				$response = array('ok' => false, 'errors' => $errors);
			} else {
				$ok = $bhdb->update_badge_holder($context, $context_id, $item);
				$response = array('ok' => $ok, 'context' => $context, 'context-id' => $context_id);
			}
			echo json_encode($response);
			break;

		case 'checked-in':
			$context = trim($_POST['context']);
			$context_id = (int)$_POST['context-id'];
			$ok = $bhdb->badge_holder_checked_in($context, $context_id);
			$response = array('ok' => $ok, 'context' => $context, 'context-id' => $context_id);
			echo json_encode($response);
			break;

	}
	exit(0);
}

$badges = $bhdb->list_badge_types();
$artwork = $badb->list_badge_artwork();

cm_admin_head('Registration Check-In');
cm_list_head($list_def);

echo '<link rel="stylesheet" href="checkin.css">';
echo '<script type="text/javascript">';
	echo 'cm_badge_type_info = (' . json_encode($badges) . ');';
	echo 'cm_badge_artwork = (' . json_encode($artwork) . ');';
echo '</script>';
echo '<script type="text/javascript" src="checkin.js"></script>';

cm_admin_body('Registration Check-In');
cm_admin_nav('badge-checkin');

echo '<article class="cm-search-page">';

cm_list_search_box($list_def);
cm_list_table($list_def);

echo '<div class="card checkin-state checkin-state-new-attendee hidden">';
	echo '<div class="card-content">';
		echo '<p>Please enter this person\'s badge and contact information.</p>';
		echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-first-name">First Name:</label></th>';
				echo '<td>';
					echo '<input type="text" name="checkin-new-attendee-first-name" id="checkin-new-attendee-first-name">';
					echo '<span class="error" id="checkin-new-attendee-first-name-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-last-name">Last Name:</label></th>';
				echo '<td>';
					echo '<input type="text" name="checkin-new-attendee-last-name" id="checkin-new-attendee-last-name">';
					echo '<span class="error" id="checkin-new-attendee-last-name-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-fandom-name">Fandom Name:</label></th>';
				echo '<td>';
					echo '<input type="text" name="checkin-new-attendee-fandom-name" id="checkin-new-attendee-fandom-name">';
					echo '<span class="error" id="checkin-new-attendee-fandom-name-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr class="checkin-new-attendee-name-on-badge-row hidden">';
				echo '<th><label for="checkin-new-attendee-name-on-badge">Name on Badge:</label></th>';
				echo '<td>';
					echo '<select name="checkin-new-attendee-name-on-badge" id="checkin-new-attendee-name-on-badge">';
						foreach ($bhdb->cm_atdb->names_on_badge as $nob) {
							$hnob = htmlspecialchars($nob);
							echo '<option value="' . $hnob . '">';
							echo $hnob . '</option>';
						}
					echo '</select>';
					echo '<span class="error" id="checkin-new-attendee-name-on-badge-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-date-of-birth">Date of Birth:</label></th>';
				echo '<td>';
					echo '<input type="date" name="checkin-new-attendee-date-of-birth" id="checkin-new-attendee-date-of-birth">';
					if (!ua('Chrome')) echo ' (YYYY-MM-DD)';
					echo '<span class="error" id="checkin-new-attendee-date-of-birth-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-badge-type-id">Badge Type:</label></th>';
				echo '<td>';
					echo '<select name="checkin-new-attendee-badge-type-id" id="checkin-new-attendee-badge-type-id">';
						$badge_types = $bhdb->cm_atdb->list_badge_types(true, true);
						foreach ($badge_types as $bt) {
							$btid = htmlspecialchars($bt['id']);
							$btname = htmlspecialchars($bt['name']);
							$btprice = htmlspecialchars(price_string($bt['price']));
							echo '<option value="' . $btid . '">';
							echo $btname . ' &mdash; ' . $btprice . '</option>';
						}
					echo '</select>';
					echo '<span class="error" id="checkin-new-attendee-badge-type-id-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-email-address">Email Address:</label></th>';
				echo '<td>';
					echo '<input type="email" name="checkin-new-attendee-email-address" id="checkin-new-attendee-email-address">';
					echo '<span class="error" id="checkin-new-attendee-email-address-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-phone-number">Phone Number:</label></th>';
				echo '<td>';
					echo '<input type="text" name="checkin-new-attendee-phone-number" id="checkin-new-attendee-phone-number">';
					echo '<span class="error" id="checkin-new-attendee-phone-number-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-new-attendee-notes">Notes:</label></th>';
				echo '<td>';
					echo '<textarea name="checkin-new-attendee-notes" id="checkin-new-attendee-notes"></textarea>';
					echo '<span class="error" id="checkin-new-attendee-notes-error"></span>';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
		echo '<button class="checkin-new-attendee-button">Continue Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-already-checked-in hidden">';
	echo '<div class="card-content">';
		echo '<p>This person has already been checked in.</p>';
		echo '<p>Please continue only if reprinting a badge.</p>';
		echo '<p>If not reprinting a badge, please contact an executive staff member.</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
		echo '<button class="checkin-skip-checkedin-button">Continue Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-badge-holder-blacklisted hidden">';
	echo '<div class="card-content">';
		echo '<div class="cm-error-box checkin-blacklisted-attendee hidden">';
			echo '<h1>This person matches an entry on the attendee blacklist.</h1>';
			echo '<p>Please contact an executive staff member before proceeding.</p>';
			echo '<p class="added-by hidden">The point of contact for the matched entry is <b></b>.</p>';
		echo '</div>';
		foreach ($bhdb->cm_apdb as $ctx => $apdb) {
			$ctx_lc_html = htmlspecialchars(strtolower($ctx));
			$ctx_name_lc_html = htmlspecialchars(strtolower($apdb->ctx_info['nav_prefix']));
			echo '<div class="cm-error-box checkin-blacklisted-applicant-' . $ctx_lc_html . ' hidden">';
				echo '<h1>This person matches an entry on the ' . $ctx_name_lc_html . ' badge blacklist.</h1>';
				echo '<p>Please contact an executive staff member before proceeding.</p>';
				echo '<p class="added-by hidden">The point of contact for the matched entry is <b></b>.</p>';
			echo '</div>';
			echo '<div class="cm-error-box checkin-blacklisted-application-' . $ctx_lc_html . ' hidden">';
				echo '<h1>This person matches an entry on the ' . $ctx_name_lc_html . ' application blacklist.</h1>';
				echo '<p>Please contact an executive staff member before proceeding.</p>';
				echo '<p class="added-by hidden">The point of contact for the matched entry is <b></b>.</p>';
			echo '</div>';
		}
		echo '<div class="cm-error-box checkin-blacklisted-staff hidden">';
			echo '<h1>This person matches an entry on the staff blacklist.</h1>';
			echo '<p>Please contact an executive staff member before proceeding.</p>';
			echo '<p class="added-by hidden">The point of contact for the matched entry is <b></b>.</p>';
		echo '</div>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
		echo '<button class="checkin-skip-blacklisted-button checkin-exec-override hidden">Continue Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-application-denied hidden">';
	echo '<div class="card-content">';
		echo '<p>This person\'s application was not accepted.</p>';
		echo '<p>Please contact the appropriate executive staff member before proceeding.</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
		echo '<button class="checkin-skip-app-denied-button checkin-exec-override hidden">Continue Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-application-unpaid hidden">';
	echo '<div class="card-content">';
		echo '<p>This person\'s application has not been completed and/or paid for.</p>';
		echo '<p>Please contact the appropriate executive staff member before proceeding.</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
		echo '<button class="checkin-skip-app-unpaid-button checkin-exec-override hidden">Continue Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-payment-incomplete hidden">';
	echo '<div class="card-content">';
		echo '<p>This person\'s badge has not been paid for.</p>';
		echo '<p>Please select a badge type and collect the required payment amount.</p>';
		echo '<p>';
			echo '<select name="checkin-payment-incomplete-badge-type-id" id="checkin-payment-incomplete-badge-type-id">';
				$printed_badge_types = array();
				$printed_separator = false;
				$badge_types = $bhdb->cm_atdb->list_badge_types(true, true);
				foreach ($badge_types as $bt) {
					$printed_badge_types[$bt['id']] = true;
					$btid = htmlspecialchars($bt['id']);
					$btname = htmlspecialchars($bt['name']);
					$btprice = htmlspecialchars(price_string($bt['price']));
					echo '<option value="' . $btid . '">' . $btname;
					echo ' &mdash; Payment Amount: ' . $btprice . '</option>';
				}
				$badge_types = $bhdb->cm_atdb->list_badge_types();
				foreach ($badge_types as $bt) {
					if (!isset($printed_badge_types[$bt['id']])) {
						if (!$printed_separator) {
							$printed_separator = true;
							echo '<option disabled>────────────────────────────────</option>';
						}
						$btid = htmlspecialchars($bt['id']);
						$btname = htmlspecialchars($bt['name']);
						$btprice = htmlspecialchars(price_string($bt['price']));
						echo '<option disabled value="' . $btid . '">';
						echo $btname . ' &mdash; Payment Amount: ' . $btprice . '</option>';
					}
				}
			echo '</select>';
			echo '<span class="error" id="checkin-payment-incomplete-badge-type-id-error"></span>';
		echo '</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
		echo '<button class="checkin-payment-collected-button">Continue Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-verify-info hidden">';
	echo '<div class="card-content">';
		echo '<p class="checkin-verify-info-row">Please verify this person\'s badge information and make any necessary changes.</p>';
		echo '<table border="0" cellpadding="0" cellspacing="0" class="checkin-verify-info-row cm-form-table">';
			echo '<tr>';
				echo '<th><label for="checkin-verify-info-first-name">First Name:</label></th>';
				echo '<td>';
					echo '<input type="text" name="checkin-verify-info-first-name" id="checkin-verify-info-first-name">';
					echo '<span class="error" id="checkin-verify-info-first-name-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-verify-info-last-name">Last Name:</label></th>';
				echo '<td>';
					echo '<input type="text" name="checkin-verify-info-last-name" id="checkin-verify-info-last-name">';
					echo '<span class="error" id="checkin-verify-info-last-name-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-verify-info-fandom-name">Fandom Name:</label></th>';
				echo '<td>';
					echo '<input type="text" name="checkin-verify-info-fandom-name" id="checkin-verify-info-fandom-name">';
					echo '<span class="error" id="checkin-verify-info-fandom-name-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr class="checkin-verify-info-name-on-badge-row hidden">';
				echo '<th><label for="checkin-verify-info-name-on-badge">Name on Badge:</label></th>';
				echo '<td>';
					echo '<select name="checkin-verify-info-name-on-badge" id="checkin-verify-info-name-on-badge">';
						foreach ($bhdb->cm_atdb->names_on_badge as $nob) {
							$hnob = htmlspecialchars($nob);
							echo '<option value="' . $hnob . '">';
							echo $hnob . '</option>';
						}
					echo '</select>';
					echo '<span class="error" id="checkin-verify-info-name-on-badge-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-verify-info-date-of-birth">Date of Birth:</label></th>';
				echo '<td>';
					echo '<input type="date" name="checkin-verify-info-date-of-birth" id="checkin-verify-info-date-of-birth">';
					if (!ua('Chrome')) echo ' (YYYY-MM-DD)';
					echo '<span class="error" id="checkin-verify-info-date-of-birth-error"></span>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="checkin-verify-info-notes">Notes:</label></th>';
				echo '<td>';
					echo '<textarea name="checkin-verify-info-notes" id="checkin-verify-info-notes"></textarea>';
					echo '<span class="error" id="checkin-verify-info-notes-error"></span>';
				echo '</td>';
			echo '</tr>';
		echo '</table>';
		echo '<p class="checkin-rewards-row hidden">Make sure this person collects their rewards.</p>';
		echo '<div class="checkin-rewards-row spacing hidden">';
			echo '<ul class="checkin-rewards"></ul>';
		echo '</div>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
		echo '<button class="checkin-info-verified-button">Continue Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-badge-already-printed hidden">';
	echo '<div class="card-content">';
		echo '<p>This person\'s badge has been pre-printed. Please look for:</p>';
		echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
			echo '<tr>';
				echo '<th>Badge Type:</th>';
				echo '<td class="checkin-preprinted-badge-type"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th>Badge ID:</th>';
				echo '<td class="checkin-preprinted-badge-id"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th>Badge Name:</th>';
				echo '<td class="checkin-preprinted-badge-name"></td>';
			echo '</tr>';
		echo '</table>';
		echo '<p>If a pre-printed badge for this person cannot be found, you can print the badge again.</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Finish Check-In</button>';
		echo '<button class="checkin-print-again-button">Print Again</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-badge-printing hidden">';
	echo '<div class="card-content">';
		echo '<p>Click a badge design to print the badge.</p>';
		echo '<div class="cm-badge-artwork-select spacing">';
			if ($artwork) {
				foreach ($artwork as $i => $a) {
					echo '<a target="_blank" class="cm-badge-artwork hidden" id="artwork-' . $i . '">';
						echo '<div class="cm-badge-artwork-image" style="';
							echo 'background: url(\'artwork-image.php?name=' . urlencode($a['file-name']) . '\');';
							echo 'background-repeat: no-repeat;';
							echo 'background-position: center;';
							echo 'background-size: contain;';
						echo '"></div>';
						echo '<div class="cm-badge-artwork-name">';
							echo htmlspecialchars($a['file-name']);
						echo '</div>';
					echo '</a>';
				}
			} else {
				echo '<div class="cm-badge-artwork-none">';
					echo 'No badge artwork is available.';
				echo '</div>';
			}
		echo '</div>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Finish Check-In</button>';
	echo '</div>';
echo '</div>';

echo '<div class="card checkin-state checkin-state-error hidden">';
	echo '<div class="card-content">';
		echo '<p>An unexpected error occurred.</p>';
		echo '<p>Please contact an executive staff member.</p>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="checkin-cancel-button">Cancel Check-In</button>';
	echo '</div>';
echo '</div>';

echo '</article>';

cm_admin_dialogs();

echo '<div class="dialog confirm-restart-dialog hidden">';
	echo '<div class="dialog-title">Check-In In Progress</div>';
	echo '<div class="dialog-content">';
		echo '<p>';
			echo 'The current check-in has not been finished. ';
			echo 'Are you sure you want to start a new one?';
		echo '</p>';
	echo '</div>';
	echo '<div class="dialog-buttons">';
		echo '<button class="cancel-restart-button">Cancel</button>';
		echo '<button class="continue-restart-button">Continue</button>';
	echo '</div>';
echo '</div>';

echo '<div class="dialog shortcuts-dialog hidden">';
	echo '<div class="dialog-title">Keyboard Shortcuts</div>';
	echo '<div class="dialog-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0">';
			echo '<tr><th colspan="2">List Pages</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Clear and focus on search box</td></tr>';
			echo '<tr><td><span class="kbd kbdw">home</span></td><td>Go to first page of results</td></tr>';
			echo '<tr><td><span class="kbd kbdw">pgup</span></td><td>Go to previous page of results</td></tr>';
			echo '<tr><td><span class="kbd kbdw">pgdn</span></td><td>Go to next page of results</td></tr>';
			echo '<tr><td><span class="kbd kbdw">end</span></td><td>Go to last page of results</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">/</span></td><td>Show keyboard shortcuts</td></tr>';
			echo '<tr><th colspan="2">Registration Check-In</th></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">A</span></td><td>Add new attendee</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Start check-in (single search result)</td></tr>';
			echo '<tr><th colspan="2">Dialog Boxes</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel / Close</td></tr>';
		echo '</table>';
	echo '</div>';
echo '</div>';

cm_admin_tail();
