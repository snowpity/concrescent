<?php

require_once __DIR__ .'/../../lib/database/badge-artwork.php';
require_once __DIR__ .'/../../lib/util/util.php';
require_once __DIR__ .'/../../lib/util/cmlists.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('badge-artwork', 'badge-artwork');

$badb = new cm_badge_artwork_db($db);

$list_def = array(
	'ajax-url' => get_site_url(false) . '/admin/badge/artwork.php',
	'entity-type' => 'badge artwork',
	'entity-type-pl' => 'badge artwork',
	'search-criteria' => 'file name',
	'columns' => array(
		array(
			'name' => 'File Name',
			'key' => 'file-name',
			'type' => 'text'
		),
	),
	'sort-order' => array(0),
	'row-key' => 'file-name',
	'name-key' => 'file-name',
	'row-actions' => array('edit', 'delete'),
	'table-actions' => array('add'),
	'add-label' => 'Upload',
	'edit-url' => get_site_url(false) . '/admin/badge/artwork-edit.php?name=',
	'add-title' => 'Upload Badge Artwork',
	'delete-title' => 'Delete Badge Artwork'
);

if (isset($_POST['cm-list-action'])) {
	header('Content-type: text/plain');
	switch ($_POST['cm-list-action']) {
		case 'list':
			$artwork = $badb->list_badge_artwork();
			$response = cm_list_process_entities($list_def, $artwork);
			echo json_encode($response);
			break;
		case 'delete':
			$id = $_POST['cm-list-key'];
			$ok = $badb->delete_badge_artwork($id);
			$response = array('ok' => $ok);
			echo json_encode($response);
			break;
	}
	exit(0);
}

$submitted = isset($_POST['cm-upload-submit']);
if ($submitted) {
	$image_file = ($_FILES['image-file'] ?? null);
	if (!$image_file || (isset($image_file['error']) && $image_file['error'])) {
		$message = 'Error uploading image. Please try again with a different image.';
		$success = false;
	} else {
		$image_path = ($image_file['tmp_name'] ?? null);
		$image_size = ($image_path ? getimagesize($image_path) : null);
		$image_w = (($image_size && $image_size[0]) ? $image_size[0] : null);
		$image_h = (($image_size && $image_size[1]) ? $image_size[1] : null);
		$image_type = ($image_path ? exif_imagetype($image_path) : null);
		$mime_type = ($image_type ? image_type_to_mime_type($image_type) : null);
		$file_name = (isset($_POST['file-name']) ? trim($_POST['file-name']) : null);
		if (!$file_name) $file_name = (isset($image_file['name']) ? trim($image_file['name']) : null);
		if ($badb->upload_badge_artwork($file_name, $mime_type, $image_w, $image_h, $image_path)) {
			$message = 'Image upload succeeded.';
			$success = true;

			$badb->delete_badge_artwork_fields($file_name);

			$fields_file = ($_FILES['fields-file'] ?? null);
			if ($fields_file && (!isset($fields_file['error']) || !$fields_file['error'])) {
				$fields_path = ($fields_file['tmp_name'] ?? null);
				if ($fields_path) $badb->upload_badge_artwork_fields($file_name, $fields_path);
			}

			$copy_from = (isset($_POST['copy-from']) ? trim($_POST['copy-from']) : null);
			if ($copy_from) $badb->copy_badge_artwork_fields($copy_from, $file_name);
		} else {
			$message = 'Error uploading image. Please try again with a different image.';
			$success = false;
		}
	}
}

cm_admin_head('Badge Artwork');
cm_list_head($list_def);
cm_admin_body('Badge Artwork');
cm_admin_nav('badge-artwork');

echo '<article class="cm-search-page">';

cm_list_search_box($list_def);

if ($submitted) {
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<p class="' . ($success ? 'cm-success-box' : 'cm-error-box') . '">';
				echo htmlspecialchars($message);
			echo '</p>';
		echo '</div>';
	echo '</div>';
}

cm_list_table($list_def);

echo '</article>';

cm_admin_dialogs();

echo '<form action="artwork.php" method="post" enctype="multipart/form-data" class="dialog edit-dialog hidden">';
	echo '<div class="dialog-title">Upload Badge Artwork</div>';
	echo '<div class="dialog-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
			echo '<tr>';
				echo '<th><label for="file-name">File Name:</label></th>';
				echo '<td><input type="text" id="file-name" name="file-name"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th></th>';
				echo '<td>(Optional. If left blank, the name of the image file will be used.)</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="image-file">Image File:</label></th>';
				echo '<td><input type="file" id="image-file" name="image-file"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th></th>';
				echo '<td>(Required. Select an image file containing badge artwork.)</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="fields-file">Layout File:</label></th>';
				echo '<td><input type="file" id="fields-file" name="fields-file"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th></th>';
				echo '<td>(Optional. Select a CSV file containing field layout.)</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="copy-from">Copy From:</label></th>';
				echo '<td>';
					echo '<select id="copy-from" name="copy-from">';
						echo '<option value="">(none)</option>';
						$artwork = $badb->list_badge_artwork();
						if ($artwork) {
							foreach ($artwork as $ba) {
								$hba = htmlspecialchars($ba['file-name']);
								echo '<option value="' . $hba . '">' . $hba . '</option>';
							}
						}
					echo '</select>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th></th>';
				echo '<td>(Optional. Select existing badge artwork to copy layout from.)</td>';
			echo '</tr>';
		echo '</table>';
	echo '</div>';
	echo '<div class="dialog-buttons">';
		echo '<input type="reset" class="cancel-edit-button" value="Cancel">';
		echo '<input type="submit" name="cm-upload-submit" value="Upload">';
	echo '</div>';
echo '</form>';

cm_list_dialogs($list_def);
cm_admin_tail();
