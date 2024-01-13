<?php

require_once __DIR__ .'/../lib/database/attendee.php';
require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/util/cmforms.php';
require_once __DIR__ .'/../lib/util/slack.php';
require_once __DIR__ .'/staff.php';

$sellable_badge_types = $sdb->list_badge_types(true, true);
if (!$sellable_badge_types) {
	$futureBadges = $sdb->list_badge_types(true, true, true);
	$startDates = array_map(static fn(array $badge): string => ($badge['start-date'] ?? ''), $futureBadges);
	sort($startDates, SORT_STRING);

	$datetime = null;
	if ($startDates[0] ?? false) {
		$datetime = new DateTimeImmutable($startDates[0]);
	}
	cm_app_closed($datetime);
}

$item = array();
$errors = array();

if (isset($_POST['submit'])) {
	$item['first-name'] = trim($_POST['first-name']);
	if (!$item['first-name']) $errors['first-name'] = 'First name is required.';
	$item['last-name'] = trim($_POST['last-name']);
	if (!$item['last-name']) $errors['last-name'] = 'Last name is required.';

	$item['fandom-name'] = trim($_POST['fandom-name']);
	$item['name-on-badge'] = $item['fandom-name'] ? trim($_POST['name-on-badge']) : 'Real Name Only';
	if (!in_array($item['name-on-badge'], $sdb->names_on_badge)) {
		$errors['name-on-badge'] = 'Name on badge is required.';
	}

	$item['date-of-birth'] = parse_date(trim($_POST['date-of-birth']));
	if (!$item['date-of-birth']) $errors['date-of-birth'] = 'Date of birth is required.';
	$item['badge-type-id'] = (int)$_POST['badge-type-id'];
	$found_badge_type = false;
	foreach ($sellable_badge_types as $badge_type) {
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

	$item['application-status'] = 'Submitted';
	$item['payment-status'] = 'Incomplete';
	$item['payment-badge-price'] = $found_badge_type ? $found_badge_type['price'] : 0;
	$item['payment-group-uuid'] = $db->uuid();
	$item['payment-txn-id'] = $item['payment-group-uuid'];
	$item['payment-txn-amt'] = $item['payment-badge-price'];
	$item['payment-date'] = $db->now();

	$item['form-answers'] = array();
	foreach ($questions as $question) {
		if ($question['active'] && $fdb->question_is_visible($question, $item['badge-type-id'])) {
			$answer = cm_form_posted_answer($question['question-id'], $question['type'],$_POST);
			$item['form-answers'][$question['question-id']] = $answer;
			if ($fdb->question_is_required($question, $item['badge-type-id']) && !$answer) {
				$errors['form-answer-'.$question['question-id']] = 'This question is required.';
			}
		}
	}

	if (!$errors) {
		if ($sdb->already_exists($item)) {
			cm_app_message(
				'Application Already Submitted',
				'application-already-submitted',
				'A staff application for this person has already been submitted.<br><br>'.
				'Please <b><a href="mailto:[[contact-address]]">contact us</a></b> '.
				'if you need an update on your status or if you believe this is an error.',
				$item
			);
			exit(0);
		}

		$id = $sdb->create_staff_member($item, $dept_map, $pos_map, $fdb);
		$item = $sdb->get_staff_member($id, false, $name_map, $dept_map, $pos_map, $fdb);

		$template = $mdb->get_mail_template('staff-submitted');
		$mdb->send_mail($item['email-address'], $template, $item);

		$slack = new cm_slack();
		$atdb = new cm_attendee_db($db);
		$blacklisted = $atdb->is_blacklisted($item) || $sdb->is_blacklisted($item);
		if ($blacklisted) {
			if ($contact_address) {
				$body = 'The following staff application which was just submitted matched the blacklist:';
				$body .= "\r\n\r\n".get_site_url(true).'/admin/staff/edit.php?id='.$id;
				mail($contact_address, 'Blacklisted Staff Application', $body, 'From: '.$contact_address);
			}
			if ($slack->get_hook_url('staff-blacklisted')) {
				$body = 'The following staff application which was just submitted matched the blacklist: ';
				$body .= $slack->make_link(get_site_url(true).'/admin/staff/edit.php?id='.$id, 'S'.$id);
				$slack->post_message('staff-blacklisted', $body);
			}
		} else {
			if ($slack->get_hook_url('staff-submitted')) {
				$body = 'A new staff application has been submitted: ';
				$body .= $slack->make_link(get_site_url(true).'/admin/staff/edit.php?review&id='.$id, $item['display-name'].' (S'.$id.')');
				$slack->post_message('staff-submitted', $body);
			}
		}

		cm_app_message(
			'Application Submitted',
			'application-submitted',
			'Your staff application has been submitted.',
			$item
		);
		exit(0);
	}
}

cm_app_head('Staff Application');
echo '<script type="text/javascript">cm_badge_type_info = ('.json_encode($sellable_badge_types).');</script>';
echo '<script type="text/javascript" src="application.js"></script>';
cm_app_body('Staff Application');

echo '<article>';
	echo '<form action="application.php" method="post" class="card cm-reg-edit">';
		echo '<div class="card-title">';
			echo 'Staff Application for ' . htmlspecialchars($event_name);
		echo '</div>';
		echo '<div class="card-content">';
			if ($errors) {
				echo '<div class="cm-error-box">';
					echo '<h2>You\'re not done yet!</h2>';
					echo '<p>';
						echo 'Some information was missing from your application. ';
						echo 'Please address the issues in red and try submitting again. ';
						echo '<b>Your application is not submitted</b> until you see ';
						echo 'the message &ldquo;Application Submitted.&rdquo;';
					echo '</p>';
				echo '</div>';
				echo '<hr>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';

				$text = $fdb->get_custom_text('main');
				if ($text) {
					echo '<tr><td colspan="2"><p>' . safe_html_string($text) . '</p></td></tr>';
					echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				}

				echo '<tr><td colspan="2"><h2>Personal Information</h2></td></tr>';
				$text = $fdb->get_custom_text('personal');
				if ($text) echo '<tr><td colspan="2"><p>' . safe_html_string($text) . '</p></td></tr>';

				echo '<tr>';
					$value = isset($item['first-name']) ? htmlspecialchars($item['first-name']) : '';
					$error = isset($errors['first-name']) ? htmlspecialchars($errors['first-name']) : '';
					echo '<th><label for="first-name">First Name</label></th>';
					echo '<td><input type="text" id="first-name" name="first-name" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['last-name']) ? htmlspecialchars($item['last-name']) : '';
					$error = isset($errors['last-name']) ? htmlspecialchars($errors['last-name']) : '';
					echo '<th><label for="last-name">Last Name</label></th>';
					echo '<td><input type="text" id="last-name" name="last-name" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['fandom-name']) ? htmlspecialchars($item['fandom-name']) : '';
					$error = isset($errors['fandom-name']) ? htmlspecialchars($errors['fandom-name']) : '';
					echo '<th><label for="fandom-name">Fandom Name</label></th>';
					echo '<td><input type="text" id="fandom-name" name="fandom-name" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr id="name-on-badge-row">';
					$value = isset($item['name-on-badge']) ? htmlspecialchars($item['name-on-badge']) : '';
					$error = isset($errors['name-on-badge']) ? htmlspecialchars($errors['name-on-badge']) : '';
					echo '<th><label for="name-on-badge">Name on Badge</label></th>';
					echo '<td>';
						echo '<select id="name-on-badge" name="name-on-badge">';
							foreach ($sdb->names_on_badge as $nob) {
								$hnob = htmlspecialchars($nob);
								echo '<option value="' . $hnob;
								echo ($value == $hnob) ? '" selected>' : '">';
								echo $hnob . '</option>';
							}
						echo '</select>';
						if ($error) echo '<span class="error">' . $error . '</span>';
					echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['date-of-birth']) ? htmlspecialchars($item['date-of-birth']) : '';
					$error = isset($errors['date-of-birth']) ? htmlspecialchars($errors['date-of-birth']) : '';
					echo '<th><label for="date-of-birth">Date of Birth</label></th>';
					echo '<td><input type="date" id="date-of-birth" name="date-of-birth" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>';
					else if (!ua('Chrome')) echo ' (YYYY-MM-DD)'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['badge-type-id']) ? htmlspecialchars($item['badge-type-id']) : '';
					$error = isset($errors['badge-type-id']) ? htmlspecialchars($errors['badge-type-id']) : '';
					echo '<th><label for="badge-type-id">Badge Type</label></th>';
					echo '<td>';
						echo '<select id="badge-type-id" name="badge-type-id">';
							foreach ($sellable_badge_types as $bt) {
								$btid = htmlspecialchars($bt['id']);
								$btname = htmlspecialchars($bt['name']);
								$btprice = htmlspecialchars(price_string($bt['price']));
								echo '<option value="' . $btid;
								echo ($value == $btid) ? '" selected>' : '">';
								echo $btname . ' &mdash; ' . $btprice . '</option>';
							}
						echo '</select>';
						if ($error) echo '<span class="error">' . $error . '</span>';
					echo '</td>';
				echo '</tr>';

				foreach ($sellable_badge_types as $bt) {
					echo '<tr class="cm-reg-inline-badge-type hidden"';
					echo ' id="cm-reg-inline-badge-type-' . (int)$bt['id'] . '">';
						echo '<th></th>';
						echo '<td>';
							if ($bt['description']) {
								echo '<p>' . safe_html_string($bt['description']) . '</p>';
							}
							if ($bt['rewards']) {
								if (substr(trim($bt['description']), -1) != ':') {
									echo '<p><b>Rewards:</b></p>';
								}
								echo '<ul>';
								foreach ($bt['rewards'] as $reward) {
									echo '<li>' . safe_html_string($reward) . '</li>';
								}
								echo '</ul>';
							}
						echo '</td>';
					echo '</tr>';
				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Contact Information</h2></td></tr>';
				$text = $fdb->get_custom_text('contact');
				if ($text) echo '<tr><td colspan="2"><p>' . safe_html_string($text) . '</p></td></tr>';

				echo '<tr>';
					$value = isset($item['email-address']) ? htmlspecialchars($item['email-address']) : '';
					$error = isset($errors['email-address']) ? htmlspecialchars($errors['email-address']) : '';
					echo '<th><label for="email-address">Email Address</label></th>';
					echo '<td><input type="email" id="email-address" name="email-address" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = $item['subscribed'] ?? true;
					echo '<th></th><td><label>';
						echo '<input type="checkbox" name="subscribed" value="1"' . ($value ? ' checked>' : '>');
						echo 'You may contact me with promotional emails.';
					echo '</label><br>';
						echo '(You may <b><a href="unsubscribe.php" target="_blank">unsubscribe</a></b> at any time.)';
					echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['phone-number']) ? htmlspecialchars($item['phone-number']) : '';
					$error = isset($errors['phone-number']) ? htmlspecialchars($errors['phone-number']) : '';
					echo '<th><label for="phone-number">Phone Number</label></th>';
					echo '<td><input type="text" id="phone-number" name="phone-number" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['address-1']) ? htmlspecialchars($item['address-1']) : '';
					$error = isset($errors['address-1']) ? htmlspecialchars($errors['address-1']) : '';
					echo '<th><label for="address-1">Street Address</label></th>';
					echo '<td><input type="text" id="address-1" name="address-1" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['address-2']) ? htmlspecialchars($item['address-2']) : '';
					$error = isset($errors['address-2']) ? htmlspecialchars($errors['address-2']) : '';
					echo '<th></th><td><input type="text" id="address-2" name="address-2" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['city']) ? htmlspecialchars($item['city']) : '';
					$error = isset($errors['city']) ? htmlspecialchars($errors['city']) : '';
					echo '<th><label for="city">City</label></th>';
					echo '<td><input type="text" id="city" name="city" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['state']) ? htmlspecialchars($item['state']) : '';
					$error = isset($errors['state']) ? htmlspecialchars($errors['state']) : '';
					echo '<th><label for="state">State or Province</label></th>';
					echo '<td><input type="text" id="state" name="state" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['zip-code']) ? htmlspecialchars($item['zip-code']) : '';
					$error = isset($errors['zip-code']) ? htmlspecialchars($errors['zip-code']) : '';
					echo '<th><label for="zip-code">ZIP or Postal Code</label></th>';
					echo '<td><input type="text" id="zip-code" name="zip-code" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['country']) ? htmlspecialchars($item['country']) : '';
					$error = isset($errors['country']) ? htmlspecialchars($errors['country']) : '';
					echo '<th><label for="country">Country</label></th>';
					echo '<td><input type="text" id="country" name="country" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				$first = true;
				foreach ($questions as $question) {
					if ($question['active']) {
						if ($first) {
							echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
							echo '<tr><td colspan="2"><h2>Staff Information</h2></td></tr>';
						}
						$answer = (
							$item['form-answers'][$question['question-id']] ?? array()
						);
						$error = (
							$errors['form-answer-' . $question['question-id']] ?? null
						);
						echo cm_form_row($question, $answer, $error);
						$first = false;
					}
				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Emergency Contact Information</h2></td></tr>';
				$text = $fdb->get_custom_text('ice');
				if ($text) echo '<tr><td colspan="2"><p>' . safe_html_string($text) . '</p></td></tr>';

				echo '<tr>';
					$value = isset($item['ice-name']) ? htmlspecialchars($item['ice-name']) : '';
					$error = isset($errors['ice-name']) ? htmlspecialchars($errors['ice-name']) : '';
					echo '<th><label for="ice-name">Emergency Contact Name</label></th>';
					echo '<td><input type="text" id="ice-name" name="ice-name" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['ice-relationship']) ? htmlspecialchars($item['ice-relationship']) : '';
					$error = isset($errors['ice-relationship']) ? htmlspecialchars($errors['ice-relationship']) : '';
					echo '<th><label for="ice-relationship">Emergency Contact Relationship</label></th>';
					echo '<td><input type="text" id="ice-relationship" name="ice-relationship" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['ice-email-address']) ? htmlspecialchars($item['ice-email-address']) : '';
					$error = isset($errors['ice-email-address']) ? htmlspecialchars($errors['ice-email-address']) : '';
					echo '<th><label for="ice-email-address">Emergency Contact Email Address</label></th>';
					echo '<td><input type="email" id="ice-email-address" name="ice-email-address" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

				echo '<tr>';
					$value = isset($item['ice-phone-number']) ? htmlspecialchars($item['ice-phone-number']) : '';
					$error = isset($errors['ice-phone-number']) ? htmlspecialchars($errors['ice-phone-number']) : '';
					echo '<th><label for="ice-phone-number">Emergency Contact Phone Number</label></th>';
					echo '<td><input type="text" id="ice-phone-number" name="ice-phone-number" value="' . $value . '">';
					if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
				echo '</tr>';

			echo '</table>';
		echo '</div>';
		echo '<div class="card-buttons">';
			echo '<input type="submit" name="submit" value="Apply">';
		echo '</div>';
	echo '</form>';
echo '</article>';

cm_app_tail();
