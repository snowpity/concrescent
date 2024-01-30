<?php

require_once __DIR__ .'/database.php';

class cm_misc_db {

	public cm_db $cm_db;

	public function __construct(cm_db $cm_db) {
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('config_misc', (
			'`key` VARCHAR(255) NOT NULL PRIMARY KEY,'.
			'`value` TEXT NULL'
		));
		$this->cm_db->table_def('config_misc_files', (
			'`file_name` VARCHAR(255) NOT NULL PRIMARY KEY,'.
			'`mime_type` VARCHAR(255) NULL,'.
			'`image_w` INT NULL,'.
			'`image_h` INT NULL,'.
			'`data` LONGBLOB NULL'
		));
	}

	public function getval($key, $def = null) {
		if (!$key) return $def;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `value` FROM '.$this->cm_db->table_name('config_misc').
			' WHERE `key` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $key);
		$stmt->execute();
		$stmt->bind_result($value);
		$success = $stmt->fetch() && $value;
		$stmt->close();
		return $success ? $value : $def;
	}

	public function setval($key, $value) {
		if (!$key) return false;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('config_misc').
			' SET `key` = ?, `value` = ?'.
			' ON DUPLICATE KEY UPDATE `value` = ?'
		);
		$stmt->bind_param('sss', $key, $value, $value);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function clearval($key) {
		if (!$key) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('config_misc').
			' WHERE `key` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $key);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function upload_file($name, $type, $image_w, $image_h, $file) {
		if (!$name || !$type || !$file) return false;
		$this->cm_db->connection->autocommit(false);
		$stmt = $this->cm_db->connection->prepare(
			'SELECT 1 FROM '.$this->cm_db->table_name('config_misc_files').
			' WHERE `file_name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->bind_result($exists);
		$exists = $stmt->fetch() && $exists;
		$stmt->close();
		$null = null;
		if ($exists) {
			$stmt = $this->cm_db->connection->prepare(
				'UPDATE '.$this->cm_db->table_name('config_misc_files').' SET '.
				'`file_name` = ?, `mime_type` = ?, `image_w` = ?, `image_h` = ?, `data` = ?'.
				' WHERE `file_name` = ? LIMIT 1'
			);
			$stmt->bind_param('ssiibs', $name, $type, $image_w, $image_h, $null, $name);
		} else {
			$stmt = $this->cm_db->connection->prepare(
				'INSERT INTO '.$this->cm_db->table_name('config_misc_files').' SET '.
				'`file_name` = ?, `mime_type` = ?, `image_w` = ?, `image_h` = ?, `data` = ?'
			);
			$stmt->bind_param('ssiib', $name, $type, $image_w, $image_h, $null);
		}
		$fp = fopen($file, 'r');
		if ($fp) {
			while (!feof($fp)) $stmt->send_long_data(4, fread($fp, 65536));
			fclose($fp);
			$success = $stmt->execute();
		} else {
			$success = false;
		}
		$stmt->close();
		$this->cm_db->connection->autocommit(true);
		return $success;
	}

	public function download_file($name, $attachment = false) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `mime_type`, `data`'.
			' FROM '.$this->cm_db->table_name('config_misc_files').
			' WHERE `file_name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->bind_result($type, $data);
		if ($stmt->fetch() && $type && $data) {
			if ($attachment) {
				if ($attachment !== true) {
					$name = $attachment;
				}
				if (!strrpos($name, '.')) {
					$o = strrpos($type, '/');
					if ($o) $name .= '.' . substr($type, $o + 1);
				}
				header('Content-Disposition: attachment; filename=' . $name);
			}
			header('Content-Type: ' . $type);
			header('Pragma: no-cache');
			header('Expires: 0');
			echo $data;
			$stmt->close();
			return true;
		}
		$stmt->close();
		return false;
	}

	public function get_file_image_size($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `image_w`, `image_h`'.
			' FROM '.$this->cm_db->table_name('config_misc_files').
			' WHERE `file_name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->bind_result($image_w, $image_h);
		if ($stmt->fetch() && $image_w && $image_h) {
			$size = array($image_w, $image_h);
			$stmt->close();
			return $size;
		}
		$stmt->close();
		return false;
	}

	public function delete_file($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('config_misc_files').
			' WHERE `file_name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}


	public function getBadgeTypesFromQuestionAnswer(string $questionId): array
	{
		$stmt = $this->cm_db->connection->prepare(
			"select
			    attendees.badge_type_id as badge_id,
			    attendee_badge_types.name as badge_name,
			    form_answers.answer as answer
			from attendees
			inner join form_answers
			on form_answers.question_id = ? and attendees.id = form_answers.context_id
			inner join attendee_badge_types
			on attendees.badge_type_id = attendee_badge_types.id"
		);
		$stmt->bind_param('i', $questionId);
		$stmt->execute();
		$results = $stmt->get_result();
		$stmt->close();
		return $results->fetch_all(MYSQLI_ASSOC);
	}
}
