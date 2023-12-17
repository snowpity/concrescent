<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/../../lib/database/badge-artwork.php';
require_once dirname(__FILE__).'/../../lib/database/badge-holder.php';
require_once dirname(__FILE__).'/../admin.php';

cm_admin_check_permission('badge-artwork', 'badge-artwork');

$bp_config = $cm_config['badge_printing'];
$badb = new cm_badge_artwork_db($db);
$bhdb = new cm_badge_holder_db($db);

$file_name = isset($_GET['name']) ? trim($_GET['name']) : null;
if (!$file_name) {
	header('Location: artwork.php');
	exit(0);
}

$message = null;
$success = null;

if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'Upload Image':
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
				if ($badb->upload_badge_artwork($file_name, $mime_type, $image_w, $image_h, $image_path)) {
					$message = 'Image upload succeeded.';
					$success = true;
				} else {
					$message = 'Error uploading image. Please try again with a different image.';
					$success = false;
				}
			}
			break;
		case 'Download Image':
			if (!$badb->download_badge_artwork($file_name, true)) {
				if (!strrpos($file_name, '.')) $file_name .= '.png';
				header('Content-Disposition: attachment; filename=' . $file_name);
				header('Content-Type: image/png');
				header('Pragma: no-cache');
				header('Expires: 0');
				$image = imagecreate(300, 200);
				$bg = imagecolorallocate($image, 255, 255, 255);
				imagefilledrectangle($image, 0, 0, 300, 200, $bg);
				$fg = imagecolorallocate($image, 0, 0, 255);
				imagestring($image, 5, (300-9*21)/2, 200/2-8-12, 'Could not load image.', $fg);
				imagestring($image, 5, (300-9*24)/2, 200/2-8+12, 'Please upload a new one.', $fg);
				imagepng($image);
				imagedestroy($image);
			}
			exit(0);
		case 'Upload Layout':
			$fields_file = ($_FILES['fields-file'] ?? null);
			if (!$fields_file || (isset($fields_file['error']) && $fields_file['error'])) {
				$message = 'Error uploading layout. Please try again with a different file.';
				$success = false;
			} else {
				$fields_path = ($fields_file['tmp_name'] ?? null);
				if ($fields_path) {
					if ($_POST['import-mode'] == 'replace') $badb->delete_badge_artwork_fields($file_name);
					if ($badb->upload_badge_artwork_fields($file_name, $fields_path)) {
						$message = 'Layout upload succeeded.';
						$success = true;
					} else {
						$message = 'Error uploading layout. Please try again with a different file.';
						$success = false;
					}
				} else {
					$message = 'Error uploading layout. Please try again with a different file.';
					$success = false;
				}
			}
			break;
		case 'Download Layout':
			$badb->download_badge_artwork_fields($file_name);
			exit(0);
		case 'Copy Layout':
			$copy_from = (isset($_POST['copy-from']) ? trim($_POST['copy-from']) : null);
			if (!$copy_from) {
				$message = 'Error copying layout. Please try again with a different layout.';
				$success = false;
			} else {
				if ($_POST['import-mode'] == 'replace') $badb->delete_badge_artwork_fields($file_name);
				if ($badb->copy_badge_artwork_fields($copy_from, $file_name)) {
					$message = 'Layout copy succeeded.';
					$success = true;
				} else {
					$message = 'Error copying layout. Please try again with a different layout.';
					$success = false;
				}
			}
			break;
		case 'Delete Layout':
			if ($badb->delete_badge_artwork_fields($file_name)) {
				$message = 'Layout deleted.';
				$success = true;
			} else {
				$message = 'An error occurred. Please try again.';
				$success = false;
			}
			break;
		case 'Save Applicable Badge Types':
			$badb->clear_badge_artwork_map(null, null, $file_name);
			foreach ($_POST as $k => $v) {
				if (substr($k, 0, 11) == 'badge-type-' && $v) {
					$k = substr($k, 11);
					$o = strrpos($k, '-');
					$context = substr($k, 0, $o);
					$context_id = (int)substr($k, $o + 1);
					$badb->set_badge_artwork_map($context, $context_id, $file_name);
				}
			}
			$message = 'Changes saved.';
			$success = true;
			break;
		case 'list-fields':
			header('Content-type: text/plain');
			$fields = $badb->list_badge_artwork_fields($file_name);
			$response = array('ok' => true, 'fields' => $fields);
			echo json_encode($response);
			exit(0);
		case 'create-field':
			header('Content-type: text/plain');
			$ok = $badb->create_badge_artwork_field(array(
				'file-name' => $file_name,
				'x1' => (float)$_POST['x1'],
				'y1' => (float)$_POST['y1'],
				'x2' => (float)$_POST['x2'],
				'y2' => (float)$_POST['y2'],
				'field-key' => trim($_POST['field-key']),
				'font-size' => (int)$_POST['font-size'],
				'font-family' => trim($_POST['font-family']),
				'font-weight-bold' => !!$_POST['font-weight-bold'],
				'font-style-italic' => !!$_POST['font-style-italic'],
				'color' => trim($_POST['color']),
				'background' => trim($_POST['background']),
				'color-minors' => trim($_POST['color-minors']),
				'background-minors' => trim($_POST['background-minors'])
			));
			$response = array('ok' => $ok);
			echo json_encode($response);
			exit(0);
		case 'update-field':
			header('Content-type: text/plain');
			$ok = $badb->update_badge_artwork_field(array(
				'id' => (int)$_POST['id'],
				'file-name' => $file_name,
				'x1' => (float)$_POST['x1'],
				'y1' => (float)$_POST['y1'],
				'x2' => (float)$_POST['x2'],
				'y2' => (float)$_POST['y2'],
				'field-key' => trim($_POST['field-key']),
				'font-size' => (int)$_POST['font-size'],
				'font-family' => trim($_POST['font-family']),
				'font-weight-bold' => !!$_POST['font-weight-bold'],
				'font-style-italic' => !!$_POST['font-style-italic'],
				'color' => trim($_POST['color']),
				'background' => trim($_POST['background']),
				'color-minors' => trim($_POST['color-minors']),
				'background-minors' => trim($_POST['background-minors'])
			));
			$response = array('ok' => $ok);
			echo json_encode($response);
			exit(0);
		case 'delete-field':
			header('Content-type: text/plain');
			$ok = $badb->delete_badge_artwork_field((int)$_POST['id']);
			$response = array('ok' => $ok);
			echo json_encode($response);
			exit(0);
	}
}

$artwork = $badb->get_badge_artwork($file_name);
if (!$artwork) {
	header('Location: artwork.php');
	exit(0);
}

$action_url = 'artwork-edit.php?name=' . urlencode($file_name);
$action_url_html = htmlspecialchars($action_url);

cm_admin_head('Edit Badge Artwork - ' . $file_name);

echo '<link rel="stylesheet" href="artwork-edit.css">';
if (isset($bp_config['stylesheet']) && $bp_config['stylesheet']) {
	foreach ($bp_config['stylesheet'] as $stylesheet) {
		echo '<link rel="stylesheet" href="' . htmlspecialchars($stylesheet) . '">';
	}
}
echo '<style>';
	echo '.badge-artwork {';
		echo 'padding-bottom: ' . $artwork['aspect-ratio'] . '%;';
		echo 'background: url(\'artwork-image.php?name=' . urlencode($file_name) . '\') no-repeat center;';
		echo 'background-size: 100% 100%;';
	echo '}';
echo '</style>';
echo '<script type="text/javascript" src="artwork-edit.js"></script>';

cm_admin_body('Edit Badge Artwork');
cm_admin_nav('badge-artwork');
echo '<article>';

if ($message) {
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<p class="' . ($success ? 'cm-success-box' : 'cm-error-box') . '">';
				echo htmlspecialchars($message);
			echo '</p>';
		echo '</div>';
	echo '</div>';
}

echo '<div class="card">';
	echo '<div class="card-title">' . htmlspecialchars($file_name) . '</div>';
	echo '<div class="card-content">';
		echo '<p>';
			echo 'Start dragging to add a text field. Click a text field to edit, ';
			echo 'or use arrow keys to cycle through. Drag the blue handles to resize.';
		echo '</p>';
		echo '<div class="spacing">';
			echo '<div class="badge-artwork-container'; if ($artwork['vertical']) echo ' vertical'; echo '">';
				echo '<div class="badge-artwork-container-inner'; if ($artwork['vertical']) echo ' vertical'; echo '">';
					echo '<div class="badge-artwork'; if ($artwork['vertical']) echo ' vertical'; echo '">';
						echo '<div class="badge-artwork-fields"></div>';
						echo '<div class="badge-artwork-field-marquee hidden"></div>';
						echo '<div class="badge-artwork-field-editor hidden">';
							echo '<span class="badge-artwork-field-editor-content"></span>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-nw"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-n"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-ne"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-e"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-se"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-s"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-sw"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-w"></div>';
							echo '<div class="badge-artwork-field-editor-handle badge-artwork-field-editor-handle-center"></div>';
						echo '</div>';
					echo '</div>';
				echo '</div>';
			echo '</div>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="card artwork-editor-card">';
	echo '<div class="card-title">Edit Badge Artwork</div>';
	echo '<div class="card-content">';
		echo '<div class="spacing">';
			echo '<form action="' . $action_url_html . '" method="post" enctype="multipart/form-data">';
				echo '<table border="0" cellpadding="0" cellspacing="0" class="badge-artwork-actions">';
					echo '<tr>';
						echo '<td><label for="image-file">Upload image:</label></td>';
						echo '<td><input type="file" name="image-file"></td>';
						echo '<td><input type="submit" name="action" value="Upload Image"></td>';
						echo '<td><input type="submit" name="action" value="Download Image"></td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td><label for="fields-file">Upload layout:</label></td>';
						echo '<td><input type="file" name="fields-file"></td>';
						echo '<td><input type="submit" name="action" value="Upload Layout"></td>';
						echo '<td><input type="submit" name="action" value="Download Layout"></td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td><label for="copy-from">Copy from:</label></td>';
						echo '<td>';
							echo '<select id="copy-from" name="copy-from">';
								$lba = $badb->list_badge_artwork();
								if ($lba) {
									foreach ($lba as $ba) {
										if ($ba['file-name'] != $file_name) {
											$hba = htmlspecialchars($ba['file-name']);
											echo '<option value="' . $hba . '">' . $hba . '</option>';
										}
									}
								}
							echo '</select>';
						echo '</td>';
						echo '<td><input type="submit" name="action" value="Copy Layout"></td>';
						echo '<td><input type="submit" name="action" value="Delete Layout"></td>';
					echo '</tr>';
					echo '<tr>';
						echo '<td><label>Import mode:</label></td>';
						echo '<td>';
							echo '<label><input type="radio" name="import-mode" value="replace" checked>Replace</label>';
							echo '<label><input type="radio" name="import-mode" value="append">Append</label>';
						echo '</td>';
					echo '</tr>';
				echo '</table>';
			echo '</form>';
		echo '</div>';
	echo '</div>';
echo '</div>';

echo '<div class="card field-editor-card hidden">';
	echo '<div class="card-title">Edit Text Field</div>';
	echo '<div class="card-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
			echo '<tr>';
				echo '<th><label for="field-editor-field-key">Field Type:</label></th>';
				echo '<td>';
					echo '<select id="field-editor-field-key">';
						foreach ($badb->field_keys as $key => $name) {
							echo '<option value="' . htmlspecialchars($key) . '">' . htmlspecialchars($name) . '</option>';
						}
					echo '</select>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="field-editor-font-family">Font Name:</label></th>';
				echo '<td><input type="text" id="field-editor-font-family"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label>Font Style:</label></th>';
				echo '<td>';
					echo '<label><input type="checkbox" id="field-editor-font-weight-bold"><b>Bold</b></label>';
					echo '&nbsp;&nbsp;&nbsp;&nbsp;';
					echo '<label><input type="checkbox" id="field-editor-font-style-italic"><i>Italic</i></label>';
				echo '</td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="field-editor-color">Text Color (Adults):</label></th>';
				echo '<td><input type="text" id="field-editor-color"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="field-editor-background">Background (Adults):</label></th>';
				echo '<td><input type="text" id="field-editor-background"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="field-editor-color-minors">Text Color (Minors):</label></th>';
				echo '<td><input type="text" id="field-editor-color-minors"></td>';
			echo '</tr>';
			echo '<tr>';
				echo '<th><label for="field-editor-background-minors">Background (Minors):</label></th>';
				echo '<td><input type="text" id="field-editor-background-minors"></td>';
			echo '</tr>';
		echo '</table>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<button class="field-editor-save">Save</button>';
		echo '<button class="field-editor-cancel">Cancel</button>';
		echo '<button class="field-editor-delete">Delete</button>';
	echo '</div>';
echo '</div>';

echo '<form action="' . $action_url_html . '" method="post" class="card">';
	echo '<div class="card-title">Applicable Badge Types</div>';
	echo '<div class="card-content">';
		$badge_types = $bhdb->list_badge_type_names();
		$cols = 3;
		$cells = count($badge_types);
		$rows = ceil($cells / $cols);
		echo '<table border="0" cellspacing="0" cellpadding="0" class="badge-types">';
			for ($row = 0; $row < $rows; $row++) {
				echo '<tr>';
					for ($col = 0; $col < $cols; $col++) {
						$cell = $col * $rows + $row;
						if ($cell < $cells) {
							$bt = $badge_types[$cell];
							$inid = htmlspecialchars('badge-type-' . $bt['context'] . '-' . $bt['context-id']);
							echo '<td>';
								echo '<label title="' . htmlspecialchars($bt['id-string']) . '">';
									echo '<input type="checkbox"';
									echo ' name="' . $inid . '"';
									echo ' id="' . $inid . '"';
									echo ' value="1"';
									if (isset($artwork['map']) && $artwork['map']) {
										foreach ($artwork['map'] as $entry) {
											if ($entry['context'] == $bt['context'] && $entry['context-id'] == $bt['context-id']) {
												echo ' checked';
											}
										}
									}
									echo '>';
									echo htmlspecialchars($bt['name']);
								echo '</label>';
							echo '</td>';
						}
					}
				echo '</tr>';
			}
		echo '</table>';
	echo '</div>';
	echo '<div class="card-buttons">';
		echo '<input type="submit" name="action" value="Save Applicable Badge Types">';
	echo '</div>';
echo '</form>';

echo '</article>';
cm_admin_dialogs();

echo '<div class="dialog shortcuts-dialog hidden">';
	echo '<div class="dialog-title">Keyboard Shortcuts</div>';
	echo '<div class="dialog-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0">';
			echo '<tr><th colspan="2">Edit Badge Artwork</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel text field editing</td></tr>';
			echo '<tr><td><span class="kbd kbdw">&larr;</span></td><td>Select previous text field</td></tr>';
			echo '<tr><td><span class="kbd kbdw">&rarr;</span></td><td>Select next text field</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">/</span></td><td>Show keyboard shortcuts</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">D</span></td><td>Delete text field</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Save text field</td></tr>';
			echo '<tr><th colspan="2">Dialog Boxes</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel / Close</td></tr>';
		echo '</table>';
	echo '</div>';
echo '</div>';

cm_admin_tail();
