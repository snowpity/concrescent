<?php

require_once __DIR__ .'/res.php';
require_once __DIR__ .'/util.php';

function cm_form_label($id, $text) {
	$htmlid = $id ? ('cm-question-' . htmlspecialchars($id)) : $id;
	$out = '<label';
	if ($htmlid) $out .= ' for="' . $htmlid . '"';
	return $out . '>' . htmlspecialchars($text) . '</label>';
}

function cm_form_input($id, $type, $values, $answer, $required = false, $disabled = false) {
	$htmlid = $id ? ('cm-question-' . htmlspecialchars($id)) : $id;
	switch ($type) {
		case 'text':
		case 'url':
		case 'email':
			$out = '<input type="' . $type . '"';
			if ($htmlid) $out .= ' id="' . $htmlid . '"';
			if ($htmlid) $out .= ' name="' . $htmlid . '"';
			if ($answer) $out .= ' value="' . htmlspecialchars(implode(' ', $answer)) . '"';
			if ($required) $out .= ' required';
			if ($disabled) $out .= ' disabled';
			return $out . '>';
		case 'textarea':
			$out = '<textarea';
			if ($htmlid) $out .= ' id="' . $htmlid . '"';
			if ($htmlid) $out .= ' name="' . $htmlid . '"';
			if ($required) $out .= ' required';
			if ($disabled) $out .= ' disabled';
			$out .= '>';
			if ($answer) $out .= htmlspecialchars(implode("\n", $answer));
			return $out . '</textarea>';
		case 'radio':
			$out = '';
			foreach ($values as $i => $value) {
				$valueid = $htmlid ? ($htmlid . '-' . htmlspecialchars($i)) : $htmlid;
				if ($out) $out .= '<br>';
				$out .= '<label><input type="radio"';
				if ($valueid) $out .= ' id="' . $valueid . '"';
				if ($htmlid) $out .= ' name="' . $htmlid . '"';
				$out .= ' value="' . htmlspecialchars($value) . '"';
				if ($answer && in_array($value, $answer)) $out .= ' checked';
				if ($disabled) $out .= ' disabled';
				$out .= '>' . htmlspecialchars($value) . '</label>';
			}
			return $out;
		case 'checkbox':
			$out = '';
			foreach ($values as $i => $value) {
				$valueid = $htmlid ? ($htmlid . '-' . htmlspecialchars($i)) : $htmlid;
				if ($out) $out .= '<br>';
				$out .= '<label><input type="checkbox"';
				if ($valueid) $out .= ' id="' . $valueid . '"';
				if ($valueid) $out .= ' name="' . $htmlid . '[]"';
				$out .= ' value="' . htmlspecialchars($value) . '"';
				if ($answer && in_array($value, $answer)) $out .= ' checked';
				if ($disabled) $out .= ' disabled';
				$out .= '>' . htmlspecialchars($value) . '</label>';
			}
			return $out;
		case 'select':
			$out = '<select';
			if ($htmlid) $out .= ' id="' . $htmlid . '"';
			if ($htmlid) $out .= ' name="' . $htmlid . '"';
			if ($required) $out .= ' required';
			if ($disabled) $out .= ' disabled';
			$out .= '>';
			foreach ($values as $value) {
				$out .= '<option value="' . htmlspecialchars($value) . '"';
				if ($answer && in_array($value, $answer)) $out .= ' selected';
				$out .= '>' . htmlspecialchars($value) . '</option>';
			}
			return $out . '</select>';
		default:
			return '';
	}
}

function cm_form_row($question, $answer, $error = null, $for_editor = false) {
	if ($for_editor) {
		$out = '<tr>';
	} else {
		$classes = array('cm-question-row');
		if (isset($question['visible']) && $question['visible']) {
			foreach ($question['visible'] as $subcontext) {
				$classes[] = 'cm-question-row-' . (($subcontext == '*') ? 'all' : $subcontext);
			}
		}
		$out = '<tr class="' . implode(' ', $classes) . '">';
	}
	$id = $question['question-id'] ?? '';
	$title = $question['title'] ?? '';
	$text = $question['text'] ?? '';
	$type = $question['type'] ?? '';
	$values = $question['values'] ?? array();
	switch ($type) {
		case 'h1':
		case 'h2':
		case 'h3':
			$out .= '<td colspan="2">';
			if ($title) $out .= '<'.$type.' class="cm-question-title">' . safe_html_string($title) . '</'.$type.'>';
			if ($text) $out .= safe_html_string($text, 'cm-question-text');
			if ($for_editor && !$title && !$text) $out .= '<'.$type.' class="cm-question-title"></'.$type.'>';
			$out .= '</td>';
			break;
		case 'p':
		case 'q':
			$out .= (($type == 'p') ? '<td colspan="2">' : '<th></th><td>');
			if ($title) $out .= safe_html_string($title, 'cm-question-title');
			if ($text) $out .= safe_html_string($text, 'cm-question-text');
			if ($for_editor && !$title && !$text) $out .= '<p class="cm-question-text"></p>';
			$out .= '</td>';
			break;
		case 'hr':
			$out .= '<td colspan="2" class="hr"><hr></td>';
			break;
		default:
			if ($title && $text) {
				$out .= '<th></th>';
				$out .= '<td>';
				$out .= safe_html_string($title, 'cm-question-title');
				$out .= safe_html_string($text, 'cm-question-text');
				$out .= '<p>' . cm_form_input($id, $type, $values, $answer, false, $for_editor) . '</p>';
				if ($error) $out .= '<p class="error">' . htmlspecialchars($error) . '</p>';
				$out .= '</td>';
			} else {
				$out .= '<th>' . cm_form_label($id, ($title ? $title : ($text ? $text : ''))) . '</th>';
				$out .= '<td>';
				$out .= cm_form_input($id, $type, $values, $answer, false, $for_editor);
				if ($error) {
					if (strpos($out, '<br>') === false) {
						$out .= '<span class="error">' . htmlspecialchars($error) . '</span>';
					} else {
						$out .= '<div class="error">' . htmlspecialchars($error) . '</div>';
					}
				}
				$out .= '</td>';
			}
			break;
	}
	return $out . '</tr>';
}

function cm_form_posted_answer($id, $type, $post) {
	$htmlid = 'cm-question-' . $id;
	switch ($type) {
		case 'text':
		case 'url':
		case 'email':
		case 'radio':
		case 'select':
			if (!isset($post[$htmlid])){
				//error_log("Post did not have " . $htmlid . " in it");
				return array();
			}
			return ($post[$htmlid] ? array($post[$htmlid]) : array());
		case 'textarea':
			if (!isset($post[$htmlid])) return array();
			$answer = $post[$htmlid];
			$answer = str_replace("\r\n", "\n", $answer);
			$answer = str_replace("\r", "\n", $answer);
			return ($answer ? explode("\n", $answer) : array());
		case 'checkbox':
			if (!isset($post[$htmlid])){
				//error_log("Post did not have " . $htmlid . " in it");
				return array();
			}
			$answer = is_string($post[$htmlid]) ? explode("\n",$post[$htmlid]) : $post[$htmlid];
			/*
			$prefix = $htmlid . '[]';
			$prefixlen = strlen($prefix);
			foreach ($_POST as $k => $v) {
				if (substr($k, 0, $prefixlen) === $prefix) {
					$answer[] = $v;
				}
			}*/
			return $answer;
		case 'h1':
		case 'h2':
		case 'h3':
		case 'p':
		case 'q':
		case 'hr':
			return array();
		default:
			error_log("Error type of question " . $type . " is not recognized for question " . $id);
			return array();
	}
}

function cm_form_review_row($question, $answer, $can_edit = true) {
	$type = $question['type'] ?? '';
	$is_title = ($type == 'h1' || $type == 'h2' || $type == 'h3');
	$is_text = ($type == 'p' || $type == 'q' || $type == 'hr');
	$title = $question['title'] ?? '';
	$text = $question['text'] ?? '';
	if ($can_edit || $is_title || $is_text) {
		if ($title) $question['text'] = null;
		return cm_form_row($question, $answer);
	} else {
		$out = '<tr><th><label>';
		if ($title) $out .= htmlspecialchars($title);
		else if ($text) $out .= htmlspecialchars($text);
		$out .= '</label></th><td>';
		switch ($type) {
			case 'url'  : $out .= implode('<br>', array_map('url_link', $answer)); break;
			case 'email': $out .= implode('<br>', array_map('email_link', $answer)); break;
			default     : $out .= paragraph_string(implode("\n", $answer)); break;
		}
		return $out . '</td></tr>';
	}
}

/* Form Editor */

function cm_form_edit_head(&$form_def) {
	echo '<script type="text/javascript">cm_form_def = (' . json_encode($form_def) . ');</script>';
	echo '<script type="text/javascript" src="' . htmlspecialchars(resource_file_url('cmforms.js', false)) . '"></script>';
}

function cm_form_edit_start() {
	echo '<div class="cm-form-editor">';
	echo '<table border="0" cellpadding="0" cellspacing="0" class="cm-form-table">';
}

function cm_form_edit_static_section(&$questions) {
	echo '<tbody class="cm-form-editor-static-section">';
	foreach ($questions as $question) {
		echo cm_form_row($question, array('Question provided by system'), null, true);
	}
	echo '</tbody>';
}

function cm_form_edit_custom_text_section($name, $default) {
	echo '<tbody class="cm-form-editor-custom-text-section"';
	echo ' id="customtextid-' . htmlspecialchars($name) . '">';
		echo '<tr title="Click to edit explanatory text." class="view-row">';
			echo '<td colspan="2">';
				echo '<p class="view-area">';
				if ($default) echo safe_html_string($default);
				echo '</p>';
			echo '</td>';
		echo '</tr>';
		echo '<tr class="edit-row hidden">';
			echo '<td colspan="2">';
				echo '<textarea></textarea>';
			echo '</td>';
		echo '</tr>';
		echo '<tr class="edit-row hidden">';
			echo '<td colspan="2" class="td-actions">';
				echo '<button class="cancel-edit-button">Cancel</button>';
				echo '<button class="confirm-edit-button">Save</button>';
			echo '</td>';
		echo '</tr>';
	echo '</tbody>';
}

function cm_form_edit_dynamic_section(&$form_def) {
	echo '<tbody class="cm-form-editor-dynamic-section"></tbody>';
	echo '<tbody class="cm-form-editor-dynamic-section-actions">';
		echo '<tr>';
			echo '<td colspan="2" title="Click to add title, explanatory text, or question." class="add-button">';
				echo 'Click to add title, explanatory text, or question.';
			echo '</td>';
		echo '</tr>';
	echo '</tbody>';
	echo '<tbody class="cm-form-editor-dynamic-section-editor hidden">';
		echo '<tr class="cm-form-editor-row-editor-row">';
			echo '<th><label>Type</label></th>';
			echo '<td>';
				echo '<select class="ea-type">';
					echo '<option value="h1">Large Title</option>';
					echo '<option value="h2">Medium Title</option>';
					echo '<option value="h3">Small Title</option>';
					echo '<option value="p">Explanatory Text</option>';
					echo '<option value="q">Indented Text</option>';
					echo '<option value="hr">Section Break</option>';
					echo '<option value="text" selected>Short Answer</option>';
					echo '<option value="textarea">Long Answer</option>';
					echo '<option value="url">URL</option>';
					echo '<option value="email">Email Address</option>';
					echo '<option value="radio">Multiple Choice</option>';
					echo '<option value="checkbox">Checkboxes</option>';
					echo '<option value="select">Choose from a List</option>';
				echo '</select>';
			echo '</td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row ear-title">';
			echo '<th><label>Title</label></th>';
			echo '<td><input type="text" class="ea-title"></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row ear-text">';
			echo '<th><label>Text</label></th>';
			echo '<td><textarea class="ea-text"></textarea></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row ear-values hidden">';
			echo '<th><label>Values</label></th>';
			echo '<td><textarea class="ea-values"></textarea></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row">';
			echo '<th><label>Active</label></th>';
			echo '<td><label><input type="checkbox" checked class="ea-active">Question and answer appear on Review and Edit detail pages.</label></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row">';
			echo '<th><label>In List</label></th>';
			echo '<td><label><input type="checkbox" class="ea-listed">Answers appear in a column on Review and Edit list pages.</label></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row">';
			echo '<th><label>Exposed</label></th>';
			echo '<td><label><input type="checkbox" class="ea-exposed">Answers are exposed in the schedule API</label></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row">';
			echo '<th><label>Visible</label></th>';
			echo '<td><label><input type="checkbox" checked class="ea-visible">Question appears on Register and Apply pages.</label> <a href="#" class="ea-visible-advanced">Advanced...</a></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row ear-visible-advanced hidden">';
			echo '<th></th>';
			echo '<td>';
				if (isset($form_def['subcontext'])) {
					foreach ($form_def['subcontext'] as $subcontext) {
						echo '<label><input type="checkbox" checked class="ea-visible-' . htmlspecialchars($subcontext['id']) . '">' . htmlspecialchars($subcontext['name']) . '</label>';
					}
				}
			echo '</td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row">';
			echo '<th><label>Required</label></th>';
			echo '<td><label><input type="checkbox" class="ea-required">Question must be answered in order to submit.</label> <a href="#" class="ea-required-advanced">Advanced...</a></td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row ear-required-advanced hidden">';
			echo '<th></th>';
			echo '<td>';
				if (isset($form_def['subcontext'])) {
					foreach ($form_def['subcontext'] as $subcontext) {
						echo '<label><input type="checkbox" class="ea-required-' . htmlspecialchars($subcontext['id']) . '">' . htmlspecialchars($subcontext['name']) . '</label>';
					}
				}
			echo '</td>';
		echo '</tr>';
		echo '<tr class="cm-form-editor-row-editor-row">';
			echo '<td colspan="2" class="td-actions">';
				echo '<button class="delete-button">Delete</button>';
				echo '<button class="up-button">&#x2191;</button>';
				echo '<button class="down-button">&#x2193;</button>';
				echo '<button class="cancel-edit-button">Cancel</button>';
				echo '<button class="confirm-edit-button">Save</button>';
			echo '</td>';
		echo '</tr>';
	echo '</tbody>';
}

function cm_form_edit_end() {
	echo '</table>';
	echo '</div>';
}

function cm_form_edit_body(&$form_def, $questions) {
	cm_form_edit_start();
	$section = array();
	foreach ($questions as $question) {
		if ($question['type'] == 'custom-text') {
			if ($section) cm_form_edit_static_section($section);
			$name = ($question['name'] ?? '');
			$default = ($question['default'] ?? '');
			cm_form_edit_custom_text_section($name, $default);
			$section = array();
		} else if ($question['type'] == 'custom-questions') {
			if ($section) cm_form_edit_static_section($section);
			cm_form_edit_dynamic_section($form_def);
			$section = array();
		} else {
			$section[] = $question;
		}
	}
	if ($section) cm_form_edit_static_section($section);
	cm_form_edit_end();
}

function cm_form_edit_dialogs() {
	echo '<div class="dialog delete-dialog hidden">';
		echo '<div class="dialog-title">Delete Question</div>';
		echo '<div class="dialog-content">';
			echo '<p>Are you sure you want to delete the question <b class="delete-name"></b>?</p>';
			echo '<p>This action cannot be undone. If you are unsure, it is better to mark it inactive.</p>';
		echo '</div>';
		echo '<div class="dialog-buttons">';
			echo '<button class="cancel-delete-button">Cancel</button>';
			echo '<button class="soft-delete-button">Mark Inactive</button>';
			echo '<button class="confirm-delete-button">Delete</button>';
		echo '</div>';
	echo '</div>';
	echo '<div class="dialog shortcuts-dialog hidden">';
		echo '<div class="dialog-title">Keyboard Shortcuts</div>';
		echo '<div class="dialog-content">';
			echo '<table border="0" cellpadding="0" cellspacing="0">';
				echo '<tr><th colspan="2">Form Edit Pages</th></tr>';
				echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel all edits in progress</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">/</span></td><td>Show keyboard shortcuts</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">A</span></td><td>Add title, explanatory text, or question</td></tr>';
				echo '<tr><th colspan="2">Single Edit In Progress</th></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">&uarr;</span></td><td>Move up</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">&darr;</span></td><td>Move down</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">D</span></td><td>Delete</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Save</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">X</span></td><td>Toggle Active</td></tr>';
				echo '<tr><th colspan="2">Dialog Boxes</th></tr>';
				echo '<tr><td><span class="kbd kbdw">esc</span></td><td>Cancel / Close</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">D</span></td><td>Delete</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">S</span></td><td>Save</td></tr>';
				echo '<tr><td><span class="kbd">ctrl</span> <span class="kbd">shift</span> <span class="kbd">X</span></td><td>Mark Inactive</td></tr>';
			echo '</table>';
		echo '</div>';
	echo '</div>';
}

function cm_form_edit_process_requests($db) {
	if (isset($_POST['cm-form-action'])) {
		header('Content-type: text/plain');
		switch ($_POST['cm-form-action']) {
			case 'load-custom-text':
				$name = $_POST['cm-form-ct-name'];
				$text = $db->get_custom_text($name);
				if ($text === false) $text = '';
				$response = array('ok' => true, 'text' => $text);
				echo json_encode($response);
				break;
			case 'save-custom-text':
				$name = $_POST['cm-form-ct-name'];
				$text = $_POST['cm-form-ct-text'];
				$ok = $db->set_custom_text($name, $text);
				$response = array('ok' => $ok);
				echo json_encode($response);
				break;
			case 'render-dynamic-row':
				$question = json_decode($_POST['cm-form-question'], true);
				$answer = array('Answer provided by user');
				echo cm_form_row($question, $answer, null, true);
				break;
			case 'get-question':
				$id = $_POST['cm-form-question-id'];
				$question = $db->get_question($id);
				$ok = ($question !== false);
				$response = array('ok' => $ok);
				if ($ok) {
					$response['question'] = $question;
					$answer = array('Answer provided by user');
					$response['html'] = cm_form_row($question, $answer, null, true);
				}
				echo json_encode($response);
				break;
			case 'list-questions':
				$questions = $db->list_questions();
				$ok = ($questions !== false);
				$response = array('ok' => $ok);
				if ($ok) {
					$response['questions'] = $questions;
					$answer = array('Answer provided by user');
					$response['html'] = array();
					foreach ($questions as $question) {
						$response['html'][] = cm_form_row($question, $answer, null, true);
					}
				}
				echo json_encode($response);
				break;
			case 'create-question':
				$question = json_decode($_POST['cm-form-question'], true);
				$id = $db->create_question($question);
				$ok = ($id !== false);
				$response = array('ok' => $ok);
				if ($ok) {
					$response['question-id'] = $id;
					$question = $db->get_question($id);
					if ($question !== false) {
						$response['question'] = $question;
						$answer = array('Answer provided by user');
						$response['html'] = cm_form_row($question, $answer, null, true);
					}
				}
				echo json_encode($response);
				break;
			case 'update-question':
				$question = json_decode($_POST['cm-form-question'], true);
				$ok = $db->update_question($question);
				$response = array('ok' => $ok);
				if ($ok) {
					$id = $question['question-id'];
					$response['question-id'] = $id;
					$question = $db->get_question($id);
					if ($question !== false) {
						$response['question'] = $question;
						$answer = array('Answer provided by user');
						$response['html'] = cm_form_row($question, $answer, null, true);
					}
				}
				echo json_encode($response);
				break;
			case 'delete-question':
				$id = $_POST['cm-form-question-id'];
				$ok = $db->delete_question($id);
				$response = array('ok' => $ok);
				echo json_encode($response);
				break;
			case 'get-question-order':
				$order = $db->get_question_order();
				$ok = ($order !== false);
				$response = array('ok' => $ok);
				if ($ok) $response['order'] = $order;
				echo json_encode($response);
				break;
			case 'set-question-order':
				$order = json_decode($_POST['cm-form-question-order'], true);
				$order = $db->set_question_order($order);
				$ok = ($order !== false);
				$response = array('ok' => $ok);
				if ($ok) $response['order'] = $order;
				echo json_encode($response);
				break;
		}
		exit(0);
	}
}
