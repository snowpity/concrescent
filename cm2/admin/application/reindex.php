<?php

ini_set('max_execution_time', 300);
ini_set('memory_limit', '1024M');

require_once __DIR__ .'/../../lib/database/application.php';
require_once __DIR__ .'/../../lib/util/res.php';
require_once __DIR__ .'/../admin.php';

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

if (isset($_POST['action'])) {
	header('Content-type: text/plain');
	$apdb = new cm_application_db($db, $context);
	if ($_POST['action'] == 'init') {
		$_SESSION['app_reindex_time_'.$ctx_lc] = microtime(true);
		$_SESSION['app_reindex_entities_'.$ctx_lc] = array_merge(
			$apdb->list_applications(null, null, true),
			$apdb->list_applicants(null, true)
		);
		$_SESSION['app_reindex_done_'.$ctx_lc] = 0;
		$_SESSION['app_reindex_total_'.$ctx_lc] = count($_SESSION['app_reindex_entities_'.$ctx_lc]);
	}
	if ($_POST['action'] == 'drop') {
		$apdb->cm_anldb->drop_index();
		$apdb->cm_atldb->drop_index();
	}
	if ($_POST['action'] == 'index') {
		$offset = (int)$_POST['offset'];
		$length = (int)$_POST['length'];
		$entities = array_slice($_SESSION['app_reindex_entities_'.$ctx_lc], $offset, $length);
		foreach ($entities as $entity) {
			switch ($entity['type']) {
				case 'application':
					$apdb->cm_anldb->add_entity($entity);
					break;
				case 'applicant':
					$apdb->cm_atldb->add_entity($entity);
					break;
			}
			$_SESSION['app_reindex_done_'.$ctx_lc]++;
		}
	}
	$response = array(
		'ok' => true,
		'done' => $_SESSION['app_reindex_done_'.$ctx_lc],
		'total' => $_SESSION['app_reindex_total_'.$ctx_lc],
		'time' => microtime(true) - $_SESSION['app_reindex_time_'.$ctx_lc]
	);
	echo json_encode($response);
	if ($_POST['action'] == 'done') {
		unset($_SESSION['app_reindex_time_'.$ctx_lc]);
		unset($_SESSION['app_reindex_entities_'.$ctx_lc]);
		unset($_SESSION['app_reindex_done_'.$ctx_lc]);
		unset($_SESSION['app_reindex_total_'.$ctx_lc]);
	}
	exit(0);
}

cm_admin_head('Rebuild '.$ctx_name.' Search Index');
echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('cmreindex.js', false)) . '"></script>';
cm_admin_body('Rebuild '.$ctx_name.' Search Index');
cm_admin_nav('application-reindex-'.$ctx_lc);

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<p class="cm-warning-box">';
				echo 'Rebuilding the '.$ctx_name_lc.' search index takes on the order of minutes.';
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
				echo $ctx_name.' search index rebuilt in <b class="time-label">0</b> seconds.';
			echo '</p>';
			echo '<p>';
				echo '<button>Rebuild '.$ctx_name.' Search Index</button>';
			echo '</p>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
