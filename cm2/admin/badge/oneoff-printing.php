<?php

require_once __DIR__ .'/../../lib/database/badge-artwork.php';
require_once __DIR__ .'/../admin.php';

cm_admin_check_permission('badge-oneoff-printing', 'badge-oneoff-printing');

$badb = new cm_badge_artwork_db($db);
$artwork = $badb->list_badge_artwork();

cm_admin_head('One-Off Badge Printing');

echo '<script type="text/javascript">';
	echo 'cm_badge_artwork_field_keys = (' . json_encode($badb->field_keys) . ');';
	echo 'cm_badge_artwork = (' . json_encode($artwork) . ');';
echo '</script>';

echo '<script type="text/javascript" src="oneoff-printing.js"></script>';

cm_admin_body('One-Off Badge Printing');
cm_admin_nav('badge-oneoff-printing');

echo '<article>';
	echo '<div class="card">';
		echo '<div class="card-content">';
			echo '<h3>Select Badge Artwork</h3>';
			echo '<div class="cm-badge-artwork-select spacing">';
				if ($artwork) {
					foreach ($artwork as $i => $a) {
						echo '<div class="cm-badge-artwork" id="artwork-' . $i . '">';
							echo '<div class="cm-badge-artwork-image" style="';
								echo 'background: url(\'artwork-image.php?name=' . urlencode($a['file-name']) . '\');';
								echo 'background-repeat: no-repeat;';
								echo 'background-position: center;';
								echo 'background-size: contain;';
							echo '"></div>';
							echo '<div class="cm-badge-artwork-name">';
								echo htmlspecialchars($a['file-name']);
							echo '</div>';
						echo '</div>';
					}
				} else {
					echo '<div class="cm-badge-artwork-none">';
						echo 'No badge artwork is available.';
					echo '</div>';
				}
			echo '</div>';
			echo '<hr class="badge-info hidden">';
			echo '<h3 class="badge-info hidden">Enter Badge Information</h3>';
			echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table badge-info hidden">';
				echo '<tbody class="badge-info-tbody">';
				echo '</tbody>';
				echo '<tbody>';
					echo '<tr>';
						echo '<th>Age:</th>';
						echo '<td>';
							echo '<label><input type="radio" name="age" value="50" checked>Adult</label>';
							echo '&nbsp;&nbsp;&nbsp;&nbsp;';
							echo '<label><input type="radio" name="age" value="10">Minor</label>';
						echo '</td>';
					echo '</tr>';
				echo '</tbody>';
			echo '</table>';
			echo '<hr class="badge-info hidden">';
			echo '<h3 class="badge-info hidden">Print Badge</h3>';
			echo '<p class="badge-info hidden">';
				echo '<a class="button print-button" target="_blank">Print</a>';
				echo '<button class="cancel-button">Close</button>';
			echo '</p>';
		echo '</div>';
	echo '</div>';
echo '</article>';

cm_admin_dialogs();

echo '<div class="dialog shortcuts-dialog hidden">';
	echo '<div class="dialog-title">Keyboard Shortcuts</div>';
	echo '<div class="dialog-content">';
		echo '<table border="0" cellpadding="0" cellspacing="0">';
			echo '<tr><th colspan="2">One-Off Badge Printing</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Close</td></tr>';
			echo '<tr><td><span class="kbd kbdw">&larr;</span></td><td>Select previous badge artwork</td></tr>';
			echo '<tr><td><span class="kbd kbdw">&rarr;</span></td><td>Select next badge artwork</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">/</span></td><td>Show keyboard shortcuts</td></tr>';
			echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">P</span></td><td>Print</td></tr>';
			echo '<tr><th colspan="2">Dialog Boxes</th></tr>';
			echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Close</td></tr>';
		echo '</table>';
	echo '</div>';
echo '</div>';

cm_admin_tail();
