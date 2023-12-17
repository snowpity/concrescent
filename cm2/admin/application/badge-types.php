<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../../lib/database/application.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
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

cm_admin_check_permission('application-badge-types-'.$ctx_lc, 'application-badge-types-'.$ctx_lc);

$apdb = new cm_application_db($db, $context);

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/application/badge-types.php?c=' . $ctx_lc,
	'entity-type' => 'badge type',
	'entity-type-pl' => 'badge types',
	'search-criteria' => 'name or description',
	'columns' => array(
		array(
			'name' => 'Name',
			'key' => 'name',
			'type' => 'text'
		),
		array(
			'name' => 'Dates Available',
			'key1' => 'start-date',
			'key2' => 'end-date',
			'type' => 'date-range'
		),
		array(
			'name' => 'Age Range',
			'key1' => 'min-age',
			'key2' => 'max-age',
			'type' => 'age-range'
		),
		array(
			'name' => '# Accepted',
			'key1' => 'applications-accepted',
			'key2' => 'applicants-accepted',
			'key3' => 'assignments-accepted',
			'term2' => 'Badges',
			'term3' => $ctx_info['assignment_term'][1],
			'type' => 'quantity3'
		),
		array(
			'name' => '# Sold',
			'key1' => 'applications-sold',
			'key2' => 'applicants-sold',
			'key3' => 'assignments-sold',
			'term2' => 'Badges',
			'term3' => $ctx_info['assignment_term'][1],
			'type' => 'quantity3'
		),
		array(
			'name' => '# Remaining',
			'key1' => 'applications-remaining',
			'key2' => 'applicants-remaining',
			'key3' => 'assignments-remaining',
			'term2' => 'Badges',
			'term3' => $ctx_info['assignment_term'][1],
			'type' => 'quantity3'
		),
		array(
			'name' => '# Total',
			'key1' => 'max-total-applications',
			'key2' => 'max-total-applicants',
			'key3' => 'max-total-assignments',
			'term2' => 'Badges',
			'term3' => $ctx_info['assignment_term'][1],
			'type' => 'quantity3'
		),
		array(
			'name' => 'Base Price',
			'key' => 'base-price',
			'type' => 'price'
		),
		array(
			'name' => 'Price / Badge',
			'key' => 'price-per-applicant',
			'type' => 'price'
		),
		array(
			'name' => 'Price / ' . $ctx_info['assignment_term'][0],
			'key' => 'price-per-assignment',
			'type' => 'price'
		),
	),
	'row-key' => 'id',
	'name-key' => 'name',
	'active-key' => 'active',
	'row-actions' => array('switch', 'edit', 'reorder', 'delete'),
	'table-actions' => array('add'),
	'add-title' => 'Add Badge Type',
	'edit-title' => 'Edit Badge Type',
	'delete-title' => 'Delete Badge Type'
);
$list_def['edit-clear-function'] = <<<END
	function() {
		$('#ea-name').val('');
		$('#ea-description').val('');
		$('#ea-rewards').val('');
		$('#ea-max-applicant-count').val('');
		$('#ea-max-assignment-count').val('');
		$('#ea-base-price').val('0.00');
		$('#ea-base-applicant-count').val(0);
		$('#ea-base-assignment-count').val(0);
		$('#ea-price-per-applicant').val('0.00');
		$('#ea-price-per-assignment').val('0.00');
		$('#ea-max-prereg-discount').val('No Discount');
		$('#ea-use-permit').prop('checked', false);
		$('#ea-require-permit').prop('checked', false);
		$('#ea-require-contract').prop('checked', false);
		$('#ea-active').prop('checked', true);
		$('#ea-max-total-applications').val('');
		$('#ea-max-total-applicants').val('');
		$('#ea-max-total-assignments').val('');
		$('#ea-start-date').val('');
		$('#ea-end-date').val('');
		$('#ea-min-age').val('');
		$('#ea-max-age').val('');
	}
END;
$list_def['edit-load-function'] = <<<END
	function(id, e) {
		$('#ea-name').val(e['name']);
		$('#ea-description').val(e['description']);
		$('#ea-rewards').val((e['rewards'] || []).join('\\n'));
		$('#ea-max-applicant-count').val(e['max-applicant-count'] || '');
		$('#ea-max-assignment-count').val(e['max-assignment-count'] || '');
		$('#ea-base-price').val(e['base-price']);
		$('#ea-base-applicant-count').val(e['base-applicant-count']);
		$('#ea-base-assignment-count').val(e['base-assignment-count']);
		$('#ea-price-per-applicant').val(e['price-per-applicant']);
		$('#ea-price-per-assignment').val(e['price-per-assignment']);
		$('#ea-max-prereg-discount').val(e['max-prereg-discount']);
		$('#ea-use-permit').prop('checked', !!e['use-permit']);
		$('#ea-require-permit').prop('checked', !!e['require-permit']);
		$('#ea-require-contract').prop('checked', !!e['require-contract']);
		$('#ea-active').prop('checked', !!e['active']);
		$('#ea-max-total-applications').val(e['max-total-applications'] || '');
		$('#ea-max-total-applicants').val(e['max-total-applicants'] || '');
		$('#ea-max-total-assignments').val(e['max-total-assignments'] || '');
		$('#ea-start-date').val(e['start-date'] || '');
		$('#ea-end-date').val(e['end-date'] || '');
		$('#ea-min-age').val(e['min-age'] || '');
		$('#ea-max-age').val(e['max-age'] || '');
	}
END;
$list_def['edit-save-function'] = <<<END
	function(id, e) {
		var rewards = $('#ea-rewards').val();
		rewards = rewards.replace(/\\r\\n/g, '\\n');
		rewards = rewards.replace(/\\r/g, '\\n');
		rewards = rewards.replace(/\\n+/g, '\\n').trim();
		rewards = rewards ? rewards.split('\\n') : [];
		return {
			'name': $('#ea-name').val(),
			'description': $('#ea-description').val(),
			'rewards': rewards,
			'max-applicant-count': $('#ea-max-applicant-count').val() || null,
			'max-assignment-count': $('#ea-max-assignment-count').val() || null,
			'base-price': $('#ea-base-price').val(),
			'base-applicant-count': $('#ea-base-applicant-count').val(),
			'base-assignment-count': $('#ea-base-assignment-count').val(),
			'price-per-applicant': $('#ea-price-per-applicant').val(),
			'price-per-assignment': $('#ea-price-per-assignment').val(),
			'max-prereg-discount': $('#ea-max-prereg-discount').val(),
			'use-permit': $('#ea-use-permit').is(':checked'),
			'require-permit': $('#ea-require-permit').is(':checked'),
			'require-contract': $('#ea-require-contract').is(':checked'),
			'active': $('#ea-active').is(':checked'),
			'max-total-applications': $('#ea-max-total-applications').val() || null,
			'max-total-applicants': $('#ea-max-total-applicants').val() || null,
			'max-total-assignments': $('#ea-max-total-assignments').val() || null,
			'start-date': $('#ea-start-date').val() || null,
			'end-date': $('#ea-end-date').val() || null,
			'min-age': $('#ea-min-age').val() || null,
			'max-age': $('#ea-max-age').val() || null
		};
	}
END;

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$badge_types = $apdb->list_badge_types();
			$response = cm_list_process_entities($list_def, $badge_types);
			echo json_encode($response);
			break;
		case 'create':
			$badge_type = json_decode($_POST['cm-list-entity'], true);
			$id = $apdb->create_badge_type($badge_type);
			$ok = ($id !== false);
			$response = array('ok' => $ok);
			if ($ok) {
				$badge_type = $apdb->get_badge_type($id);
				if ($badge_type) {
					$response['row'] = cm_list_make_row($list_def, $badge_type);
				}
			}
			echo json_encode($response);
			break;
		case 'update':
			$badge_type = json_decode($_POST['cm-list-entity'], true);
			$badge_type['id'] = $_POST['cm-list-key'];
			$ok = $apdb->update_badge_type($badge_type);
			$response = array('ok' => $ok);
			if ($ok) {
				$badge_type = $apdb->get_badge_type($badge_type['id']);
				if ($badge_type) {
					$response['row'] = cm_list_make_row($list_def, $badge_type);
				}
			}
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $apdb->delete_badge_type($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
		case 'activate':
		case 'deactivate':
			$id = $_POST['cm-list-key'];
			$ok = $apdb->activate_badge_type($id, $_POST['cm-list-action'] == 'activate');
			$response = array('ok' => $ok);
			if ($ok) {
				$badge_type = $apdb->get_badge_type($id);
				if ($badge_type) {
					$response['row'] = cm_list_make_row($list_def, $badge_type);
				}
			}
			echo json_encode($response);
			break;
		case 'reorder':
			$id = $_POST['cm-list-key'];
			$ok = $apdb->reorder_badge_type($id, (int)$_POST['cm-list-reorder-direction']);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head($ctx_name . ' Badge Types');
cm_list_head($list_def);
cm_admin_body($ctx_name . ' Badge Types');
cm_admin_nav('application-badge-types-' . $ctx_lc);

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_edit_dialog_start();

echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
	echo '<tr>';
		echo '<th><label for="ea-name">Name:</label></th>';
		echo '<td><input type="text" name="ea-name" id="ea-name"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-description">Description:</label></th>';
		echo '<td><textarea name="ea-description" id="ea-description"></textarea></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-rewards">Rewards:</label></th>';
		echo '<td>';
			echo '<textarea name="ea-rewards" id="ea-rewards"></textarea>';
			echo '<br>(Enter one reward per line. Do not add bullets or hyphens.)';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-max-assignment-count">Max ' . $ctx_info['assignment_term'][1] . '<br>Requestable:</label></th>';
		echo '<td><input type="number" name="ea-max-assignment-count" id="ea-max-assignment-count" min="1" max="999">';
	echo '</tr>';
	echo '<tr>';
		echo '<th></th>';
		echo '<td>';
			echo (
				'(Leave blank or set to 0 for no maximum. Set to 1 for ' . strtolower($ctx_info['assignment_term'][1]) .
				' to not appear on the application. In addition, set Price Per ' . $ctx_info['assignment_term'][0] . ' to 0 for ' .
				strtolower($ctx_info['assignment_term'][1]) . ' to not appear in the shopping cart during confirmation and payment.)'
			);
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-max-applicant-count">Max Badges<br>Requestable:</label></th>';
		echo '<td><input type="number" name="ea-max-applicant-count" id="ea-max-applicant-count" min="1" max="999">';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-base-price">Base Price:</label></th>';
		echo '<td><input type="number" name="ea-base-price" id="ea-base-price" min="0" step="0.01"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-base-assignment-count">' . $ctx_info['assignment_term'][1] . ' Included<br> in Base Price:</label></th>';
		echo '<td><input type="number" name="ea-base-assignment-count" id="ea-base-assignment-count" min="0" max="999">';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-base-applicant-count">Badges Included<br> in ' . $ctx_info['assignment_term'][0] . ' Price:</label></th>';
		echo '<td><input type="number" name="ea-base-applicant-count" id="ea-base-applicant-count" min="0" max="999">';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-price-per-assignment">Price Per ' . $ctx_info['assignment_term'][0] . ':</label></th>';
		echo '<td><input type="number" name="ea-price-per-assignment" id="ea-price-per-assignment" min="0" step="0.01"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-price-per-applicant">Price Per Badge:</label></th>';
		echo '<td><input type="number" name="ea-price-per-applicant" id="ea-price-per-applicant" min="0" step="0.01"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-max-prereg-discount">Max Discount for<br>Already Registered:</label></th>';
		echo '<td>';
			echo '<select name="ea-max-prereg-discount" id="ea-max-prereg-discount">';
				echo '<option value="No Discount">No Discount</option>';
				echo '<option value="Price per Applicant">Price Per Badge</option>';
				echo '<option value="Price per Assignment">Price Per ' . $ctx_info['assignment_term'][0] . '</option>';
				echo '<option value="Total Price">Total Payment Amount</option>';
			echo '</select>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-use-permit">Use Permit:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-use-permit" id="ea-use-permit">Applicants may have a seller\'s permit.</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-require-permit">Require Permit:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-require-permit" id="ea-require-permit">Applicants are required to have a seller\'s permit.</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-require-contract">Require Contract:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-require-contract" id="ea-require-contract">Application terms and payment must be resolved offline.</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-active">Active:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-active" id="ea-active">Active</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-max-total-applications">Max Applications<br>Available:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-max-total-applications" id="ea-max-total-applications" min="1">';
			echo '&nbsp;&nbsp;(Leave blank for unlimited.)';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-max-total-applicants">Max Badges<br>Available:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-max-total-applicants" id="ea-max-total-applicants" min="1">';
			echo '&nbsp;&nbsp;(Leave blank for unlimited.)';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-max-total-assignments">Max ' . $ctx_info['assignment_term'][1] . '<br>Available:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-max-total-assignments" id="ea-max-total-assignments" min="1">';
			echo '&nbsp;&nbsp;(Leave blank for unlimited.)';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-start-date">Dates Available:</label></th>';
		echo '<td>';
			echo '<input type="date" name="ea-start-date" id="ea-start-date">';
			echo '&nbsp;&nbsp;through&nbsp;&nbsp;';
			echo '<input type="date" name="ea-end-date" id="ea-end-date">';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-min-age">Age Range:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-min-age" id="ea-min-age" min="1" max="999">';
			echo '&nbsp;&nbsp;through&nbsp;&nbsp;';
			echo '<input type="number" name="ea-max-age" id="ea-max-age" min="1" max="999">';
		echo '</td>';
	echo '</tr>';
echo '</table>';

cm_list_edit_dialog_end();
cm_list_dialogs($list_def);
cm_admin_tail();
