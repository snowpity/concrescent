<?php

require_once __DIR__ .'/../lib/database/attendee.php';
require_once __DIR__ .'/../lib/util/util.php';
require_once __DIR__ .'/../lib/util/cmforms.php';
require_once __DIR__ .'/../lib/util/slack.php';
require_once __DIR__ .'/apply.php';

function applicant_form($apdb, $i, $applicant, $errors) {
	$out = '<tbody class="applicant-rows applicant-rows-'.$i.'">';
		$out .= '<tr><td colspan="2"><h3>Badge #' . ($i + 1) . '</h3></td></tr>';
		$out .= '<tr>';
			$value = isset($applicant['first-name']) ? htmlspecialchars($applicant['first-name']) : '';
			$error = isset($errors['first-name-'.$i]) ? htmlspecialchars($errors['first-name-'.$i]) : '';
			$out .= '<th><label for="first-name-'.$i.'">First Name</label></th>';
			$out .= '<td><input type="text" id="first-name-'.$i.'" name="first-name-'.$i.'" value="' . $value . '">';
			if ($error) $out .= '<span class="error">' . $error . '</span>'; $out .= '</td>';
		$out .= '</tr>';
		$out .= '<tr>';
			$value = isset($applicant['last-name']) ? htmlspecialchars($applicant['last-name']) : '';
			$error = isset($errors['last-name-'.$i]) ? htmlspecialchars($errors['last-name-'.$i]) : '';
			$out .= '<th><label for="last-name-'.$i.'">Last Name</label></th>';
			$out .= '<td><input type="text" id="last-name-'.$i.'" name="last-name-'.$i.'" value="' . $value . '">';
			if ($error) $out .= '<span class="error">' . $error . '</span>'; $out .= '</td>';
		$out .= '</tr>';
		$out .= '<tr>';
			$value = isset($applicant['fandom-name']) ? htmlspecialchars($applicant['fandom-name']) : '';
			$error = isset($errors['fandom-name-'.$i]) ? htmlspecialchars($errors['fandom-name-'.$i]) : '';
			$out .= '<th><label for="fandom-name-'.$i.'">Fandom Name</label></th>';
			$out .= '<td><input type="text" id="fandom-name-'.$i.'" name="fandom-name-'.$i.'" value="' . $value . '">';
			if ($error) $out .= '<span class="error">' . $error . '</span>'; $out .= '</td>';
		$out .= '</tr>';
		$out .= '<tr id="name-on-badge-row-'.$i.'">';
			$value = isset($applicant['name-on-badge']) ? htmlspecialchars($applicant['name-on-badge']) : '';
			$error = isset($errors['name-on-badge-'.$i]) ? htmlspecialchars($errors['name-on-badge-'.$i]) : '';
			$out .= '<th><label for="name-on-badge-'.$i.'">Name on Badge</label></th>';
			$out .= '<td>';
				$out .= '<select id="name-on-badge-'.$i.'" name="name-on-badge-'.$i.'">';
					foreach ($apdb->names_on_badge as $nob) {
						$hnob = htmlspecialchars($nob);
						$out .= '<option value="' . $hnob;
						$out .= ($value == $hnob) ? '" selected>' : '">';
						$out .= $hnob . '</option>';
					}
				$out .= '</select>';
				if ($error) $out .= '<span class="error">' . $error . '</span>';
			$out .= '</td>';
		$out .= '</tr>';
		$out .= '<tr>';
			$value = isset($applicant['date-of-birth']) ? htmlspecialchars($applicant['date-of-birth']) : '';
			$error = isset($errors['date-of-birth-'.$i]) ? htmlspecialchars($errors['date-of-birth-'.$i]) : '';
			$out .= '<th><label for="date-of-birth-'.$i.'">Date of Birth</label></th>';
			$out .= '<td><input type="date" id="date-of-birth-'.$i.'" name="date-of-birth-'.$i.'" value="' . $value . '">';
			if ($error) $out .= '<span class="error">' . $error . '</span>';
			else if (!ua('Chrome')) $out .= ' (YYYY-MM-DD)'; $out .= '</td>';
		$out .= '</tr>';
		$out .= '<tr>';
			$value = isset($applicant['email-address']) ? htmlspecialchars($applicant['email-address']) : '';
			$error = isset($errors['email-address-'.$i]) ? htmlspecialchars($errors['email-address-'.$i]) : '';
			$out .= '<th><label for="email-address-'.$i.'">Email Address</label></th>';
			$out .= '<td><input type="email" id="email-address-'.$i.'" name="email-address-'.$i.'" value="' . $value . '">';
			if ($error) $out .= '<span class="error">' . $error . '</span>'; $out .= '</td>';
		$out .= '</tr>';
		$out .= '<tr>';
			$value = $applicant['subscribed'] ?? true;
			$out .= '<th></th><td><label>';
				$out .= '<input type="checkbox" name="subscribed-'.$i.'" value="1"' . ($value ? ' checked>' : '>');
				$out .= 'You may contact me with promotional emails.';
			$out .= '</label></td>';
		$out .= '</tr>';
		$out .= '<tr>';
			$value = isset($applicant['phone-number']) ? htmlspecialchars($applicant['phone-number']) : '';
			$error = isset($errors['phone-number-'.$i]) ? htmlspecialchars($errors['phone-number-'.$i]) : '';
			$out .= '<th><label for="phone-number-'.$i.'">Phone Number</label></th>';
			$out .= '<td><input type="text" id="phone-number-'.$i.'" name="phone-number-'.$i.'" value="' . $value . '">';
			if ($error) $out .= '<span class="error">' . $error . '</span>'; $out .= '</td>';
		$out .= '</tr>';
		$out .= '<tr>';
			$value = $applicant['attendee-id'] ?? false;
			$error = isset($errors['already-registered-'.$i]) ? htmlspecialchars($errors['already-registered-'.$i]) : '';
			$out .= '<th><label for="already-registered-'.$i.'">Already Registered</label></th>';
			$out .= '<td>';
				$out .= '<label>';
					$out .= '<input type="checkbox" name="already-registered-'.$i.'" value="1"';
					$out .= ($value || $error) ? ' checked>' : '>';
					$out .= 'This person has already registered as an attendee.';
				$out .= '</label>';
				if ($error) $out .= '<p class="error">' . $error . '</p>';
			$out .= '</td>';
		$out .= '</tr>';
	return $out . '</tbody>';
}

/* - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - */

$active_badge_types = $apdb->list_badge_types(true, false);
$sellable_badge_types = $apdb->list_badge_types(true, true);
if (!$sellable_badge_types) cm_app_closed();

$item = array();
$errors = array();

if (isset($_POST['submit'])) {
	$atdb = new cm_attendee_db($db);

	$item['contact-first-name'] = trim($_POST['contact-first-name']);
	if (!$item['contact-first-name']) $errors['contact-first-name'] = 'First name is required.';
	$item['contact-last-name'] = trim($_POST['contact-last-name']);
	if (!$item['contact-last-name']) $errors['contact-last-name'] = 'Last name is required.';

	$item['contact-email-address'] = trim($_POST['contact-email-address']);
	if (!$item['contact-email-address']) $errors['contact-email-address'] = 'Email address is required.';
	$item['contact-subscribed'] = isset($_POST['contact-subscribed']) && $_POST['contact-subscribed'];
	$item['contact-phone-number'] = trim($_POST['contact-phone-number']);
	if (!$item['contact-phone-number']) $errors['contact-phone-number'] = 'Phone number is required.';
	else if (strlen($item['contact-phone-number']) < 7) $errors['contact-phone-number'] = 'Phone number is too short.';

	$item['badge-type-id'] = (int)$_POST['badge-type-id'];
	$found_badge_type = false;
	foreach ($sellable_badge_types as $badge_type) {
		if ($badge_type['id'] == $item['badge-type-id']) {
			$found_badge_type = $badge_type;
		}
	}
	if (!$found_badge_type) {
		$errors['badge-type-id'] = 'The badge you selected is not available.';
	}

	$item['business-name'] = trim($_POST['business-name']);
	if (!$item['business-name']) $errors['business-name'] = 'This question is required.';
	$item['application-name'] = trim($_POST['application-name']);
	if (!$item['application-name']) $errors['application-name'] = 'This question is required.';

	$item['assignment-count'] = (int)trim($_POST['assignment-count']);
	if ($item['assignment-count'] < 1) {
		$errors['assignment-count'] = 'Must be at least 1.';
	} else if (
		$found_badge_type &&
		$found_badge_type['max-assignment-count'] &&
		$item['assignment-count'] > $found_badge_type['max-assignment-count']
	) {
		$errors['assignment-count'] = 'Must be no more than ' . $found_badge_type['max-assignment-count'] . '.';
	}

	$item['applicant-count'] = (int)trim($_POST['applicant-count']);
	if ($item['applicant-count'] < 1) {
		$errors['applicant-count'] = 'Must be at least 1.';
	} else if (
		$found_badge_type &&
		$found_badge_type['max-applicant-count'] &&
		$item['applicant-count'] > $found_badge_type['max-applicant-count']
	) {
		$errors['applicant-count'] = 'Must be no more than ' . $found_badge_type['max-applicant-count'] . '.';
	}

	$item['application-status'] = 'Submitted';
	$item['payment-status'] = 'Incomplete';
	$item['payment-group-uuid'] = $db->uuid();
	$item['payment-txn-id'] = $item['payment-group-uuid'];
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

	$item['applicants'] = array();
	for ($i = 0; $i < $item['applicant-count']; $i++) {
		$first_name = trim($_POST['first-name-'.$i]);
		$last_name = trim($_POST['last-name-'.$i]);
		if (!$first_name && !$last_name) {
			$errors['first-name-'.$i] = 'First name or last name is required.';
		}

		$fandom_name = trim($_POST['fandom-name-'.$i]);
		$name_on_badge = $fandom_name ? trim($_POST['name-on-badge-'.$i]) : 'Real Name Only';
		if (!in_array($name_on_badge, $apdb->names_on_badge)) {
			$errors['name-on-badge-'.$i] = 'Name on badge is required.';
		}

		$date_of_birth = parse_date(trim($_POST['date-of-birth-'.$i]));
		if (!$date_of_birth) $errors['date-of-birth-'.$i] = 'Date of birth is required.';
		$email_address = trim($_POST['email-address-'.$i]);
		if (!$email_address) $errors['email-address-'.$i] = 'Email address is required.';
		$subscribed = isset($_POST['subscribed-'.$i]) && $_POST['subscribed-'.$i];
		$phone_number = trim($_POST['phone-number-'.$i]);
		if (!$phone_number) $errors['phone-number-'.$i] = 'Phone number is required.';
		else if (strlen($phone_number) < 7) $errors['phone-number-'.$i] = 'Phone number is too short.';

		$applicant = array(
			'first-name' => $first_name,
			'last-name' => $last_name,
			'fandom-name' => $fandom_name,
			'name-on-badge' => $name_on_badge,
			'date-of-birth' => $date_of_birth,
			'email-address' => $email_address,
			'subscribed' => $subscribed,
			'phone-number' => $phone_number,
			'attendee-id' => null
		);
		if (isset($_POST['already-registered-'.$i]) && $_POST['already-registered-'.$i]) {
			if (($id = $atdb->lookup_attendee($applicant))) $applicant['attendee-id'] = $id;
			else $errors['already-registered-'.$i] = 'Could not find existing registration.';
		}
		$item['applicants'][$i] = $applicant;
	}

	if (!$errors) {
		if ($apdb->already_exists($item)) {
			cm_app_message(
				'Application Already Submitted',
				'application-already-submitted',
				'An application for this '.$ctx_name_lc.' has already been submitted.<br><br>'.
				'Please <b><a href="mailto:[[contact-address]]">contact us</a></b> '.
				'if you need to update your application or if you believe this is an error.',
				$item
			);
			exit(0);
		}

		$id = $apdb->create_application($item, $fdb);
		foreach ($item['applicants'] as $applicant) {
			$applicant['application-id'] = $id;
			$apdb->create_applicant($applicant);
		}
		$item = $apdb->get_application($id, false, true, $name_map, $fdb);

		$template = $mdb->get_mail_template('application-submitted-'.$ctx_lc);
		$mdb->send_mail($item['contact-email-address'], $template, $item);

		$slack = new cm_slack();
		$blacklisted = $apdb->is_application_blacklisted($item);
		foreach ($item['applicants'] as $applicant) {
			if (
				$atdb->is_blacklisted($applicant) ||
				$apdb->is_applicant_blacklisted($applicant)
			) {
				$blacklisted = true;
			}
		}
		if ($blacklisted) {
			if ($contact_address) {
				$body = 'The following '.$ctx_name_lc.' application which was just submitted matched the blacklist:';
				$body .= "\r\n\r\n".get_site_url(true).'/admin/application/edit.php?c='.$ctx_lc.'&id='.$id;
				mail($contact_address, 'Blacklisted '.$ctx_name.' Application', $body, 'From: '.$contact_address);
			}
			if ($slack->get_hook_url(array('application-blacklisted', $ctx_uc))) {
				$body = 'The following '.$ctx_name_lc.' application which was just submitted matched the blacklist: ';
				$body .= $slack->make_link(get_site_url(true).'/admin/application/edit.php?c='.$ctx_lc.'&id='.$id, $ctx_uc.'A'.$id);
				$slack->post_message(array('application-blacklisted', $ctx_uc), $body);
			}
		} else {
			if ($slack->get_hook_url(array('application-submitted', $ctx_uc))) {
				$body = 'A new '.$ctx_name_lc.' application has been submitted: ';
				$body .= $slack->make_link(get_site_url(true).'/admin/application/edit.php?c='.$ctx_lc.'&review&id='.$id, $item['application-name'].' ('.$ctx_uc.'A'.$id.')');
				$slack->post_message(array('application-submitted', $ctx_uc), $body);
			}
		}

		cm_app_message(
			'Application Submitted',
			'application-submitted',
			'Your '.$ctx_name_lc.' application has been submitted.',
			$item
		);
		exit(0);
	}
}

cm_app_head($ctx_name . ' Application');
echo '<script type="text/javascript">';
	$empty_applicant_template = applicant_form($apdb, 0, array(), array());
	$js_ctx_info = (
		array('ctx_lc' => $ctx_lc, 'ctx_uc' => $ctx_uc) + $ctx_info +
		array('empty_applicant_template' => $empty_applicant_template)
	);
	echo 'cm_app_context_info = ('.json_encode($js_ctx_info).');';
	echo 'cm_badge_type_info = ('.json_encode($sellable_badge_types).');';
echo '</script>';
echo '<script type="text/javascript" src="application.js"></script>';
cm_app_body($ctx_name . ' Application');

echo '<article>';
	echo '<form action="application.php?c=' . $ctx_lc . '" method="post" class="card cm-reg-edit">';
		echo '<div class="card-title">';
			echo htmlspecialchars($ctx_name) . ' Application for ' . htmlspecialchars($event_name);
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
				echo '<tbody class="application-rows">';

					$text = $fdb->get_custom_text('main');
					if ($text) {
						echo '<tr><td colspan="2"><p>' . safe_html_string($text) . '</p></td></tr>';
						echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
					}

					echo '<tr><td colspan="2"><h2>Primary Contact Information</h2></td></tr>';
					echo '<tr><td colspan="2"><p>';
						$text = $fdb->get_custom_text('contact');
						if ($text) {
							echo safe_html_string($text);
						} else {
							echo (
								'Please provide us with the name and contact information of '.
								'the person we should contact regarding this application. '.
								'This is the person who will be contacted in case this '.
								'application is accepted.'
							);
						}
					echo '</p></td></tr>';

					echo '<tr>';
						$value = isset($item['contact-first-name']) ? htmlspecialchars($item['contact-first-name']) : '';
						$error = isset($errors['contact-first-name']) ? htmlspecialchars($errors['contact-first-name']) : '';
						echo '<th><label for="contact-first-name">First Name</label></th>';
						echo '<td><input type="text" id="contact-first-name" name="contact-first-name" value="' . $value . '">';
						if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
					echo '</tr>';

					echo '<tr>';
						$value = isset($item['contact-last-name']) ? htmlspecialchars($item['contact-last-name']) : '';
						$error = isset($errors['contact-last-name']) ? htmlspecialchars($errors['contact-last-name']) : '';
						echo '<th><label for="contact-last-name">Last Name</label></th>';
						echo '<td><input type="text" id="contact-last-name" name="contact-last-name" value="' . $value . '">';
						if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
					echo '</tr>';

					echo '<tr>';
						$value = isset($item['contact-email-address']) ? htmlspecialchars($item['contact-email-address']) : '';
						$error = isset($errors['contact-email-address']) ? htmlspecialchars($errors['contact-email-address']) : '';
						echo '<th><label for="contact-email-address">Email Address</label></th>';
						echo '<td><input type="email" id="contact-email-address" name="contact-email-address" value="' . $value . '">';
						if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
					echo '</tr>';

					echo '<tr>';
						$value = $item['contact-subscribed'] ?? true;
						echo '<th></th><td><label>';
							echo '<input type="checkbox" name="contact-subscribed" value="1"' . ($value ? ' checked>' : '>');
							echo 'You may contact me with promotional emails.';
						echo '</label><br>';
							echo '(You may <b><a href="unsubscribe.php?c=' . $ctx_lc . '" target="_blank">unsubscribe</a></b> at any time.)';
						echo '</td>';
					echo '</tr>';

					echo '<tr>';
						$value = isset($item['contact-phone-number']) ? htmlspecialchars($item['contact-phone-number']) : '';
						$error = isset($errors['contact-phone-number']) ? htmlspecialchars($errors['contact-phone-number']) : '';
						echo '<th><label for="contact-phone-number">Phone Number</label></th>';
						echo '<td><input type="text" id="contact-phone-number" name="contact-phone-number" value="' . $value . '">';
						if ($error) echo '<span class="error">' . $error . '</span>'; echo '</td>';
					echo '</tr>';

					echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
					echo '<tr><td colspan="2"><h2>' . $ctx_name . ' Information</h2></td></tr>';
					$text = $fdb->get_custom_text('application');
					if ($text) echo '<tr><td colspan="2"><p>' . safe_html_string($text) . '</p></td></tr>';

					echo '<tr>';
						$value = isset($item['badge-type-id']) ? htmlspecialchars($item['badge-type-id']) : '';
						$error = isset($errors['badge-type-id']) ? htmlspecialchars($errors['badge-type-id']) : '';
						echo '<th></th>';
						echo '<td>';
							echo '<p class="cm-question-title">Badge Type</p>';
							echo '<p class="cm-question-text">The type of ' . $ctx_name_lc . ' badge you are requesting.</p>';
							echo '<p><select id="badge-type-id" name="badge-type-id">';
								foreach ($sellable_badge_types as $bt) {
									$btid = htmlspecialchars($bt['id']);
									$btname = htmlspecialchars($bt['name']);
									$btprice = htmlspecialchars(price_string($bt['base-price']));
									echo '<option value="' . $btid;
									echo ($value == $btid) ? '" selected>' : '">';
									echo $btname . ' &mdash; ' . $btprice . '</option>';
								}
							echo '</select></p>';
							if ($error) echo '<p class="error">' . $error . '</p>';
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

					echo '<tr>';
						$value = isset($item['business-name']) ? htmlspecialchars($item['business-name']) : '';
						$error = isset($errors['business-name']) ? htmlspecialchars($errors['business-name']) : '';
						echo '<th></th>';
						echo '<td>';
							echo '<p class="cm-question-title">' . $ctx_info['business_name_term'] . '</p>';
							echo '<p class="cm-question-text">' . $ctx_info['business_name_text'] . '</p>';
							echo '<p><input type="text" id="business-name" name="business-name" value="' . $value . '"></p>';
							if ($error) echo '<p class="error">' . $error . '</p>';
						echo '</td>';
					echo '</tr>';

					echo '<tr>';
						$value = isset($item['application-name']) ? htmlspecialchars($item['application-name']) : '';
						$error = isset($errors['application-name']) ? htmlspecialchars($errors['application-name']) : '';
						echo '<th></th>';
						echo '<td>';
							echo '<p class="cm-question-title">' . $ctx_info['application_name_term'] . '</p>';
							echo '<p class="cm-question-text">' . $ctx_info['application_name_text'] . '</p>';
							echo '<p><input type="text" id="application-name" name="application-name" value="' . $value . '"></p>';
							if ($error) echo '<p class="error">' . $error . '</p>';
						echo '</td>';
					echo '</tr>';

					echo '<tr class="assignment-count-row">';
						$value = isset($item['assignment-count']) ? htmlspecialchars($item['assignment-count']) : '1';
						$error = isset($errors['assignment-count']) ? htmlspecialchars($errors['assignment-count']) : '';
						echo '<th></th>';
						echo '<td>';
							echo '<p class="cm-question-title">' . $ctx_info['assignment_term'][1] . ' Requested</p>';
							echo '<p class="cm-question-text">The number of ' . strtolower($ctx_info['assignment_term'][1]) . ' you are requesting.</p>';
							echo '<p><input type="number" id="assignment-count" name="assignment-count" min="1" value="' . $value . '"></p>';
							if ($error) echo '<p class="error">' . $error . '</p>';
							else echo '<p class="assignment-count-rate"></p>';
						echo '</td>';
					echo '</tr>';

					foreach ($questions as $question) {
						if ($question['active']) {
							$answer = (
								isset($item['form-answers']) &&
								isset($item['form-answers'][$question['question-id']]) ?
								$item['form-answers'][$question['question-id']] :
								array()
							);
							$error = (
								$errors['form-answer-' . $question['question-id']] ?? null
							);
							echo cm_form_row($question, $answer, $error);
						}
					}

					echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
					echo '<tr><td colspan="2"><h2>Badge Information</h2></td></tr>';
					echo '<tr><td colspan="2"><p>';
						$text = $fdb->get_custom_text('applicant');
						if ($text) {
							echo safe_html_string($text);
						} else {
							echo (
								'Please provide us with the names and contact information of '.
								'the people who should receive badges, <b>INCLUDING YOURSELF</b>. '.
								'We ask for date of birth only to verify age.'
							);
						}
					echo '</p></td></tr>';

					echo '<tr class="applicant-count-row">';
						$value = isset($item['applicant-count']) ? htmlspecialchars($item['applicant-count']) : '1';
						$error = isset($errors['applicant-count']) ? htmlspecialchars($errors['applicant-count']) : '';
						echo '<th></th>';
						echo '<td>';
							echo '<p class="cm-question-title">Badges Requested</p>';
							echo '<p class="cm-question-text">The number of badges you are requesting.</p>';
							echo '<p><input type="number" id="applicant-count" name="applicant-count" min="1" value="' . $value . '"></p>';
							if ($error) echo '<p class="error">' . $error . '</p>';
							else echo '<p class="applicant-count-rate"></p>';
						echo '</td>';
					echo '</tr>';

				echo '</tbody>';

				if (isset($item['applicants']) && $item['applicants']) {
					foreach ($item['applicants'] as $i => $applicant) {
						echo applicant_form($apdb, $i, $applicant, $errors);
					}
				} else if (isset($item['applicant-count']) && $item['applicant-count']) {
					for ($i = 0; $i < $item['applicant-count']; $i++) {
						echo applicant_form($apdb, $i, array(), $errors);
					}
				} else {
					echo applicant_form($apdb, 0, array(), $errors);
				}

			echo '</table>';
		echo '</div>';
		echo '<div class="card-buttons">';
			echo '<input type="submit" name="submit" value="Apply">';
		echo '</div>';
	echo '</form>';
echo '</article>';

cm_app_tail();
