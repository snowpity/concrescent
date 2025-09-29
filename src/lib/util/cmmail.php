<?php

require_once __DIR__ .'/res.php';
require_once __DIR__ .'/util.php';

function cm_mail_head() {
	echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('cmmail.js', false)) . '"></script>';
}

function cm_mail_notification($success) {
	if ($success) {
		echo '<p class="cm-success-box">Changes saved.</p>';
	} else {
		echo '<p class="cm-error-box">Save failed. Please try again.</p>';
	}
}

function cm_mail_editor($id, $name, $template) {
	echo '<h3>' . htmlspecialchars($name) . '</h3>';
	echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-mail-editor">';
		echo '<tr>';
			echo '<th>';
				echo '<label for="cm-mail-contact-' . htmlspecialchars($id) . '">';
					echo 'Contact:';
				echo '</label>';
			echo '</th>';
			echo '<td>';
				echo '<input type="email"';
				echo ' name="cm-mail-contact-' . htmlspecialchars($id) . '"';
				echo ' id="cm-mail-contact-' . htmlspecialchars($id) . '"';
				if ($template && isset($template['contact-address'])) {
					echo ' value="' . htmlspecialchars($template['contact-address']) . '"';
				}
				echo ' class="cm-mail-contact">';
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<th>';
				echo '<label for="cm-mail-from-' . htmlspecialchars($id) . '">';
					echo 'From:';
				echo '</label>';
			echo '</th>';
			echo '<td>';
				echo '<input type="email"';
				echo ' name="cm-mail-from-' . htmlspecialchars($id) . '"';
				echo ' id="cm-mail-from-' . htmlspecialchars($id) . '"';
				if ($template && isset($template['from'])) {
					echo ' value="' . htmlspecialchars($template['from']) . '"';
				}
				echo ' class="cm-mail-from">';
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<th>';
				echo '<label for="cm-mail-bcc-' . htmlspecialchars($id) . '">';
					echo 'Bcc:';
				echo '</label>';
			echo '</th>';
			echo '<td>';
				echo '<input type="email"';
				echo ' name="cm-mail-bcc-' . htmlspecialchars($id) . '"';
				echo ' id="cm-mail-bcc-' . htmlspecialchars($id) . '"';
				if ($template && isset($template['bcc'])) {
					echo ' value="' . htmlspecialchars($template['bcc']) . '"';
				}
				echo ' class="cm-mail-bcc">';
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<th>';
				echo '<label for="cm-mail-subject-' . htmlspecialchars($id) . '">';
					echo 'Subject:';
				echo '</label>';
			echo '</th>';
			echo '<td>';
				echo '<input type="text"';
				echo ' name="cm-mail-subject-' . htmlspecialchars($id) . '"';
				echo ' id="cm-mail-subject-' . htmlspecialchars($id) . '"';
				if ($template && isset($template['subject'])) {
					echo ' value="' . htmlspecialchars($template['subject']) . '"';
				}
				echo ' class="cm-mail-subject">';
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<th>';
				echo '<label for="cm-mail-type-' . htmlspecialchars($id) . '">';
					echo 'Type:';
				echo '</label>';
			echo '</th>';
			echo '<td>';
				echo '<select';
				echo ' name="cm-mail-type-' . htmlspecialchars($id) . '"';
				echo ' id="cm-mail-type-' . htmlspecialchars($id) . '"';
				echo ' class="cm-mail-type">';
					echo '<option value="Text"';
					if ($template && isset($template['type']) && $template['type'] == 'Text') echo ' selected';
					echo '>Text</option>';
					echo '<option value="Simple HTML"';
					if ($template && isset($template['type']) && $template['type'] == 'Simple HTML') echo ' selected';
					echo '>Simple HTML</option>';
					echo '<option value="Full HTML"';
					if ($template && isset($template['type']) && $template['type'] == 'Full HTML') echo ' selected';
					echo '>Full HTML</option>';
				echo '</select>';
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<th></th>';
			echo '<td>';
				echo '<textarea';
				echo ' name="cm-mail-body-' . htmlspecialchars($id) . '"';
				echo ' id="cm-mail-body-' . htmlspecialchars($id) . '"';
				echo ' class="cm-mail-body">';
					if ($template && isset($template['body'])) {
						$body = $template['body'];
						$body = str_replace("\r\n", "\n", $body);
						$body = str_replace("\r", "\n", $body);
						echo htmlspecialchars($body);
					}
				echo '</textarea>';
			echo '</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<th></th>';
			echo '<th>Preview:</th>';
		echo '</tr>';
		echo '<tr>';
			echo '<th></th>';
			echo '<td>';
				echo '<div class="cm-mail-preview">';
					echo '<iframe></iframe>';
				echo '</div>';
			echo '</td>';
		echo '</tr>';
	echo '</table>';
}

function cm_mail_merge_help($fields) {
	echo '<h3>Mail Merge Fields</h3>';
	echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-mail-merge-help">';
		echo '<tr>';
			echo '<td><code>[[event-name]]</code></td>';
			echo '<td>The name of the event.</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td><code>[[event-start-date]]</code></td>';
			echo '<td>The date of the first day of the event.</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td><code>[[event-end-date]]</code></td>';
			echo '<td>The date of the last day of the event.</td>';
		echo '</tr>';
		echo '<tr>';
			echo '<td><code>[[contact-address]]</code></td>';
			echo '<td>The email address set as the Contact for this message.</td>';
		echo '</tr>';
		foreach ($fields as $k => $v) {
			echo '<tr>';
				echo '<td><code>[[' . htmlspecialchars($k) . ']]</code></td>';
				echo '<td>' . htmlspecialchars($v) . '</td>';
			echo '</tr>';
		}
	echo '</table>';
	echo '<p>';
		echo 'If you do not wish to send out an email automatically, ';
		echo 'you can leave it blank and no email will be sent.';
	echo '</p>';
}

function cm_mail_form_submit() {
	echo '<input type="hidden" name="cm-mail-action" value="update">';
	echo '<input type="submit" name="submit" value="Save Changes">';
}

function cm_mail_dialogs() {
	echo '<div class="dialog shortcuts-dialog hidden">';
		echo '<div class="dialog-title">Keyboard Shortcuts</div>';
		echo '<div class="dialog-content">';
			echo '<table border="0" cellpadding="0" cellspacing="0">';
				echo '<tr><th colspan="2">Form Letter Pages</th></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">/</span></td><td>Show keyboard shortcuts</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Save Changes</td></tr>';
				echo '<tr><th colspan="2">Dialog Boxes</th></tr>';
				echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel / Close</td></tr>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
}

function cm_mail_posted_template($id) {
	$contact = $_POST['cm-mail-contact-' . $id] ?? '';
	$from    = $_POST['cm-mail-from-' . $id] ?? '';
	$bcc     = $_POST['cm-mail-bcc-' . $id] ?? '';
	$subject = $_POST['cm-mail-subject-' . $id] ?? '';
	$type    = $_POST['cm-mail-type-' . $id] ?? 'Text';
	$body    = $_POST['cm-mail-body-' . $id] ?? '';
	$body = str_replace("\r\n", "\n", $body);
	$body = str_replace("\r", "\n", $body);
	$body = str_replace("\n", "\r\n", $body);
	return array(
		'name' => $id,
		'contact-address' => $contact,
		'from' => $from,
		'bcc' => $bcc,
		'subject' => $subject,
		'type' => $type,
		'body' => $body
	);
}
