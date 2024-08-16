<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/application.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/database/mail.php';
require_once __DIR__ .'/../../lib/database/misc.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../../lib/util/cmforms.php';
require_once __DIR__ .'/../../lib/util/slack.php';
require_once __DIR__ .'/../admin.php';
require_once __DIR__ .'/../../../vendor/autoload.php';

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

cm_admin_check_permission('applications-'.$ctx_lc, array('||',
	'applications-view-'.$ctx_lc,
	'applications-review-'.$ctx_lc,
	'applications-edit-'.$ctx_lc
));

$read_only = isset($_GET['ro']);
$can_edit = $adb->user_has_permission($admin_user, 'applications-edit-'.$ctx_lc) && !$read_only;
$can_review = $adb->user_has_permission($admin_user, 'applications-review-'.$ctx_lc) && !$read_only;
$review_mode = isset($_GET['review']);
$can_edit_info = $review_mode ? false : $can_edit;
$can_edit_status = $review_mode ? $can_review : $can_edit;
$can_submit = $can_edit_info || $can_edit_status;

$can_view_applicants = $adb->user_has_permission($admin_user, 'applicants-view-'.$ctx_lc) && !$review_mode && !$read_only;
$can_edit_applicants = $adb->user_has_permission($admin_user, 'applicants-edit-'.$ctx_lc) && !$review_mode && !$read_only;
$can_delete_applicants = $adb->user_has_permission($admin_user, 'applicants-delete-'.$ctx_lc) && !$review_mode && !$read_only;

$apdb = new cm_application_db($db, $context);
$name_map = $apdb->get_badge_type_name_map();

$fdb = new cm_forms_db($db, 'application-'.$ctx_lc);
$questions = $fdb->list_questions();

$atdb = new cm_attendee_db($db);
$mdb = new cm_mail_db($db);
$midb = new cm_misc_db($db);

$taskSchedulePublishable = new \App\Task\SchedulePublishableTask(
    new \App\Hook\CloudflareApi(),
);

$new = !isset($_GET['id']);
$id = $new ? -1 : (int)$_GET['id'];
$item = $new ? array() : $apdb->get_application($id, false, true, $name_map, $fdb);
$submitted = $can_submit && isset($_POST['submit']);
$changed = false;

$action_url = 'edit.php?c=' . $ctx_lc;
if ($review_mode) $action_url .= '&review';
if (!$new) $action_url .= '&id=' . $id;
if ($read_only) $action_url .= '&ro';

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/application/' . $action_url,
	'entity-type' => $ctx_name_lc . ' badge',
	'entity-type-pl' => $ctx_name_lc . ' badges',
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
			'name' => 'Email Address',
			'key' => 'email-address',
			'type' => 'email-subbed'
		),
		array(
			'name' => 'Already Registered',
			'key' => 'attendee-id',
			'type' => 'bool'
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
	),
	'sort-order' => array(0),
	'row-key' => 'id',
	'name-key' => 'display-name',
	'row-actions' => array(
		(($can_view_applicants || $can_edit_applicants) ? 'edit' : null),
		($can_delete_applicants ? 'delete' : null)
	),
	'table-actions' => array(($can_edit_applicants ? 'add' : null)),
	'edit-label' => ($can_edit_applicants ? 'Edit' : 'View'),
	'add-url' => get_site_url(false) . '/admin/application/badge-edit.php?c='.$ctx_lc.'&pid='.$id,
	'edit-url' => get_site_url(false) . '/admin/application/badge-edit.php?c='.$ctx_lc.'&id=',
	'delete-title' => 'Delete ' . $ctx_name . ' Badge'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$applicants = $item['applicants'] ?? array();
			$response = cm_list_process_entities($list_def, $applicants);
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $apdb->delete_applicant($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

if ($submitted) {
	if ($can_edit_info) {
		/* Basic Information */
		$item['contact-first-name'] = trim($_POST['contact-first-name']);
		$item['contact-last-name'] = trim($_POST['contact-last-name']);
		$item['contact-email-address'] = trim($_POST['contact-email-address']);
		$item['contact-subscribed'] = isset($_POST['contact-subscribed']) && $_POST['contact-subscribed'];
		$item['contact-phone-number'] = trim($_POST['contact-phone-number']);
		$item['badge-type-id'] = (int)$_POST['badge-type-id'];
		$item['business-name'] = trim($_POST['business-name']);
		$item['application-name'] = trim($_POST['application-name']);
		$item['assignment-count'] = (int)trim($_POST['assignment-count']);
		$item['applicant-count'] = (int)trim($_POST['applicant-count']);
		$item['permit-number'] = trim($_POST['permit-number']);

		/* Payment Information */
		if (
			$new
			|| (               $item['payment-status'     ]  !=                $_POST['payment-status'     ]  )
			|| ( float_or_null($item['payment-badge-price']) !== float_or_null($_POST['payment-badge-price']) )
			|| (               $item['payment-type'       ]  !=                $_POST['payment-type'       ]  )
			|| (               $item['payment-txn-id'     ]  !=                $_POST['payment-txn-id'     ]  )
			|| ( float_or_null($item['payment-txn-amt'    ]) !== float_or_null($_POST['payment-txn-amt'    ]) )
			|| (               $item['payment-details'    ]  !=                $_POST['payment-details'    ]  )
		) {
			$item['payment-status'     ] =          trim($_POST['payment-status'     ]);
			$item['payment-badge-price'] = float_or_null($_POST['payment-badge-price']);
			$item['payment-type'       ] =          trim($_POST['payment-type'       ]);
			$item['payment-txn-id'     ] =          trim($_POST['payment-txn-id'     ]);
			$item['payment-txn-amt'    ] = float_or_null($_POST['payment-txn-amt'    ]);
			$item['payment-details'    ] =               $_POST['payment-details'    ] ;
			$item['payment-group-uuid' ] = $db->uuid();
			$item['payment-date'       ] = $db->now();
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
		$item['notes'] = $_POST['notes'];

		/* Assigned Rooms & Tables */
		$assignments = array();
		foreach ($_POST as $k => $v) {
			if (substr($k, 0, 28) === 'assignment-room-or-table-id-') {
				$key = substr($k, 28);
				if (isset($assignments[$key])) {
					$assignments[$key]['room-or-table-id'] = trim($v);
				} else {
					$assignments[$key] = array('room-or-table-id' => trim($v));
				}
			}
			if (substr($k, 0, 22) === 'assignment-start-time-') {
				$key = substr($k, 22);
				if (isset($assignments[$key])) {
					$assignments[$key]['start-time'] = parse_datetime(trim($v));
				} else {
					$assignments[$key] = array('start-time' => parse_datetime(trim($v)));
				}
			}
			if (substr($k, 0, 20) === 'assignment-end-time-') {
				$key = substr($k, 20);
				if (isset($assignments[$key])) {
					$assignments[$key]['end-time'] = parse_datetime(trim($v));
				} else {
					$assignments[$key] = array('end-time' => parse_datetime(trim($v)));
				}
			}
		}
		$item['assigned-rooms-and-tables'] = array_values($assignments);
	}

	/* Write Changes */
	if ($new) {
		$id = $apdb->create_application($item, $fdb);
		$new = ($id === false);
		$changed = ($id !== false);
		if ($changed) {
			$action_url .= '&id=' . $id;
			$list_def['ajax-url'] = get_site_url(false) . '/admin/application/' . $action_url;
			$list_def['add-url'] = get_site_url(false) . '/admin/application/badge-edit.php?c='.$ctx_lc.'&pid='.$id;
		}
	} else {
		$changed = $apdb->update_application($item, $fdb);
	}
	if ($changed) {
		$item = $apdb->get_application($id, false, true, $name_map, $fdb);
		if ($can_edit_info) {
			if (isset($_POST['add-to-blacklist']) && $_POST['add-to-blacklist']) {
				$blacklist_entry = $item;
				$blacklist_entry['added-by'] = trim($_POST['add-to-blacklist-added-by']);
				$blacklist_entry['notes'] = trim($_POST['add-to-blacklist-notes']);
				$apdb->create_application_blacklist_entry($blacklist_entry);
			}
			if (isset($_POST['resend-payment-email']) && $_POST['resend-payment-email']) {
				$template = $mdb->get_mail_template('application-paid-' . $ctx_lc);
				$mdb->send_mail($item['contact-email-address'], $template, $item);
			}
		}
		if ($can_edit_status) {
			$taskSchedulePublishable->onScheduleManualUpdate();
			if (isset($_POST['resend-application-email']) && $_POST['resend-application-email']) {
				$application_status = strtolower($item['application-status']);
				$template_name = 'application-' . $application_status . '-' . $ctx_lc;
				$template = $mdb->get_mail_template($template_name);
				$mdb->send_mail($item['contact-email-address'], $template, $item);

				$slack = new cm_slack();
				$template_name = array('application-' . $application_status, $ctx_uc);
				if ($slack->get_hook_url($template_name)) {
					$body = 'The ' . $ctx_name_lc . ' application for ';
					$body .= $slack->make_link(
						get_site_url(true).'/admin/application/edit.php?c='.$ctx_lc.'&review&id='.$id,
						$item['application-name'].' ('.$item['id-string'].')'
					);
					$body .= ' has been '.$application_status;
					if ($admin_user['name']) {
						$body .= ' by '.$admin_user['name'];
					} else if ($admin_user['username']) {
						$body .= ' by '.$admin_user['username'];
					}
					$body .= '.';
					$slack->post_message($template_name, $body);
				}
			}
		}
	}
}

$title = ($new ? 'Add ' : ($review_mode ? 'Review ' : 'Edit ')) . $ctx_name . ' Application';
$name = $item['application-name'] ?? null;
$full_title = (!$new && $name) ? ($title . ' - ' . $name) : $title;

$image_size = $midb->get_file_image_size('rooms-and-tables');
if (!$image_size) $image_size = array(640, 480);
$image_ratio = $image_size[1] * 100 / $image_size[0];

cm_admin_head($full_title);
if (!$new) cm_list_head($list_def);

echo '<link rel="stylesheet" href="edit.css">';
echo '<style>.tag-map { padding-bottom: ' . $image_ratio . '%; }</style>';
echo '<script type="text/javascript">cm_assigned_rooms_and_tables = ('.json_encode($item['assigned-rooms-and-tables'] ?? array()).');</script>';
echo '<script type="text/javascript" src="edit.js"></script>';

cm_admin_body($title);
cm_admin_nav('applications-' . $ctx_lc);

function echo_mode_switch() {
	global $ctx_lc, $can_edit, $can_review, $review_mode, $new, $id;
	if ($can_edit && $review_mode && !$new) {
		echo '<div class="mode-switch">';
			echo '<a href="edit.php?c=' . $ctx_lc . '&id=' . $id . '" class="button">';
				echo 'Switch to Edit Mode';
			echo '</a>';
		echo '</div>';
	}
	if ($can_review && !$review_mode && !$new) {
		echo '<div class="mode-switch">';
			echo '<a href="edit.php?c=' . $ctx_lc . '&review&id=' . $id . '" class="button">';
				echo 'Switch to Review Mode';
			echo '</a>';
		echo '</div>';
	}
}

echo '<article>';
	if ($can_submit) {
		echo '<form action="' . $action_url . '" method="post" class="card cm-reg-edit">';
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
			$attendee_blacklisted = array();
			$applicant_blacklisted = array();
			if (isset($item['applicants'])) {
				foreach ($item['applicants'] as $applicant) {
					if ($atdb->is_blacklisted($applicant)) {
						$attendee_blacklisted[] = $applicant['display-name'];
					}
					if ($apdb->is_applicant_blacklisted($applicant)) {
						$applicant_blacklisted[] = $applicant['display-name'];
					}
				}
			}
			if ($attendee_blacklisted) {
				echo '<div class="cm-error-box">';
					echo '<h1>This record matches at least one entry on the attendee blacklist.</h1>';
					echo '<p>Please contact an executive staff member before proceeding.</p>';
					echo '<p>The badges that matched are: ';
					echo htmlspecialchars(implode(', ', $attendee_blacklisted));
					echo '</p>';
				echo '</div>';
			}
			if ($applicant_blacklisted) {
				echo '<div class="cm-error-box">';
					echo '<h1>This record matches at least one entry on the ' . $ctx_name_lc . ' badge blacklist.</h1>';
					echo '<p>Please contact an executive staff member before proceeding.</p>';
					echo '<p>The badges that matched are: ';
					echo htmlspecialchars(implode(', ', $applicant_blacklisted));
					echo '</p>';
				echo '</div>';
			}
			if (($application_blacklisted = $apdb->is_application_blacklisted($item))) {
				echo '<div class="cm-error-box">';
					echo '<h1>This record matches an entry on the ' . $ctx_name_lc . ' application blacklist.</h1>';
					echo '<p>Please contact an executive staff member before proceeding.</p>';
					if ($application_blacklisted['added-by']) {
						echo '<p>The point of contact for the matched entry is <b>';
						echo htmlspecialchars($application_blacklisted['added-by']);
						echo '</b>.</p>';
					}
				echo '</div>';
			}
			if ($item['min-age'] && $item['min-age'] < 18) {
				echo '<p class="cm-note-box">At least one applicant on this application is under 18.</p>';
			}
			if (($can_submit && $submitted) || $attendee_blacklisted || $applicant_blacklisted || $application_blacklisted || ($item['min-age'] && $item['min-age'] < 18)) {
				echo '<hr>';
			}
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table cm-list-table-containing-table">';

				echo '<tr><td colspan="2"><h2>Primary Contact Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="contact-first-name">First Name</label></th>';
					$value = isset($item['contact-first-name']) ? htmlspecialchars($item['contact-first-name']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="contact-first-name" name="contact-first-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="contact-last-name">Last Name</label></th>';
					$value = isset($item['contact-last-name']) ? htmlspecialchars($item['contact-last-name']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="contact-last-name" name="contact-last-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="contact-email-address">Email Address</label></th>';
					$value = isset($item['contact-email-address']) ? htmlspecialchars($item['contact-email-address']) : '';
					if ($can_edit_info) {
						echo '<td><input type="email" id="contact-email-address" name="contact-email-address" value="' . $value . '"></td>';
					} else {
						echo '<td><a href="mailto:' . $value . '">' . $value . '</a></td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th>&nbsp;</th>';
					$value = $item['contact-subscribed'] ?? true;
					if ($can_edit_info) {
						echo '<td><label>';
							echo '<input type="checkbox" name="contact-subscribed" value="1"' . ($value ? ' checked>' : '>');
							echo 'You may contact me with promotional emails.';
						echo '</label></td>';
					} else {
						echo '<td>' . ($value ? 'You may contact me with promotional emails.' : 'You <b>MAY NOT</b> contact me with promotional emails.') . '</td>';
					}
				echo '</tr>';

				$value = isset($item['contact-unsubscribe-link']) ? htmlspecialchars($item['contact-unsubscribe-link']) : '';
				if ($value) {
					echo '<tr>';
						echo '<th><label>Unsubscribe Link</label></th>';
						echo '<td><a href="' . $value . '" target="_blank">' . $value . '</a></td>';
					echo '</tr>';
				}

				echo '<tr>';
					echo '<th><label for="contact-phone-number">Phone Number</label></th>';
					$value = isset($item['contact-phone-number']) ? htmlspecialchars($item['contact-phone-number']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="contact-phone-number" name="contact-phone-number" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>' . htmlspecialchars($ctx_name) . ' Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="badge-type-id">Badge Type</label></th>';
					if ($can_edit_info) {
						$value = isset($item['badge-type-id']) ? htmlspecialchars($item['badge-type-id']) : '';
						echo '<td>';
							echo '<select id="badge-type-id" name="badge-type-id">';
								$badge_types = $apdb->list_badge_types();
								foreach ($badge_types as $bt) {
									$btid = htmlspecialchars($bt['id']);
									$btname = htmlspecialchars($bt['name']);
									$btprice = htmlspecialchars(price_string($bt['base-price']));
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

				echo '<tr>';
					echo '<th><label for="business-name">' . htmlspecialchars($ctx_info['business_name_term']) . '</label></th>';
					$value = isset($item['business-name']) ? htmlspecialchars($item['business-name']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="business-name" name="business-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="application-name">' . htmlspecialchars($ctx_info['application_name_term']) . '</label></th>';
					$value = isset($item['application-name']) ? htmlspecialchars($item['application-name']) : '';
					if ($can_edit_info) {
						echo '<td><input type="text" id="application-name" name="application-name" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="assignment-count">' . htmlspecialchars($ctx_info['assignment_term'][1]) . ' Requested</label></th>';
					$value = isset($item['assignment-count']) ? htmlspecialchars($item['assignment-count']) : 1;
					if ($can_edit_info) {
						echo '<td><input type="number" id="assignment-count" name="assignment-count" min="1" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

				echo '<tr>';
					echo '<th><label for="applicant-count">Badges Requested</label></th>';
					$value = isset($item['applicant-count']) ? htmlspecialchars($item['applicant-count']) : 1;
					if ($can_edit_info) {
						echo '<td><input type="number" id="applicant-count" name="applicant-count" min="1" value="' . $value . '"></td>';
					} else {
						echo '<td>' . $value . '</td>';
					}
				echo '</tr>';

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
						$answer = (
							$item['form-answers'][$question['question-id']] ?? array()
						);
						echo cm_form_review_row($question, $answer, $can_edit_info);
					}
				}

				if ($can_edit_info && !$new && !$application_blacklisted) {
					echo '<tr class="cm-add-to-blacklist">';
						echo '<th>&nbsp;</th>';
						echo '<td><label><input type="checkbox" name="add-to-blacklist" value="1">Add to Application Blacklist</label></td>';
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

				if (!$new) {
					echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
					echo '<tr><td colspan="2"><h2>Badge Information</h2></td></tr>';
					echo '<tr><td colspan="2" class="cm-list-table-containing-cell">';
					cm_list_table($list_def);
					echo '</td></tr>';
				}

				echo '<tr><td colspan="2" class="hr"><hr></td></tr>';
				echo '<tr><td colspan="2"><h2>Application Information</h2></td></tr>';

				echo '<tr>';
					echo '<th><label for="application-status">Application Status</label></th>';
					$value = isset($item['application-status']) ? htmlspecialchars($item['application-status']) : '';
					if ($can_edit_status) {
						echo '<td>';
							echo '<select id="application-status" name="application-status">';
								foreach ($apdb->application_statuses as $as) {
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
						echo '<th>Assigned ' . htmlspecialchars($ctx_info['assignment_term'][1]) . '</th>';
						echo '<td id="ea-assignments">';
							echo '<div class="ea-assignment-row">';
								echo 'Loading...';
							echo '</div>';
						echo '</td>';
					echo '</tr>';
					echo '<tr class="hidden">';
						echo '<th>&nbsp;</th>';
						echo '<td id="ea-assignment-none">';
							echo '<div class="ea-assignment-row">';
								echo 'None';
							echo '</div>';
						echo '</td>';
					echo '</tr>';
					echo '<tr class="hidden">';
						echo '<th>&nbsp;</th>';
						echo '<td id="ea-assignment-template">';
							echo '<div class="ea-assignment-row">';
								echo '<label>Room/Table:</label>';
								echo '<input type="text" class="ea-assignment-room-or-table-id">';
								echo '<button class="select-button">Select</button>';
								echo '<label>Start Time:</label>';
								echo '<input type="datetime-local" class="ea-assignment-start-time" value="'.$cm_config['event']['start_date'].'T00:00">';
								echo '<label>End Time:</label>';
								echo '<input type="datetime-local" class="ea-assignment-end-time" value="'.$cm_config['event']['end_date'].'T23:59">';
								echo '<button class="delete-button">Delete</button>';
							echo '</div>';
						echo '</td>';
					echo '</tr>';
					echo '<tr>';
						echo '<th>&nbsp;</th>';
						echo '<td id="ea-assignment-add">';
							echo '<button class="add-button">Add</button>';
							if (!ua('Chrome')) echo ' (For start and end time, use the format YYYY-MM-DD HH:MM.)';
						echo '</td>';
					echo '</tr>';
				} else {
					echo '<tr>';
						echo '<th>Assigned ' . htmlspecialchars($ctx_info['assignment_term'][1]) . '</th>';
						echo '<td>';
							if (isset($item['assigned-rooms-and-tables']) && $item['assigned-rooms-and-tables']) {
								foreach ($item['assigned-rooms-and-tables'] as $art) {
									echo '<div>';
										echo htmlspecialchars($art['room-or-table-id']);
										if ($art['start-time']) {
											if ($art['end-time']) {
												echo ' &mdash; ' . htmlspecialchars($art['start-time']);
												echo ' through ' . htmlspecialchars($art['end-time']);
											} else {
												echo ' &mdash; starting ' . htmlspecialchars($art['start-time']);
											}
										} else if ($art['end-time']) {
											echo ' &mdash; ending ' . htmlspecialchars($art['end-time']);
										}
									echo '</div>';
								}
							} else {
								echo '<div>None</div>';
							}
						echo '</td>';
					echo '</tr>';
				}

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
						echo '<th><label for="permit-number">Permit Number</label></th>';
						$value = isset($item['permit-number']) ? htmlspecialchars($item['permit-number']) : '';
						if ($can_edit_info) {
							echo '<td><input type="text" id="permit-number" name="permit-number" value="' . $value . '"></td>';
						} else {
							echo '<td>' . $value . '</td>';
						}
					echo '</tr>';

					echo '<tr>';
						echo '<th><label for="payment-status">Payment Status</label></th>';
						$value = isset($item['payment-status']) ? htmlspecialchars($item['payment-status']) : '';
						if ($can_edit_info) {
							echo '<td>';
								echo '<select id="payment-status" name="payment-status">';
									foreach ($apdb->payment_statuses as $ps) {
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

					if (!$new) {
						$invoice = $apdb->generate_invoice($item, $atdb);
						if ($invoice) {
							echo '<tr>';
								echo '<th>&nbsp;</th>';
								echo '<td>';
									echo 'If left blank, the amount to be paid will be the price calculated below. ';
									echo 'If the calculated price is incorrect or not desired, set this field to the desired payment amount.';
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<th>&nbsp;</th>';
								echo '<td class="cm-list-table-containing-cell">';
									echo '<div class="cm-list-table">';
										echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-cart">';
											echo '<thead>';
												echo '<tr>';
													echo '<th>Item</th>';
													echo '<th class="td-numeric">Price</th>';
												echo '</tr>';
											echo '</thead>';
											$total_price = 0;
											echo '<tbody>';
												foreach ($invoice as $ii) {
													echo '<tr>';
														echo '<td>';
															echo '<div><b>' . htmlspecialchars($ii['name']) . '</b></div>';
															echo '<div>' . htmlspecialchars($ii['details']) . '</div>';
														echo '</td>';
														echo '<td class="td-numeric">';
															echo htmlspecialchars($ii['price-string']);
														echo '</td>';
													echo '</tr>';
													$total_price += $ii['price'];
												}
											echo '</tbody>';
											echo '<tfoot>';
												echo '<tr>';
													echo '<th>Total:</th>';
													echo '<th class="td-numeric">';
														echo htmlspecialchars(price_string($total_price));
													echo '</th>';
												echo '</tr>';
											echo '</tfoot>';
										echo '</table>';
									echo '</div>';
								echo '</td>';
							echo '</tr>';
							echo '<tr>';
								echo '<th>&nbsp;</th>';
								echo '<td>';
									echo 'The calculated price will not be updated until after changes have been saved.';
								echo '</td>';
							echo '</tr>';
						}
					}

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

					if (!$new) {

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

echo '<div class="dialog room-table-select-dialog hidden">';
	echo '<div class="dialog-title">Select Room or Table</div>';
	echo '<div class="dialog-content">';
		echo '<div class="spacing">';
			echo '<div class="tag-map">';
				echo '<div class="tags">';
					$tags = $apdb->list_rooms_and_tables(true);
					if ($tags) {
						foreach ($tags as $tag) {
							echo '<div class="tag" style="';
							echo 'top:' . (min($tag['y1'], $tag['y2']) * 100) . '%;';
							echo 'left:' . (min($tag['x1'], $tag['x2']) * 100) . '%;';
							echo 'right:' . ((1 - max($tag['x1'], $tag['x2'])) * 100) . '%;';
							echo 'bottom:' . ((1 - max($tag['y1'], $tag['y2'])) * 100) . '%;';
							echo '">';
								echo '<div class="tag-button-container">';
									if ($tag['assignments']) {
										$app_names = array();
										foreach ($tag['assignments'] as $a) {
											if (isset($a['application-name']) && $a['application-name']) {
												$app_names[] = $a['application-name'];
											} else {
												$app_names[] = (
													'[' . $a['context'] . 'A' .
													$a['context-id'] . ']'
												);
											}
										}
										echo '<button class="confirm-select-button assigned" ';
										echo 'title="' . htmlspecialchars(implode("\n", $app_names)) . '">';
									} else {
										echo '<button class="confirm-select-button">';
									}
									echo htmlspecialchars($tag['id']);
									echo '</button>';
								echo '</div>';
							echo '</div>';
						}
					}
				echo '</div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
	echo '<div class="dialog-buttons">';
		echo '<button class="cancel-select-button">Cancel</button>';
	echo '</div>';
echo '</div>';

if (!$new) cm_list_dialogs($list_def);
cm_admin_tail();
