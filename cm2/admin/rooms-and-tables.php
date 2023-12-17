<?php

require_once __DIR__ .'/../lib/database/application.php';
require_once __DIR__ .'/../lib/database/misc.php';
require_once __DIR__ .'/admin.php';

cm_admin_check_permission('rooms-and-tables', 'rooms-and-tables');

$apdb = new cm_application_db($db, null);
$midb = new cm_misc_db($db);

$message = null;
$success = null;

if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'upload-image':
			$file = ($_FILES['file'] ?? null);
			if (!$file || (isset($file['error']) && $file['error'])) {
				$message = 'Error uploading image. Please try again with a different image.';
				$success = false;
			} else {
				$image_path = ($file['tmp_name'] ?? null);
				$image_size = ($image_path ? getimagesize($image_path) : null);
				$image_w = (($image_size && $image_size[0]) ? $image_size[0] : null);
				$image_h = (($image_size && $image_size[1]) ? $image_size[1] : null);
				$image_type = ($image_path ? exif_imagetype($image_path) : null);
				$mime_type = ($image_type ? image_type_to_mime_type($image_type) : null);
				if (
					$mime_type && $midb->upload_file(
						'rooms-and-tables', $mime_type,
						$image_w, $image_h, $image_path
					)
				) {
					$message = 'Image upload succeeded.';
					$success = true;
				} else {
					$message = 'Error uploading image. Please try again with a different image.';
					$success = false;
				}
			}
			break;
		case 'download-image':
			if (!$midb->download_file('rooms-and-tables', true)) {
				header('Content-Disposition: attachment; filename=rooms-and-tables.png');
				header('Content-Type: image/png');
				header('Pragma: no-cache');
				header('Expires: 0');
				$image = imagecreate(640, 480);
				$bg = imagecolorallocate($image, 255, 255, 255);
				imagefilledrectangle($image, 0, 0, 640, 480, $bg);
				$fg = imagecolorallocate($image, 0, 0, 255);
				imagestring($image, 5, (640-9*21)/2, 480/2-8-12, 'Could not load image.', $fg);
				imagestring($image, 5, (640-9*24)/2, 480/2-8+12, 'Please upload a new one.', $fg);
				imagepng($image);
				imagedestroy($image);
			}
			exit(0);
		case 'delete-image':
			$midb->delete_file('rooms-and-tables');
			break;
		case 'upload-tags':
			$file = ($_FILES['file'] ?? null);
			if (!$file || (isset($file['error']) && $file['error'])) {
				$message = 'Error uploading file. Please try again with a different file.';
				$success = false;
			} else {
				$path = ($file['tmp_name'] ?? null);
				if ($path && $apdb->upload_rooms_and_tables($path)) {
					$message = 'File upload succeeded.';
					$success = true;
				} else {
					$message = 'Error uploading file. Please try again with a different file.';
					$success = false;
				}
			}
			break;
		case 'download-tags':
			$apdb->download_rooms_and_tables();
			exit(0);
		case 'delete-tags':
			$apdb->delete_rooms_and_tables();
			break;
		case 'list-tags':
			header('Content-type: text/plain');
			$tags = $apdb->list_rooms_and_tables(true);
			$response = array('ok' => true, 'tags' => $tags);
			echo json_encode($response);
			exit(0);
		case 'create-tag':
			header('Content-type: text/plain');
			$ok = $apdb->create_room_or_table(array(
				'x1' => (float)$_POST['x1'],
				'y1' => (float)$_POST['y1'],
				'x2' => (float)$_POST['x2'],
				'y2' => (float)$_POST['y2'],
				'id' => trim($_POST['newid'])
			));
			$response = array('ok' => $ok);
			echo json_encode($response);
			exit(0);
		case 'update-tag':
			header('Content-type: text/plain');
			$ok = $apdb->update_room_or_table(
				trim($_POST['oldid']),
				array(
					'x1' => (float)$_POST['x1'],
					'y1' => (float)$_POST['y1'],
					'x2' => (float)$_POST['x2'],
					'y2' => (float)$_POST['y2'],
					'id' => trim($_POST['newid'])
				)
			);
			$response = array('ok' => $ok);
			echo json_encode($response);
			exit(0);
		case 'delete-tag':
			header('Content-type: text/plain');
			$ok = $apdb->delete_room_or_table(trim($_POST['oldid']));
			$response = array('ok' => $ok);
			echo json_encode($response);
			exit(0);
	}
}

$image_size = $midb->get_file_image_size('rooms-and-tables');
if (!$image_size) $image_size = array(640, 480);
$image_ratio = $image_size[1] * 100 / $image_size[0];

cm_admin_head('Rooms & Tables');

echo '<link rel="stylesheet" href="rooms-and-tables.css">';
echo '<style>.tag-map { padding-bottom: ' . $image_ratio . '%; }</style>';
echo '<script type="text/javascript" src="rooms-and-tables.js"></script>';

cm_admin_body('Rooms & Tables');
cm_admin_nav('rooms-and-tables');

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			if ($message) {
				echo '<p class="' . ($success ? 'cm-success-box' : 'cm-error-box') . '">';
				echo htmlspecialchars($message);
				echo '</p>';
				echo '<hr>';
			}
			echo '<p>';
				echo 'Upload an image of your event space floor plan. ';
				echo 'Then click or drag to tag a room or table with its identifier.';
			echo '</p>';
			echo '<div class="spacing">';
				echo '<div class="tag-map">';
					echo '<div class="tags"></div>';
					echo '<div class="tag-marquee hidden"></div>';
					echo '<div class="tag-editor hidden">';
						echo '<div class="tag-editor-handle tag-editor-handle-nw"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-n"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-ne"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-e"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-se"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-s"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-sw"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-w"></div>';
						echo '<div class="tag-editor-handle tag-editor-handle-center"></div>';
						echo '<div class="tag-editor-input">';
							echo '<input type="text" class="tag-editor-id">';
						echo '</div>';
						echo '<div class="tag-editor-buttons">';
							echo '<button class="tag-editor-save">Save</button>';
							echo '<button class="tag-editor-cancel">Cancel</button>';
							echo '<button class="tag-editor-delete">Delete</button>';
						echo '</div>';
					echo '</div>';
				echo '</div>';
			echo '</div>';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="tag-map-actions">';
				echo '<tr>';
					echo '<td>';
						echo '<label for="image-file">Upload image:</label>';
					echo '</td>';
					echo '<td>';
						echo '<form action="rooms-and-tables.php" method="post" enctype="multipart/form-data">';
							echo '<input type="hidden" name="action" value="upload-image">';
							echo '<input type="file" name="file" id="file">&nbsp;&nbsp;';
							echo '<input type="submit" value="Upload Image">';
						echo '</form>';
					echo '</td>';
					echo '<td>';
						echo '<form action="rooms-and-tables.php" method="post">';
							echo '<input type="hidden" name="action" value="download-image">';
							echo '<input type="submit" value="Download Image">';
						echo '</form>';
					echo '</td>';
					echo '<td>';
						echo '<form action="rooms-and-tables.php" method="post">';
							echo '<input type="hidden" name="action" value="delete-image">';
							echo '<input type="submit" value="Delete Image">';
						echo '</form>';
					echo '</td>';
				echo '</tr>';
				echo '<tr>';
					echo '<td>';
						echo '<label for="tags-file">Upload tags:</label>';
					echo '</td>';
					echo '<td>';
						echo '<form action="rooms-and-tables.php" method="post" enctype="multipart/form-data">';
							echo '<input type="hidden" name="action" value="upload-tags">';
							echo '<input type="file" name="file" id="file">&nbsp;&nbsp;';
							echo '<input type="submit" value="Upload Tags">';
						echo '</form>';
					echo '</td>';
					echo '<td>';
						echo '<form action="rooms-and-tables.php" method="post">';
							echo '<input type="hidden" name="action" value="download-tags">';
							echo '<input type="submit" value="Download Tags">';
						echo '</form>';
					echo '</td>';
					echo '<td>';
						echo '<form action="rooms-and-tables.php" method="post">';
							echo '<input type="hidden" name="action" value="delete-tags">';
							echo '<input type="submit" value="Delete Tags">';
						echo '</form>';
					echo '</td>';
				echo '</tr>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();

echo '<div class="dialog shortcuts-dialog hidden">';
	echo '<div class="dialog-title">Keyboard Shortcuts</div>';
	echo '<div class="dialog-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0">';
			echo '<tr><th colspan="2">Rooms &amp; Tables</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel tag editing</td></tr>';
			echo '<tr><td><span class="kbd kbdw">&larr;</span></td><td>Select previous tag</td></tr>';
			echo '<tr><td><span class="kbd kbdw">&rarr;</span></td><td>Select next tag</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">/</span></td><td>Show keyboard shortcuts</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">D</span></td><td>Delete tag</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Save tag</td></tr>';
			echo '<tr><th colspan="2">Dialog Boxes</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel / Close</td></tr>';
		echo '</table>';
	echo '</div>';
echo '</div>';

cm_admin_tail();
