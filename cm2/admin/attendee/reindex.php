<?php

ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/util/res.php';
require_once __DIR__ .'/../admin.php';

if (isset($_POST['action'])) {
	header('Content-type: text/plain');
	$atdb = new cm_attendee_db($db);
	if ($_POST['action'] == 'init') {
		$_SESSION['attendee_reindex_time'] = microtime(true);
		$_SESSION['attendee_reindex_entities'] = $atdb->list_attendees();
		$_SESSION['attendee_reindex_done'] = 0;
		$_SESSION['attendee_reindex_total'] = count($_SESSION['attendee_reindex_entities']);
	}
	if ($_POST['action'] == 'drop') {
		$atdb->cm_ldb->drop_index();
	}
	if ($_POST['action'] == 'index') {
		$offset = (int)$_POST['offset'];
		$length = (int)$_POST['length'];
		$entities = array_slice($_SESSION['attendee_reindex_entities'], $offset, $length);
		foreach ($entities as $entity) {
			$atdb->cm_ldb->add_entity($entity);
			$_SESSION['attendee_reindex_done']++;
		}
	}
	$response = array(
		'ok' => true,
		'done' => $_SESSION['attendee_reindex_done'],
		'total' => $_SESSION['attendee_reindex_total'],
		'time' => microtime(true) - $_SESSION['attendee_reindex_time']
	);
	echo json_encode($response);
	if ($_POST['action'] == 'done') {
		unset($_SESSION['attendee_reindex_time']);
		unset($_SESSION['attendee_reindex_entities']);
		unset($_SESSION['attendee_reindex_done']);
		unset($_SESSION['attendee_reindex_total']);
	}
	exit(0);
}

cm_admin_head('Rebuild Attendee Search Index');
echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('cmreindex.js', false)) . '"></script>';

cm_admin_body('Rebuild Attendee Search Index');
cm_admin_nav('attendee-reindex');

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<p class="cm-warning-box">';
				echo 'Rebuilding the attendee search index takes on the order of minutes.';
			echo '</p>';
			echo '<p class="cm-error-box hidden">';
				echo '<b>DO NOT</b> leave this page while the reindexing is in progress.';
			echo '</p>';
			echo '<p class="cm-note-box hidden">';
				echo '<span class="status-label">Starting...</span>';
				echo '<span class="progress-track hidden"><span class="progress-bar"></span></span>';
				echo '<span class="progress-label hidden">0%</span>';
			echo '</p>';
			echo '<p class="cm-success-box hidden">';
				echo 'Attendee search index rebuilt in <b class="time-label">0</b> seconds.';
			echo '</p>';
			echo '<p>';
				echo '<button>Rebuild Attendee Search Index</button>';
			echo '</p>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
