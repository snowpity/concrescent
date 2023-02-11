<?php

require_once dirname(__FILE__).'/database.php';

class cm_forms_db {

	public $context;
	public $cm_db;

	public function __construct($cm_db, $context) {
		$this->context = $context;
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('form_custom_text', (
			'`context` VARCHAR(63) NOT NULL,'.
			'`name` VARCHAR(63) NOT NULL,'.
			'`text` TEXT NOT NULL,'.
			'PRIMARY KEY (`context`, `name`)'
		));
		$this->cm_db->table_def('form_questions', (
			'`question_id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`context` VARCHAR(63) NOT NULL,'.
			'`order` INTEGER NOT NULL,'.
			'`title` VARCHAR(255) NOT NULL,'.
			'`text` TEXT NULL,'.
			'`type` ENUM('.
				'\'h1\',\'h2\',\'h3\',\'p\',\'q\',\'hr\','.
				'\'text\',\'textarea\',\'url\',\'email\','.
				'\'radio\',\'checkbox\',\'select\''.
			') NOT NULL,'.
			'`values` TEXT NULL,'.
			'`active` BOOLEAN NOT NULL,'.
			'`listed` BOOLEAN NOT NULL,'.
			'`exposed` BOOLEAN NOT NULL,'.
			'`visible` TEXT NOT NULL,'.
			'`required` TEXT NOT NULL'
		));
		$this->cm_db->table_def('form_answers', (
			'`question_id` INTEGER NOT NULL,'.
			'`context` VARCHAR(63) NOT NULL,'.
			'`context_id` INTEGER NOT NULL,'.
			'`answer` TEXT NOT NULL,'.
			'PRIMARY KEY (`question_id`, `context`, `context_id`)'
		));
	}

	public function get_custom_text($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `text`'.
			' FROM '.$this->cm_db->table_name('form_custom_text').
			' WHERE `context` = ? AND `name` = ? LIMIT 1'
		);
		$stmt->bind_param('ss', $this->context, $name);
		$stmt->execute();
		$stmt->bind_result($text);
		if ($stmt->fetch()) {
			$stmt->close();
			return $text;
		}
		$stmt->close();
		return false;
	}

	public function list_custom_text() {
		$texts = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `name`, `text`'.
			' FROM '.$this->cm_db->table_name('form_custom_text').
			' WHERE `context` = ? ORDER BY `name`'
		);
		$stmt->bind_param('s', $this->context);
		$stmt->execute();
		$stmt->bind_result($name, $text);
		while ($stmt->fetch()) {
			$texts[$name] = $text;
		}
		$stmt->close();
		return $texts;
	}

	public function set_custom_text($name, $text) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('form_custom_text').' SET '.
			'`context` = ?, `name` = ?, `text` = ?'.
			' ON DUPLICATE KEY UPDATE '.
			'`context` = ?, `name` = ?, `text` = ?'
		);
		$stmt->bind_param(
			'ssssss',
			$this->context, $name, $text,
			$this->context, $name, $text
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function clear_custom_text($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('form_custom_text').
			' WHERE `context` = ? AND `name` = ? LIMIT 1'
		);
		$stmt->bind_param('ss', $this->context, $name);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function get_question($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `question_id`, `context`, `order`,'.
			' `title`, `text`, `type`, `values`,'.
			' `active`, `listed`, `exposed`, `visible`, `required`'.
			' FROM '.$this->cm_db->table_name('form_questions').
			' WHERE `question_id` = ? AND `context` = ? LIMIT 1'
		);
		$stmt->bind_param('is', $id, $this->context);
		$stmt->execute();
		$stmt->bind_result(
			$question_id, $context, $order,
			$title, $text, $type, $values,
			$active, $listed, $exposed, $visible, $required
		);
		if ($stmt->fetch()) {
			$result = array(
				'question-id' => $question_id,
				'context' => $context,
				'order' => $order,
				'title' => $title,
				'text' => $text,
				'type' => $type,
				'values' => ($values ? explode("\n", $values) : array()),
				'active' => !!$active,
				'listed' => !!$listed,
				'exposed' => !!$exposed,
				'visible' => ($visible ? explode(',', $visible) : array()),
				'required' => ($required ? explode(',', $required) : array())
			);
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_questions() {
		$questions = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `question_id`, `context`, `order`,'.
			' `title`, `text`, `type`, `values`,'.
			' `active`, `listed`, `exposed`, `visible`, `required`'.
			' FROM '.$this->cm_db->table_name('form_questions').
			' WHERE `context` = ? ORDER BY `order`'
		);
		$stmt->bind_param('s', $this->context);
		$stmt->execute();
		$stmt->bind_result(
			$question_id, $context, $order,
			$title, $text, $type, $values,
			$active, $listed, $exposed, $visible, $required
		);
		while ($stmt->fetch()) {
			$questions[] = array(
				'question-id' => $question_id,
				'context' => $context,
				'order' => $order,
				'title' => $title,
				'text' => $text,
				'type' => $type,
				'values' => ($values ? explode("\n", $values) : array()),
				'active' => !!$active,
				'listed' => !!$listed,
				'exposed' => !!$exposed,
				'visible' => ($visible ? explode(',', $visible) : array()),
				'required' => ($required ? explode(',', $required) : array())
			);
		}
		$stmt->close();
		return $questions;
	}

	public function create_question($question) {
		if (!$question) return false;
		$this->cm_db->connection->autocommit(false);
		$stmt = $this->cm_db->connection->prepare(
			'SELECT IFNULL(MAX(`order`),0)+1 FROM '.
			$this->cm_db->table_name('form_questions').
			' WHERE `context` = ?'
		);
		$stmt->bind_param('s', $this->context);
		$stmt->execute();
		$stmt->bind_result($order);
		$stmt->fetch();
		$stmt->close();
		$title = (isset($question['title']) ? $question['title'] : '');
		$text = (isset($question['text']) ? $question['text'] : '');
		$type = (isset($question['type']) ? $question['type'] : '');
		$values = (isset($question['values']) ? implode("\n", $question['values']) : '');
		$active = (isset($question['active']) ? ($question['active'] ? 1 : 0) : 1);
		$listed = (isset($question['listed']) ? ($question['listed'] ? 1 : 0) : 0);
		$exposed = (isset($question['exposed']) ? ($question['exposed'] ? 1 : 0) : 0);
		$visible = (isset($question['visible']) ? implode(',', $question['visible']) : '*');
		$required = (isset($question['required']) ? implode(',', $question['required']) : '');
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('form_questions').' SET '.
			'`context` = ?, `order` = ?, '.
			'`title` = ?, `text` = ?, `type` = ?, `values` = ?, '.
			'`active` = ?, `listed` = ?, `exposed` = ?, `visible` = ?, `required` = ?'
		);
		$stmt->bind_param(
			'sissssiiiss',
			$this->context, $order,
			$title, $text, $type, $values,
			$active, $listed, $exposed, $visible, $required
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		$this->cm_db->connection->autocommit(true);
		return $id;
	}

	public function update_question($question) {
		if (!$question || !isset($question['question-id']) || !$question['question-id']) return false;
		$title = (isset($question['title']) ? $question['title'] : '');
		$text = (isset($question['text']) ? $question['text'] : '');
		$type = (isset($question['type']) ? $question['type'] : '');
		$values = (isset($question['values']) ? implode("\n", $question['values']) : '');
		$active = (isset($question['active']) ? ($question['active'] ? 1 : 0) : 1);
		$listed = (isset($question['listed']) ? ($question['listed'] ? 1 : 0) : 0);
		$exposed = (isset($question['exposed']) ? ($question['exposed'] ? 1 : 0) : 0);
		$visible = (isset($question['visible']) ? implode(',', $question['visible']) : '*');
		$required = (isset($question['required']) ? implode(',', $question['required']) : '');
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('form_questions').' SET '.
			'`title` = ?, `text` = ?, `type` = ?, `values` = ?, '.
			'`active` = ?, `listed` = ?, `exposed` = ?, `visible` = ?, `required` = ?'.
			' WHERE `question_id` = ? AND `context` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'ssssiiissis',
			$title, $text, $type, $values,
			$active, $listed, $exposed, $visible, $required,
			$question['question-id'],
			$this->context
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_question($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('form_questions').
			' WHERE `question_id` = ? AND `context` = ? LIMIT 1'
		);
		$stmt->bind_param('is', $id, $this->context);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function get_question_order() {
		$ids = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `question_id`'.
			' FROM '.$this->cm_db->table_name('form_questions').
			' WHERE `context` = ? ORDER BY `order`'
		);
		$stmt->bind_param('s', $this->context);
		$stmt->execute();
		$stmt->bind_result($id);
		while ($stmt->fetch()) $ids[] = $id;
		$stmt->close();
		return $ids;
	}

	public function set_question_order($newids) {
		if (!$newids) return false;
		$this->cm_db->connection->autocommit(false);
		$oldids = $this->get_question_order();
		foreach ($oldids as $id) {
			if (!in_array($id, $newids)) {
				$newids[] = $id;
			}
		}
		foreach ($newids as $i => $id) {
			$stmt = $this->cm_db->connection->prepare(
				'UPDATE '.$this->cm_db->table_name('form_questions').
				' SET `order` = ?'.
				' WHERE `question_id` = ? AND `context` = ? LIMIT 1'
			);
			$ni = $i + 1;
			$stmt->bind_param('iis', $ni, $id, $this->context);
			$stmt->execute();
			$stmt->close();
		}
		$this->cm_db->connection->autocommit(true);
		return $this->get_question_order();
	}

	public function question_is_visible($question, $subcontext) {
		return ($question && $question['visible'] && (
			in_array('*', $question['visible']) ||
			in_array($subcontext, $question['visible'])
		));
	}

	public function question_is_required($question, $subcontext) {
		return ($question && $question['required'] && (
			in_array('*', $question['required']) ||
			in_array($subcontext, $question['required'])
		));
	}

	public function get_answer($context_id, $question_id) {
		if (!$context_id || !$question_id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `answer`'.
			' FROM '.$this->cm_db->table_name('form_answers').
			' WHERE `question_id` = ? AND `context` = ? AND `context_id` = ? LIMIT 1'
		);
		$stmt->bind_param('isi', $question_id, $this->context, $context_id);
		$stmt->execute();
		$stmt->bind_result($text);
		if ($stmt->fetch()) {
			$stmt->close();
			return ($text ? explode("\n", $text) : array());
		}
		$stmt->close();
		return false;
	}

	public function list_answers($context_id) {
		if (!$context_id) return false;
		$answers = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `question_id`, `answer`'.
			' FROM '.$this->cm_db->table_name('form_answers').
			' WHERE `context` = ? AND `context_id` = ? ORDER BY `question_id`'
		);
		$stmt->bind_param('si', $this->context, $context_id);
		$stmt->execute();
		$stmt->bind_result($question_id, $text);
		while ($stmt->fetch()) {
			$answers[$question_id] = ($text ? explode("\n", $text) : array());
		}
		$stmt->close();
		return $answers;
	}

	public function set_answer($context_id, $question_id, $answer) {
		if (!$context_id || !$question_id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('form_answers').' SET '.
			'`question_id` = ?, `context` = ?, `context_id` = ?, `answer` = ?'.
			' ON DUPLICATE KEY UPDATE '.
			'`question_id` = ?, `context` = ?, `context_id` = ?, `answer` = ?'
		);
		$text = implode("\n", $answer);
		$stmt->bind_param(
			'isisisis',
			$question_id, $this->context, $context_id, $text,
			$question_id, $this->context, $context_id, $text
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function set_answers($context_id, $answers) {
		if (!$context_id) return false;
		if (!$answers) return true;
		foreach ($answers as $question_id => $answer) {
			if ($answer) {
				if (!$this->set_answer($context_id, $question_id, $answer)) {
					return false;
				}
			}
		}
		return true;
	}

	public function clear_answer($context_id, $question_id) {
		if (!$context_id || !$question_id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('form_answers').
			' WHERE `question_id` = ? AND `context` = ? AND `context_id` = ? LIMIT 1'
		);
		$stmt->bind_param('isi', $question_id, $this->context, $context_id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function clear_answers($context_id) {
		if (!$context_id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('form_answers').
			' WHERE `context` = ? AND `context_id` = ?'
		);
		$stmt->bind_param('si', $this->context, $context_id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

}