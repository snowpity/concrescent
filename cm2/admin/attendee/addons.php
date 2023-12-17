<?php

require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('attendee-addons', 'attendee-addons');

$atdb = new cm_attendee_db($db);
$name_map = $atdb->get_badge_type_name_map();

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/attendee/addons.php',
	'entity-type' => 'addon',
	'entity-type-pl' => 'addons',
	'search-criteria' => 'name or description',
	'columns' => array(
		array(
			'name' => 'Name',
			'key' => 'name',
			'type' => 'text'
		),
		array(
			'name' => 'Valid For',
			'key' => 'badge-type-names',
			'type' => 'array-short'
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
	'add-title' => 'Add Addon',
	'edit-title' => 'Edit Addon',
	'delete-title' => 'Delete Addon'
);
$list_def['edit-clear-function'] = <<<END
	function() {
		$('#ea-name').val('');
		$('#ea-description').val('');
		$('#ea-price').val('0.00');
		$('#ea-payable-onsite').prop('checked', false);
		$('#ea-active').prop('checked', true);
		$('.ea-badge-types').prop('checked', false);
		$('.ea-badge-types-all').prop('checked', true);
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
		$('#ea-price').val(e['price']);
		$('#ea-payable-onsite').prop('checked', !!e['payable-onsite']);
		$('#ea-active').prop('checked', !!e['active']);
		$('.ea-badge-types').each(function() {
			var id = $(this).attr('id').substring(15);
			$(this).prop('checked', e['badge-type-ids'].indexOf(id) >= 0);
		});
		$('#ea-quantity').val(e['quantity'] || '');
		$('#ea-start-date').val(e['start-date'] || '');
		$('#ea-end-date').val(e['end-date'] || '');
		$('#ea-min-age').val(e['min-age'] || '');
		$('#ea-max-age').val(e['max-age'] || '');
	}
END;
$list_def['edit-save-function'] = <<<END
	function(id, e) {
		var ne = {
			'name': $('#ea-name').val(),
			'description': $('#ea-description').val(),
			'price': $('#ea-price').val(),
			'payable-onsite': $('#ea-payable-onsite').is(':checked'),
			'active': $('#ea-active').is(':checked'),
			'badge-type-ids': [],
			'quantity': $('#ea-quantity').val() || null,
			'start-date': $('#ea-start-date').val() || null,
			'end-date': $('#ea-end-date').val() || null,
			'min-age': $('#ea-min-age').val() || null,
			'max-age': $('#ea-max-age').val() || null
		};
		$('.ea-badge-types').each(function() {
			if ($(this).is(':checked')) {
				var id = $(this).attr('id').substring(15);
				ne['badge-type-ids'].push(id);
			}
		});
		return ne;
	}
END;

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$addons = $atdb->list_addons(false, false, false, $name_map);
			$response = cm_list_process_entities($list_def, $addons);
			echo json_encode($response);
			break;
		case 'create':
			$addon = json_decode($_POST['cm-list-entity'], true);
			$id = $atdb->create_addon($addon);
			$ok = ($id !== false);
			$response = array('ok' => $ok);
			if ($ok) {
				$addon = $atdb->get_addon($id, $name_map);
				if ($addon) {
					$response['row'] = cm_list_make_row($list_def, $addon);
				}
			}
			echo json_encode($response);
			break;
		case 'update':
			$addon = json_decode($_POST['cm-list-entity'], true);
			$addon['id'] = $_POST['cm-list-key'];
			$ok = $atdb->update_addon($addon);
			$response = array('ok' => $ok);
			if ($ok) {
				$addon = $atdb->get_addon($addon['id'], $name_map);
				if ($addon) {
					$response['row'] = cm_list_make_row($list_def, $addon);
				}
			}
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $atdb->delete_addon($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
		case 'activate':
		case 'deactivate':
			$id = $_POST['cm-list-key'];
			$ok = $atdb->activate_addon($id, $_POST['cm-list-action'] == 'activate');
			$response = array('ok' => $ok);
			if ($ok) {
				$addon = $atdb->get_addon($id, $name_map);
				if ($addon) {
					$response['row'] = cm_list_make_row($list_def, $addon);
				}
			}
			echo json_encode($response);
			break;
		case 'reorder':
			$id = $_POST['cm-list-key'];
			$ok = $atdb->reorder_addon($id, (int)$_POST['cm-list-reorder-direction']);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Attendee Addons');
cm_list_head($list_def);
cm_admin_body('Attendee Addons');
cm_admin_nav('attendee-addons');

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
		echo '<th><label for="ea-price">Price:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-price" id="ea-price" min="0" step="0.01">&nbsp;&nbsp;';
			echo '<label><input type="checkbox" name="ea-payable-onsite" id="ea-payable-onsite">Payable On-Site</label>';
		echo '</td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-active">Active:</label></th>';
		echo '<td><label><input type="checkbox" name="ea-active" id="ea-active">Active</label></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th class="th-tall"><label>Badge Types:</label></th>';
		echo '<td>';
			foreach ($name_map as $id => $name) {
				echo '<div><label><input type="checkbox"';
				echo ' name="ea-badge-types-' . $id . '"';
				echo ' id="ea-badge-types-' . $id . '"';
				echo ' class="ea-badge-types">';
				echo htmlspecialchars($name);
				echo '</label></div>';
			}
			echo '<div><label><input type="checkbox"';
			echo ' name="ea-badge-types-*"';
			echo ' id="ea-badge-types-*"';
			echo ' class="ea-badge-types ea-badge-types-all">';
			echo 'ALL';
			echo '</label></div>';
		echo '</td>';
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
