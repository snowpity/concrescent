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
			'SELECT `value` FROM `config_misc`' .
			' WHERE `key` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $key);
		$stmt->execute();
		$stmt->bind_result($value);
		$success = $stmt->fetch() && $value;
		return $success ? $value : $def;
	}

	public function setval($key, $value) {
		if (!$key) return false;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO `config_misc`' .
			' SET `key` = ?, `value` = ?'.
			' ON DUPLICATE KEY UPDATE `value` = ?'
		);
		$stmt->bind_param('sss', $key, $value, $value);
		$success = $stmt->execute();
		return $success;
	}

	public function clearval($key) {
		if (!$key) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM `config_misc`' .
			' WHERE `key` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $key);
		$success = $stmt->execute();
		return $success;
	}

	// TODO (Mr. Metric): this is a duplicate of badge-artwork.php/upload_badge_artwork
	public function upload_file(string $name, $type, $image_w, $image_h, $file): bool
	{
		if(!$type || !$file) { return false; }

		if($this->cm_db->table_has_row('config_misc_files', 'file_name', $name))
		{
			$sql = 'UPDATE `config_misc_files` SET'
				.' `file_name` = :file_name, `mime_type` = :mime_type, `image_w` = :image_w, `image_h` = :image_h, `data` = :data'
				.' WHERE `file_name` = :file_name';
		}
		else
		{
			$sql = 'INSERT INTO `config_misc_files`'
				.' (`file_name`, `mime_type`, `image_w`, `image_h`, `data`)'
				.' VALUES (:file_name, :mime_type, :image_w, :image_h, :data)';
		}

		$data = file_get_contents($file);
		if($data === false)
		{
			return false;
		}

		return $this->cm_db->prepare($sql)->execute([
			':file_name' => $name   ,
			':mime_type' => $type   ,
			':image_w'   => $image_w,
			':image_h'   => $image_h,
			':data'      => $data   ,
		]);
	}

	public function download_file($name, $attachment = false) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `mime_type`, `data`'.
			' FROM `config_misc_files`' .
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
			return true;
		}
		return false;
	}

	public function get_file_image_size($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `image_w`, `image_h`'.
			' FROM `config_misc_files`' .
			' WHERE `file_name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->bind_result($image_w, $image_h);
		if ($stmt->fetch() && $image_w && $image_h) {
			$size = array($image_w, $image_h);
			return $size;
		}
		return false;
	}

	public function delete_file($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM `config_misc_files`' .
			' WHERE `file_name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$success = $stmt->execute();
		return $success;
	}


	public function getBadgeTypesFromQuestionAnswer(string $creditId, string $approvalId): array
	{
		return $this->cm_db->execute(
			"SELECT attendees.badge_type_id AS badge_id,
				attendee_badge_types.name AS badge_name,
				form_credits.answer AS answer
			FROM `attendees`
				INNER JOIN `form_answers` AS form_credits
					ON form_credits.question_id = :credit_id
						AND form_credits.context = 'attendee'
						AND attendees.id = form_credits.context_id
				INNER JOIN `form_answers` AS forms_approval
					ON forms_approval.question_id = :approval_id
						AND forms_approval.context = 'attendee'
						AND attendees.id = forms_approval.context_id
				INNER JOIN `attendee_badge_types`
					ON attendees.badge_type_id = attendee_badge_types.id
			ORDER BY attendees.id"
		, [
			':credit_id'   => $creditId  ,
			':approval_id' => $approvalId,
		])->fetchAll(PDO::FETCH_ASSOC);
	}
}
