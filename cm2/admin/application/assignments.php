<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/application.php';
require_once __DIR__ .'/../../lib/database/misc.php';
require_once __DIR__ .'/../../lib/database/forms.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
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

cm_admin_check_permission('application-assignments-'.$ctx_lc, 'application-assignments-'.$ctx_lc);

$apdb = new cm_application_db($db, $context);
$midb = new cm_misc_db($db);

$taskSchedulePublishable = new \App\Task\SchedulePublishableTask(
    new \App\Hook\CloudflareApi(),
);

$list_def = array(
	'loader' => 'server-side',
	'ajax-url' => get_site_url(false) . '/admin/application/assignments.php?c='.$ctx_lc,
	'entity-type' => $ctx_name_lc.' application',
	'entity-type-pl' => $ctx_name_lc.' applications',
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
			'name' => $ctx_info['business_name_term'],
			'key' => 'business-name',
			'type' => 'text'
		),
		array(
			'name' => $ctx_info['application_name_term'],
			'key' => 'application-name',
			'type' => 'text'
		),
		array(
			'name' => 'Badge Type',
			'key' => 'badge-type-name',
			'type' => 'text'
		),
		array(
			'name' => 'Application Status',
			'key' => 'application-status',
			'type' => 'status-label'
		),
	),
	'sort-order' => array(~0),
	'row-key' => 'id',
	'name-key' => 'application-name',
	'row-actions' => array('select')
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$time = microtime(true);
			$response = $apdb->cm_anldb->list_indexes($list_def);
			$response['rows'] = array();
			$name_map = $apdb->get_badge_type_name_map();
			$fdb = new cm_forms_db($db, 'application-'.$ctx_lc);
			foreach ($response['ids'] as $id) {
				$application = $apdb->get_application($id, false, true, $name_map, $fdb);
				$response['rows'][] = cm_list_make_row($list_def, $application);
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
		case 'list-tags':
			$tags = $apdb->list_rooms_and_tables(true);
			$response = array('ok' => true, 'tags' => $tags);
			echo json_encode($response);
			break;
		case 'list-assignments':
			$assignments = $apdb->list_room_and_table_assignments(null, $ctx_uc);
			$response = array('ok' => true, 'assignments' => $assignments);
			echo json_encode($response);
			break;
		case 'update-assignment':
			$response = array('ok' => true, 'deleted' => false, 'created' => false);
			if (
				isset($_POST['old-context']) && $_POST['old-context'] &&
				isset($_POST['old-context-id']) && $_POST['old-context-id']
			) {
				$tmpdb = new cm_application_db($db, $_POST['old-context']);
				$tmpapp = $tmpdb->get_application($_POST['old-context-id']);
				if (
					$tmpapp &&
					isset($tmpapp['assigned-rooms-and-tables']) &&
					$tmpapp['assigned-rooms-and-tables']
				) {
					$old_room_or_table_id = $_POST['old-room-or-table-id'];
					$old_start_time = parse_datetime($_POST['old-start-time']);
					$old_end_time = parse_datetime($_POST['old-end-time']);
					foreach ($tmpapp['assigned-rooms-and-tables'] as $i => $art) {
						$art_room_or_table_id = $art['room-or-table-id'];
						$art_start_time = parse_datetime($art['start-time']);
						$art_end_time = parse_datetime($art['end-time']);
						if (
							$art_room_or_table_id == $old_room_or_table_id &&
							$art_start_time == $old_start_time &&
							$art_end_time == $old_end_time
						) {
							array_splice($tmpapp['assigned-rooms-and-tables'], $i, 1);
							if ($tmpdb->update_application($tmpapp)) {
								$response['deleted'] = true;
							} else {
								$response['ok'] = false;
							}
							break;
						}
					}
				}
			}
			if (
				isset($_POST['context']) && $_POST['context'] &&
				isset($_POST['context-id']) && $_POST['context-id']
			) {
				$tmpdb = new cm_application_db($db, $_POST['context']);
				$tmpapp = $tmpdb->get_application($_POST['context-id']);
				if ($tmpapp) {
					$assignment = array(
						'context' => $_POST['context'],
						'context-id' => $_POST['context-id'],
						'room-or-table-id' => $_POST['room-or-table-id'],
						'start-time' => parse_datetime($_POST['start-time']),
						'end-time' => parse_datetime($_POST['end-time'])
					);
					if (
						isset($tmpapp['assigned-rooms-and-tables']) &&
						$tmpapp['assigned-rooms-and-tables']
					) {
						$tmpapp['assigned-rooms-and-tables'][] = $assignment;
					} else {
						$tmpapp['assigned-rooms-and-tables'] = array($assignment);
					}
					if ($tmpdb->update_application($tmpapp)) {
						$response['created'] = true;
					} else {
						$response['ok'] = false;
					}
				}
			}
			echo json_encode($response);
			if ($response['ok']) $taskSchedulePublishable->onScheduleManualUpdate();
			break;
	}
	exit(0);
}

$image_size = $midb->get_file_image_size('rooms-and-tables');
if (!$image_size) $image_size = array(640, 480);
$image_ratio = $image_size[1] * 100 / $image_size[0];

$event_dates = array();
$event_dates_iso = array();
$start_time = strtotime($cm_config['event']['start_date']);
$end_time = strtotime($cm_config['event']['end_date']);
while ($start_time <= $end_time) {
	$event_dates[] = date('D M j', $start_time);
	$event_dates_iso[] = date('Y-m-d', $start_time);
	$start_time = strtotime('+1 day', $start_time);
}

cm_admin_head($ctx_name.' '.$ctx_info['assignment_term'][0].' Assignments');
cm_list_head($list_def);

echo '<link rel="stylesheet" href="assignments.css">';
echo '<style>';
	echo '.tag-map { padding-bottom: ' . $image_ratio . '%; }';
	echo '.calendar { height: ' . (count($event_dates) * 1440 + 37) . 'px; }';
	echo '.calendar-column { height: ' . (count($event_dates) * 1440 + 21) . 'px; }';
echo '</style>';

echo '<script type="text/javascript">';
	$js_event_info = (
		$cm_config['event'] +
		array('dates' => $event_dates_iso)
	);
	echo 'cm_app_event_info = ('.json_encode($js_event_info).');';
	$js_ctx_info = (
		array('ctx_lc' => $ctx_lc, 'ctx_uc' => $ctx_uc) + $ctx_info +
		array('ctx_name' => $ctx_name, 'ctx_name_lc' => $ctx_name_lc)
	);
	echo 'cm_app_context_info = ('.json_encode($js_ctx_info).');';
echo '</script>';
echo '<script type="text/javascript" src="assignments.js"></script>';

cm_admin_body($ctx_name.' '.$ctx_info['assignment_term'][0].' Assignments');
cm_admin_nav('application-assignments-'.$ctx_lc);
echo '<article>';

echo '<div class="card">';
	echo '<div class="card-title">Rooms &amp; Tables</div>';
	echo '<div class="card-content">';
		echo '<div class="spacing">';
			echo '<div class="tag-map">';
				echo '<div class="tags"></div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="card">';
	echo '<div class="card-title">Assignments by Room or Table</div>';
	echo '<div class="card-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0">';
			echo '<thead>';
				echo '<tr>';
					echo '<th>R/T</th>';
					echo '<th>Time</th>';
					echo '<th>'.htmlspecialchars($ctx_info['application_name_term']).'</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody class="cm-assignments-by-room-or-table"></tbody>';
		echo '</table>';
	echo '</div>';
echo '</div>';

echo '<div class="card">';
	echo '<div class="card-title">Assignments by '.htmlspecialchars($ctx_info['application_name_term']).'</div>';
	echo '<div class="card-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0">';
			echo '<thead>';
				echo '<tr>';
					echo '<th>'.htmlspecialchars($ctx_info['application_name_term']).'</th>';
					echo '<th>R/T</th>';
					echo '<th>Time</th>';
				echo '</tr>';
			echo '</thead>';
			echo '<tbody class="cm-assignments-by-application-name"></tbody>';
		echo '</table>';
	echo '</div>';
echo '</div>';

echo '<div class="card">';
	echo '<div class="card-title">Assignments by Time</div>';
	echo '<div class="card-content">';
		echo '<div class="spacing">';
			echo '<div class="calendar">';
				echo '<div class="calendar-header">';
					echo '<div class="calendar-column">';
						echo '<div class="calendar-column-body">';
							foreach ($event_dates as $i => $event_date) {
								echo '<div class="calendar-day-label" style="top: '.($i * 1440).'px;">';
								echo htmlspecialchars($event_date);
								echo '</div>';
							}
						echo '</div>';
					echo '</div>';
					echo '<div class="calendar-column">';
						echo '<div class="calendar-column-body">';
							foreach ($event_dates as $i => $event_date) {
								for ($h = 0; $h < 24; $h++) {
									echo '<div class="calendar-hour-label" style="top: '.($i * 1440 + $h * 60).'px;">';
									echo substr('00'.$h, -2);
									echo '</div>';
								}
							}
						echo '</div>';
					echo '</div>';
					echo '<div class="calendar-column">';
						echo '<div class="calendar-column-body">';
							foreach ($event_dates as $i => $event_date) {
								for ($h = 0; $h < 24; $h++) {
									echo '<div class="calendar-hour-label" style="top: '.($i * 1440 + $h * 60).'px;">';
									echo (($h % 12) ? ($h % 12) : 12) . (($h < 12) ? 'am' : 'pm');
									echo '</div>';
								}
							}
						echo '</div>';
					echo '</div>';
				echo '</div>';
				echo '<div class="calendar-body"></div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '</article>';
cm_admin_dialogs();

echo '<div class="dialog edit-assignment-dialog hidden">';
	echo '<div class="dialog-title">Edit Assignment</div>';
	echo '<div class="dialog-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
			echo '<tr>';
				echo '<th><label for="ea-context-id-string">Application ID:</label></th>';
				echo '<td>';
					echo '<input type="hidden" id="ea-old-context" name="ea-old-context">';
					echo '<input type="hidden" id="ea-old-context-id" name="ea-old-context-id">';
					echo '<input type="hidden" id="ea-context" name="ea-context">';
					echo '<input type="hidden" id="ea-context-id" name="ea-context-id">';
					echo '<input type="text" id="ea-context-id-string" name="ea-context-id-string" readonly>';
					echo '<button class="edit-dialog-select-application-button">Select</button>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="ea-room-or-table-id">Room or Table ID:</label></th>';
				echo '<td>';
					echo '<input type="hidden" id="ea-old-room-or-table-id" name="ea-old-room-or-table-id">';
					echo '<input type="text" id="ea-room-or-table-id" name="ea-room-or-table-id">';
					echo '<button class="edit-dialog-select-room-or-table-button">Select</button>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="ea-start-time">Start Time:</label></th>';
				echo '<td>';
					echo '<input type="hidden" id="ea-old-start-time" name="ea-old-start-time">';
					echo '<input type="datetime-local" id="ea-start-time" name="ea-start-time">';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="ea-end-time">End Time:</label></th>';
				echo '<td>';
					echo '<input type="hidden" id="ea-old-end-time" name="ea-old-end-time">';
					echo '<input type="datetime-local" id="ea-end-time" name="ea-end-time">';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th></th>';
				echo '<td>';
					if (ua('Chrome')) {
						echo 'For a start time of midnight, use 00:01 or 12:01 AM.<br>';
						echo 'For an end time of midnight, use 23:59 or 11:59 PM.';
					} else {
						echo 'For start and end time, use YYYY-MM-DD HH:MM format.<br>';
						echo 'For a start time of midnight, use 00:01.<br>';
						echo 'For an end time of midnight, use 23:59.';
					}
				echo '</td>';
			echo '</tr>';
		echo '</table>';
	echo '</div>';
	echo '<div class="dialog-buttons">';
		echo '<button class="edit-dialog-delete-button">Delete</button>';
		echo '<button class="edit-dialog-cancel-button">Cancel</button>';
		echo '<button class="edit-dialog-save-button">Save</button>';
	echo '</div>';
echo '</div>';

echo '<div class="dialog select-application-dialog hidden">';
	echo '<div class="dialog-title">Select Application</div>';
	echo '<div class="dialog-content">';
		cm_list_search_box($list_def);
		cm_list_table($list_def);
	echo '</div>';
	echo '<div class="dialog-buttons">';
		echo '<button class="select-application-dialog-cancel-button">Cancel</button>';
	echo '</div>';
echo '</div>';

echo '<div class="dialog select-room-table-dialog hidden">';
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
										echo '<button class="select-room-table-dialog-select-button assigned" ';
										echo 'title="' . htmlspecialchars(implode("\n", $app_names)) . '">';
									} else {
										echo '<button class="select-room-table-dialog-select-button">';
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
		echo '<button class="select-room-table-dialog-cancel-button">Cancel</button>';
	echo '</div>';
echo '</div>';

echo '<div class="dialog shortcuts-dialog hidden">';
	echo '<div class="dialog-title">Keyboard Shortcuts</div>';
	echo '<div class="dialog-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0">';
			echo '<tr><th colspan="2">Edit Assignment</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">A</span></td><td>Select Application</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">R</span></td><td>Select Room or Table</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">D</span></td><td>Delete</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Save</td></tr>';
			echo '<tr><th colspan="2">Select Application</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel</td></tr>';
			echo '<tr><td><span class="kbd kbdw">home</span></td><td>Go to first page of results</td></tr>';
			echo '<tr><td><span class="kbd kbdw">pgup</span></td><td>Go to previous page of results</td></tr>';
			echo '<tr><td><span class="kbd kbdw">pgdn</span></td><td>Go to next page of results</td></tr>';
			echo '<tr><td><span class="kbd kbdw">end</span></td><td>Go to last page of results</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Select (single search result)</td></tr>';
			echo '<tr><th colspan="2">Miscellaneous</th></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">/</span></td><td>Show keyboard shortcuts</td></tr>';
		echo '</table>';
	echo '</div>';
echo '</div>';

cm_admin_tail();
