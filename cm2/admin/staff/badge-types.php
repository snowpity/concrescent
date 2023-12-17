<?php

require_once __DIR__ .'/../../lib/database/staff.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('staff-badge-types', 'staff-badge-types');

$sdb = new cm_staff_db($db);

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/staff/badge-types.php',
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
			'key' => 'quantity-accepted',
			'type' => 'quantity'
		),
		array(
			'name' => '# Sold',
			'key' => 'quantity-sold',
			'type' => 'quantity'
		),
		array(
			'name' => '# Remaining',
			'key' => 'quantity-remaining',
			'type' => 'quantity'
		),
		array(
			'name' => '# Total',
			'key' => 'quantity',
			'type' => 'quantity'
		),
		array(
			'name' => 'Price',
			'key' => 'price',
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
		$('#ea-price').val('0.00');
		$('#ea-active').prop('checked', true);
		$('#ea-quantity').val('');
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
		$('#ea-price').val(e['price']);
		$('#ea-active').prop('checked', !!e['active']);
		$('#ea-quantity').val(e['quantity'] || '');
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
			'price': $('#ea-price').val(),
			'active': $('#ea-active').is(':checked'),
			'quantity': $('#ea-quantity').val() || null,
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
			$badge_types = $sdb->list_badge_types();
			$response = cm_list_process_entities($list_def, $badge_types);
			echo json_encode($response);
			break;
		case 'create':
			$badge_type = json_decode($_POST['cm-list-entity'], true);
			$id = $sdb->create_badge_type($badge_type);
			$ok = ($id !== false);
			$response = array('ok' => $ok);
			if ($ok) {
				$badge_type = $sdb->get_badge_type($id);
				if ($badge_type) {
					$response['row'] = cm_list_make_row($list_def, $badge_type);
				}
			}
			echo json_encode($response);
			break;
		case 'update':
			$badge_type = json_decode($_POST['cm-list-entity'], true);
			$badge_type['id'] = $_POST['cm-list-key'];
			$ok = $sdb->update_badge_type($badge_type);
			$response = array('ok' => $ok);
			if ($ok) {
				$badge_type = $sdb->get_badge_type($badge_type['id']);
				if ($badge_type) {
					$response['row'] = cm_list_make_row($list_def, $badge_type);
				}
			}
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $sdb->delete_badge_type($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
		case 'activate':
		case 'deactivate':
			$id = $_POST['cm-list-key'];
			$ok = $sdb->activate_badge_type($id, $_POST['cm-list-action'] == 'activate');
			$response = array('ok' => $ok);
			if ($ok) {
				$badge_type = $sdb->get_badge_type($id);
				if ($badge_type) {
					$response['row'] = cm_list_make_row($list_def, $badge_type);
				}
			}
			echo json_encode($response);
			break;
		case 'reorder':
			$id = $_POST['cm-list-key'];
			$ok = $sdb->reorder_badge_type($id, (int)$_POST['cm-list-reorder-direction']);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Staff Badge Types');
cm_list_head($list_def);
cm_admin_body('Staff Badge Types');
cm_admin_nav('staff-badge-types');

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
		echo '<th><label for="ea-price">Price:</label></th>';
		echo '<td><input type="number" name="ea-price" id="ea-price" min="0" step="0.01"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-active">Active:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-active" id="ea-active">Active</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-quantity">Quantity Available:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-quantity" id="ea-quantity" min="1">';
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
