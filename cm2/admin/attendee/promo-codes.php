<?php

require_once __DIR__ .'/../../lib/database/attendee.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('attendee-promo-codes', 'attendee-promo-codes');

$atdb = new cm_attendee_db($db);
$name_map = $atdb->get_badge_type_name_map();

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/attendee/promo-codes.php',
	'entity-type' => 'promo code',
	'entity-type-pl' => 'promo codes',
	'search-criteria' => 'code or description',
	'columns' => array(
		array(
			'name' => 'Code',
			'key' => 'code',
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
			'name' => '# Used',
			'key' => 'quantity-used',
			'type' => 'quantity'
		),
		array(
			'name' => 'Limit Per Customer',
			'key' => 'limit-per-customer',
			'type' => 'quantity'
		),
		array(
			'name' => 'Discount',
			'key' => 'price-html',
			'type' => 'html-numeric'
		),
	),
	'sort-order' => array(0),
	'row-key' => 'id',
	'name-key' => 'code',
	'active-key' => 'active',
	'row-actions' => array('switch', 'edit', 'delete'),
	'table-actions' => array('add'),
	'add-title' => 'Add Promo Code',
	'edit-title' => 'Edit Promo Code',
	'delete-title' => 'Delete Promo Code'
);
$list_def['edit-clear-function'] = <<<END
	function() {
		$('#ea-code').val('');
		$('#ea-description').val('');
		$('#ea-price').val('0.00');
		$('#ea-percentage').prop('checked', false);
		$('#ea-active').prop('checked', true);
		$('.ea-badge-types').prop('checked', false);
		$('.ea-badge-types-all').prop('checked', true);
		$('#ea-limit-per-customer').val('');
		$('#ea-start-date').val('');
		$('#ea-end-date').val('');
	}
END;
$list_def['edit-load-function'] = <<<END
	function(id, e) {
		$('#ea-code').val(e['code']);
		$('#ea-description').val(e['description']);
		$('#ea-price').val(e['price']);
		$('#ea-percentage').prop('checked', !!e['percentage']);
		$('#ea-active').prop('checked', !!e['active']);
		$('.ea-badge-types').each(function() {
			var id = $(this).attr('id').substring(15);
			$(this).prop('checked', e['badge-type-ids'].indexOf(id) >= 0);
		});
		$('#ea-limit-per-customer').val(e['limit-per-customer'] || '');
		$('#ea-start-date').val(e['start-date'] || '');
		$('#ea-end-date').val(e['end-date'] || '');
	}
END;
$list_def['edit-save-function'] = <<<END
	function(id, e) {
		var ne = {
			'code': $('#ea-code').val(),
			'description': $('#ea-description').val(),
			'price': $('#ea-price').val(),
			'percentage': $('#ea-percentage').is(':checked'),
			'active': $('#ea-active').is(':checked'),
			'badge-type-ids': [],
			'limit-per-customer': $('#ea-limit-per-customer').val() || null,
			'start-date': $('#ea-start-date').val() || null,
			'end-date': $('#ea-end-date').val() || null
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
			$promo_codes = $atdb->list_promo_codes($name_map);
			$response = cm_list_process_entities($list_def, $promo_codes);
			echo json_encode($response);
			break;
		case 'create':
			$promo_code = json_decode($_POST['cm-list-entity'], true);
			$id = $atdb->create_promo_code($promo_code);
			$ok = ($id !== false);
			$response = array('ok' => $ok);
			if ($ok) {
				$promo_code = $atdb->get_promo_code($id, false, false, $name_map);
				if ($promo_code) {
					$response['row'] = cm_list_make_row($list_def, $promo_code);
				}
			}
			echo json_encode($response);
			break;
		case 'update':
			$promo_code = json_decode($_POST['cm-list-entity'], true);
			$promo_code['id'] = $_POST['cm-list-key'];
			$ok = $atdb->update_promo_code($promo_code);
			$response = array('ok' => $ok);
			if ($ok) {
				$promo_code = $atdb->get_promo_code($promo_code['id'], false, false, $name_map);
				if ($promo_code) {
					$response['row'] = cm_list_make_row($list_def, $promo_code);
				}
			}
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $atdb->delete_promo_code($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
		case 'activate':
		case 'deactivate':
			$id = $_POST['cm-list-key'];
			$ok = $atdb->activate_promo_code($id, $_POST['cm-list-action'] == 'activate');
			$response = array('ok' => $ok);
			if ($ok) {
				$promo_code = $atdb->get_promo_code($id, false, false, $name_map);
				if ($promo_code) {
					$response['row'] = cm_list_make_row($list_def, $promo_code);
				}
			}
			echo json_encode($response);
			break;
	}
	exit(0);
}

cm_admin_head('Attendee Promo Codes');
cm_list_head($list_def);
cm_admin_body('Attendee Promo Codes');
cm_admin_nav('attendee-promo-codes');

echo '<article class="cm-search-page">';
cm_list_search_box($list_def);
cm_list_table($list_def);
echo '</article>';

cm_admin_dialogs();
cm_list_edit_dialog_start();

echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
	echo '<tr>';
		echo '<th><label for="ea-code">Code:</label></th>';
		echo '<td><input type="text" name="ea-code" id="ea-code"></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-description">Description:</label></th>';
		echo '<td><textarea name="ea-description" id="ea-description"></textarea></td>';
	echo '</tr>';
	echo '<tr>';
		echo '<th><label for="ea-price">Discount:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-price" id="ea-price" min="0" step="0.01">&nbsp;&nbsp;';
			echo '<label><input type="checkbox" name="ea-percentage" id="ea-percentage">Percentage</label>';
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
		echo '<th><label for="ea-limit-per-customer">Limit Per Customer:</label></th>';
		echo '<td>';
			echo '<input type="number" name="ea-limit-per-customer" id="ea-limit-per-customer" min="1">';
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
echo '</table>';

cm_list_edit_dialog_end();
cm_list_dialogs($list_def);
cm_admin_tail();
