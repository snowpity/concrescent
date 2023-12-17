<?php

require_once __DIR__ .'/../../lib/database/badge-artwork.php';
require_once __DIR__ .'/../../lib/database/badge-holder.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('badge-preprinting', 'badge-preprinting');

$badb = new cm_badge_artwork_db($db);
$bhdb = new cm_badge_holder_db($db);

$list_def = array(
	'columns' => array(
		array(
			'name' => 'ID',
			'key' => 'id-string',
			'type' => 'text'
		),
		array(
			'name' => 'Name',
			'key' => 'display-name',
			'type' => 'text'
		),
		array(
			'name' => 'App. Status',
			'key' => 'application-status',
			'type' => 'status-label'
		),
		array(
			'name' => 'Pmt. Status',
			'key' => 'payment-status',
			'type' => 'status-label'
		),
	),
	'row-key' => 'badge-holder-id-string',
	'row-actions' => array('select')
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

$badges = $bhdb->list_badge_type_names();
$artwork = $badb->list_badge_artwork();

cm_admin_head('Badge Pre-Printing');

echo '<link rel="stylesheet" href="preprinting.css">';

echo '<script type="text/javascript">cm_badge_artwork = (' . json_encode($artwork) . ');</script>';
echo '<script type="text/javascript" src="preprinting.js"></script>';

cm_admin_body('Badge Pre-Printing');
cm_admin_nav('badge-preprinting');

echo '<article class="badge-preprinting">';

echo '<div class="card badge-preprinting-types">';
	echo '<div class="card-title">Badge Types</div>';
	echo '<div class="card-content">';
		echo '<p>';
			echo 'Select a badge type and "Paid", "Accepted", or "All" ';
			echo 'to get a list of badge holders who have paid for, ';
			echo 'been accepted for, or registered for a badge, ';
			echo 'respectively.';
		echo '</p>';
		echo '<div class="spacing">';
			echo '<label><input type="radio" name="criteria" value="paid" checked>Paid</label>';
			echo '<label><input type="radio" name="criteria" value="accepted">Accepted</label>';
			echo '<label><input type="radio" name="criteria" value="all">All</label>';
		echo '</div>';
		echo '<div class="spacing">';
			foreach ($badges as $badge) {
				$value = htmlspecialchars('badge-type-' . $badge['context'] . '-' . $badge['context-id']);
				echo '<label title="' . htmlspecialchars($badge['id-string']) . '">';
					echo '<input type="radio" name="badge-type" value="' . $value . '"';
					echo ' data-context="' . htmlspecialchars($badge['context']) . '"';
					echo ' data-context-id="' . htmlspecialchars($badge['context-id']) . '">';
					echo htmlspecialchars($badge['name']);
				echo '</label>';
			}
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="card badge-preprinting-holders">';
	echo '<div class="card-title">Badge Holders</div>';
	echo '<div class="card-content">';
		echo '<p>';
			echo 'Select a badge holder to get the list of ';
			echo 'possible designs for their badge.';
		echo '</p>';
		echo '<div class="cm-list-table">';
			echo '<table border="0" cellpadding="0" cellspacing="0">';
				echo '<thead>';
					echo '<th>ID</th>';
					echo '<th>Name</th>';
					echo '<th>App. Status</th>';
					echo '<th>Pmt. Status</th>';
					echo '<th class="td-actions">Select</th>';
				echo '</thead>';
				echo '<tbody class="badge-holders-tbody">';
				echo '</tbody>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="card badge-preprinting-print">';
	echo '<div class="card-title">Print Badge</div>';
	echo '<div class="card-content">';
		echo '<p>';
			echo 'Click a badge design to print the badge.';
		echo '</p>';
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
echo '</div>';

echo '</article>';

cm_admin_dialogs();
cm_admin_tail();
