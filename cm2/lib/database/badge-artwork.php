<?php

require_once __DIR__ .'/database.php';

class cm_badge_artwork_db {

	public $field_keys = array(
		'only-name' => 'Only Name',
		'large-name' => 'Large Name',
		'small-name' => 'Small Name',
		'badge-type-name' => 'Badge Type Name',
		'application-name' => 'Application Name',
		'assigned-room-or-table-id' => 'Assigned Room or Table',
		'assigned-department-name' => 'Assigned Department',
		'assigned-position-name' => 'Assigned Position',
		'assigned-position-name-s' => 'Assigned Department & Position (Concatenated)',
		'assigned-position-name-h' => 'Assigned Department & Position (Hyphenated)',
		'id-string' => 'Badge ID',
		'uuid' => 'UUID',
		'qr-data' => 'QR Code Data',
		'img-src=qr-url' => 'QR Code Image',
		'first-name' => 'First Name',
		'last-name' => 'Last Name',
		'real-name' => 'Real Name',
		'fandom-name' => 'Fandom Name',
		'display-name' => 'Display Name'
	);

	public $cm_db;

	public function __construct($cm_db) {
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('badge_artwork_files', (
			'`file_name` VARCHAR(255) NOT NULL PRIMARY KEY,'.
			'`mime_type` VARCHAR(255) NULL,'.
			'`image_w` INT NULL,'.
			'`image_h` INT NULL,'.
			'`data` LONGBLOB NULL'
		));
		$this->cm_db->table_def('badge_artwork_fields', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`file_name` VARCHAR(255) NOT NULL,'.
			'`x1` DECIMAL(7,6) NOT NULL,'.
			'`y1` DECIMAL(7,6) NOT NULL,'.
			'`x2` DECIMAL(7,6) NOT NULL,'.
			'`y2` DECIMAL(7,6) NOT NULL,'.
			'`field_key` VARCHAR(255) NOT NULL,'.
			'`font_size` INTEGER NULL,'.
			'`font_family` VARCHAR(255) NULL,'.
			'`font_weight_bold` BOOLEAN NULL,'.
			'`font_style_italic` BOOLEAN NULL,'.
			'`color` VARCHAR(255) NULL,'.
			'`background` VARCHAR(255) NULL,'.
			'`color_minors` VARCHAR(255) NULL,'.
			'`background_minors` VARCHAR(255) NULL'
		));
		$this->cm_db->table_def('badge_artwork_map', (
			'`context` VARCHAR(255) NOT NULL,'.
			'`context_id` INTEGER NOT NULL,'.
			'`file_name` VARCHAR(255) NOT NULL'
		));
	}

	public function upload_badge_artwork($name, $type, $image_w, $image_h, $file) {
		if (!$name || !$type || !$file) return false;
		$this->cm_db->connection->autocommit(false);
		$stmt = $this->cm_db->connection->prepare(
			'SELECT 1 FROM '.$this->cm_db->table_name('badge_artwork_files').
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
				'UPDATE '.$this->cm_db->table_name('badge_artwork_files').' SET '.
				'`file_name` = ?, `mime_type` = ?, `image_w` = ?, `image_h` = ?, `data` = ?'.
				' WHERE `file_name` = ? LIMIT 1'
			);
			$stmt->bind_param('ssiibs', $name, $type, $image_w, $image_h, $null, $name);
		} else {
			$stmt = $this->cm_db->connection->prepare(
				'INSERT INTO '.$this->cm_db->table_name('badge_artwork_files').' SET '.
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

	public function download_badge_artwork($name, $attachment = false) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `mime_type`, `data`'.
			' FROM '.$this->cm_db->table_name('badge_artwork_files').
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

	public function get_badge_artwork($name, $include_data = false) {
		if (!$name) return false;
		if ($include_data) {
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `file_name`, `mime_type`, `image_w`, `image_h`, `data`'.
				' FROM '.$this->cm_db->table_name('badge_artwork_files').
				' WHERE `file_name` = ? LIMIT 1'
			);
		} else {
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `file_name`, `mime_type`, `image_w`, `image_h`'.
				' FROM '.$this->cm_db->table_name('badge_artwork_files').
				' WHERE `file_name` = ? LIMIT 1'
			);
		}
		$stmt->bind_param('s', $name);
		$stmt->execute();
		if ($include_data) {
			$stmt->bind_result($file_name, $mime_type, $image_w, $image_h, $data);
		} else {
			$stmt->bind_result($file_name, $mime_type, $image_w, $image_h);
		}
		if ($stmt->fetch()) {
			$vertical = ($image_h > $image_w);
			$aspect_ratio = $image_h * 100 / $image_w;
			$search_content = array($file_name);
			$result = array(
				'file-name' => $file_name,
				'mime-type' => $mime_type,
				'image-w' => $image_w,
				'image-h' => $image_h,
				'vertical' => $vertical,
				'aspect-ratio' => $aspect_ratio,
				'search-content' => $search_content
			);
			if ($include_data) {
				$result['data-md5'] = md5($data);
				if ($include_data == 'base64') {
					$result['data-base64'] = base64_encode($data);
				} else {
					$result['data'] = $data;
				}
			}
			$stmt->close();
			$result['fields'] = $this->list_badge_artwork_fields($name);
			$result['map'] = $this->get_badge_artwork_map(null, null, $name);
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_badge_artwork_names() {
		$names = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `file_name`'.
			' FROM '.$this->cm_db->table_name('badge_artwork_files').
			' ORDER BY `file_name`'
		);
		$stmt->execute();
		$stmt->bind_result($name);
		while ($stmt->fetch()) {
			$names[] = $name;
		}
		$stmt->close();
		return $names;
	}

	public function list_badge_artwork() {
		$badge_artwork = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `file_name`, `mime_type`, `image_w`, `image_h`'.
			' FROM '.$this->cm_db->table_name('badge_artwork_files').
			' ORDER BY `file_name`'
		);
		$stmt->execute();
		$stmt->bind_result($file_name, $mime_type, $image_w, $image_h);
		while ($stmt->fetch()) {
			$vertical = ($image_h > $image_w);
			$aspect_ratio = $image_h * 100 / $image_w;
			$search_content = array($file_name);
			$badge_artwork[] = array(
				'file-name' => $file_name,
				'mime-type' => $mime_type,
				'image-w' => $image_w,
				'image-h' => $image_h,
				'vertical' => $vertical,
				'aspect-ratio' => $aspect_ratio,
				'search-content' => $search_content
			);
		}
		$stmt->close();
		foreach ($badge_artwork as $i => $ba) {
			$badge_artwork[$i]['fields'] = $this->list_badge_artwork_fields($ba['file-name']);
			$badge_artwork[$i]['map'] = $this->get_badge_artwork_map(null, null, $ba['file-name']);
		}
		return $badge_artwork;
	}

	public function list_badge_artwork_fields($name) {
		if (!$name) return false;
		$fields = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `file_name`, `x1`, `y1`, `x2`, `y2`,'.
			' `field_key`, `font_size`, `font_family`,'.
			' `font_weight_bold`, `font_style_italic`, `color`,'.
			' `background`, `color_minors`, `background_minors`'.
			' FROM '.$this->cm_db->table_name('badge_artwork_fields').
			' WHERE `file_name` = ? ORDER BY `id`'
		);
		$stmt->bind_param('s', $name);
		$stmt->execute();
		$stmt->bind_result(
			$id, $file_name, $x1, $y1, $x2, $y2,
			$field_key, $font_size, $font_family,
			$font_weight_bold, $font_style_italic, $color,
			$background, $color_minors, $background_minors
		);
		while ($stmt->fetch()) {
			$fields[] = array(
				'id' => $id,
				'file-name' => $file_name,
				'x1' => $x1,
				'y1' => $y1,
				'x2' => $x2,
				'y2' => $y2,
				'field-key' => $field_key,
				'font-size' => $font_size,
				'font-family' => $font_family,
				'font-weight-bold' => !!$font_weight_bold,
				'font-style-italic' => !!$font_style_italic,
				'color' => $color,
				'background' => $background,
				'color-minors' => $color_minors,
				'background-minors' => $background_minors
			);
		}
		$stmt->close();
		return $fields;
	}

	public function create_badge_artwork_field($field) {
		if (!$field) return false;
		$file_name = ($field['file-name'] ?? null);
		$x1 = (isset($field['x1']) ? (float)$field['x1'] : null);
		$y1 = (isset($field['y1']) ? (float)$field['y1'] : null);
		$x2 = (isset($field['x2']) ? (float)$field['x2'] : null);
		$y2 = (isset($field['y2']) ? (float)$field['y2'] : null);
		$field_key = ($field['field-key'] ?? null);
		$font_size = ($field['font-size'] ?? null);
		$font_family = ($field['font-family'] ?? null);
		$font_weight_bold = (isset($field['font-weight-bold']) ? ($field['font-weight-bold'] ? 1 : 0) : null);
		$font_style_italic = (isset($field['font-style-italic']) ? ($field['font-style-italic'] ? 1 : 0) : null);
		$color = ($field['color'] ?? null);
		$background = ($field['background'] ?? null);
		$color_minors = ($field['color-minors'] ?? null);
		$background_minors = ($field['background-minors'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('badge_artwork_fields').' SET '.
			'`file_name` = ?, `x1` = ?, `y1` = ?, `x2` = ?, `y2` = ?, '.
			'`field_key` = ?, `font_size` = ?, `font_family` = ?, '.
			'`font_weight_bold` = ?, `font_style_italic` = ?, `color` = ?, '.
			'`background` = ?, `color_minors` = ?, `background_minors` = ?'
		);
		$stmt->bind_param(
			'sddddsisiissss',
			$file_name, $x1, $y1, $x2, $y2,
			$field_key, $font_size, $font_family,
			$font_weight_bold, $font_style_italic, $color,
			$background, $color_minors, $background_minors
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		return $id;
	}

	public function update_badge_artwork_field($field) {
		if (!$field || !isset($field['id']) || !$field['id']) return false;
		$file_name = ($field['file-name'] ?? null);
		$x1 = (isset($field['x1']) ? (float)$field['x1'] : null);
		$y1 = (isset($field['y1']) ? (float)$field['y1'] : null);
		$x2 = (isset($field['x2']) ? (float)$field['x2'] : null);
		$y2 = (isset($field['y2']) ? (float)$field['y2'] : null);
		$field_key = ($field['field-key'] ?? null);
		$font_size = ($field['font-size'] ?? null);
		$font_family = ($field['font-family'] ?? null);
		$font_weight_bold = (isset($field['font-weight-bold']) ? ($field['font-weight-bold'] ? 1 : 0) : null);
		$font_style_italic = (isset($field['font-style-italic']) ? ($field['font-style-italic'] ? 1 : 0) : null);
		$color = ($field['color'] ?? null);
		$background = ($field['background'] ?? null);
		$color_minors = ($field['color-minors'] ?? null);
		$background_minors = ($field['background-minors'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('badge_artwork_fields').' SET '.
			'`file_name` = ?, `x1` = ?, `y1` = ?, `x2` = ?, `y2` = ?, '.
			'`field_key` = ?, `font_size` = ?, `font_family` = ?, '.
			'`font_weight_bold` = ?, `font_style_italic` = ?, `color` = ?, '.
			'`background` = ?, `color_minors` = ?, `background_minors` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'sddddsisiissssi',
			$file_name, $x1, $y1, $x2, $y2,
			$field_key, $font_size, $font_family,
			$font_weight_bold, $font_style_italic, $color,
			$background, $color_minors, $background_minors,
			$field['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_badge_artwork_field($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('badge_artwork_fields').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function upload_badge_artwork_fields($name, $file) {
		if (!$name || !$file) return false;
		$in = fopen($file, 'r');
		if (!$in) return false;
		while (($row = fgetcsv($in))) {
			if (count($row) < 5) continue;
			$this->create_badge_artwork_field(array(
				'file-name' => $name,
				'x1' => (float)$row[1],
				'y1' => (float)$row[2],
				'x2' => (float)$row[3],
				'y2' => (float)$row[4],
				'field-key' => trim($row[0]),
				'font-size' => (isset($row[5]) && strlen(trim($row[5]))) ? (int)trim($row[5]) : null,
				'font-family' => (isset($row[6]) && strlen(trim($row[6]))) ? trim($row[6]) : null,
				'font-weight-bold' => (isset($row[7]) && strlen(trim($row[7]))) ? (trim($row[7]) ? 1 : 0) : null,
				'font-style-italic' => (isset($row[8]) && strlen(trim($row[8]))) ? (trim($row[8]) ? 1 : 0) : null,
				'color' => (isset($row[9]) && strlen(trim($row[9]))) ? trim($row[9]) : null,
				'background' => (isset($row[10]) && strlen(trim($row[10]))) ? trim($row[10]) : null,
				'color-minors' => (isset($row[11]) && strlen(trim($row[11]))) ? trim($row[11]) : null,
				'background-minors' => (isset($row[12]) && strlen(trim($row[12]))) ? trim($row[12]) : null
			));
		}
		fclose($in);
		return true;
	}

	public function download_badge_artwork_fields($name) {
		if (!$name) return false;
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename='.$name.'-fields.csv');
		header('Pragma: no-cache');
		header('Expires: 0');
		$out = fopen('php://output', 'w');
		$fields = $this->list_badge_artwork_fields($name);
		foreach ($fields as $field) {
			$row = array(
				$field['field-key'],
				$field['x1'], $field['y1'],
				$field['x2'], $field['y2'],
				$field['font-size'], $field['font-family'],
				($field['font-weight-bold'] ? '1' : '0'),
				($field['font-style-italic'] ? '1' : '0'),
				$field['color'], $field['background'],
				$field['color-minors'], $field['background-minors']
			);
			fputcsv($out, $row);
		}
		fclose($out);
		exit(0);
	}

	public function copy_badge_artwork_fields($from_name, $to_name) {
		if (!$from_name || !$to_name) return false;
		$fields = $this->list_badge_artwork_fields($from_name);
		foreach ($fields as $field) {
			$field['file-name'] = $to_name;
			$this->create_badge_artwork_field($field);
		}
		return true;
	}

	public function delete_badge_artwork_fields($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('badge_artwork_fields').
			' WHERE `file_name` = ?'
		);
		$stmt->bind_param('s', $name);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function get_badge_artwork_map($context, $context_id, $file_name) {
		$query = (
			'SELECT `context`, `context_id`, `file_name`'.
			' FROM '.$this->cm_db->table_name('badge_artwork_map')
		);
		$first = true;
		$bind = array('');
		if ($context) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `context` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$context;
		}
		if ($context_id) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `context_id` = ?';
			$first = false;
			$bind[0] .= 'i';
			$bind[] = &$context_id;
		}
		if ($file_name) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `file_name` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$file_name;
		}
		$query .= ' ORDER BY `context`, `context_id`, `file_name`';
		$stmt = $this->cm_db->connection->prepare($query);
		if (!$first) call_user_func_array(array($stmt, 'bind_param'), $bind);
		$stmt->execute();
		$stmt->bind_result($context, $context_id, $file_name);
		$map = array();
		while ($stmt->fetch()) {
			$map[] = array(
				'context' => $context,
				'context-id' => $context_id,
				'file-name' => $file_name
			);
		}
		$stmt->close();
		return $map;
	}

	public function set_badge_artwork_map($context, $context_id, $file_name) {
		if (!$context || !$context_id || !$file_name) return false;
		$this->cm_db->connection->autocommit(false);
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('badge_artwork_map').
			' WHERE `context` = ? AND `context_id` = ? AND `file_name` = ?'
		);
		$stmt->bind_param('sis', $context, $context_id, $file_name);
		$stmt->execute();
		$stmt->close();
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('badge_artwork_map').
			' SET `context` = ?, `context_id` = ?, `file_name` = ?'
		);
		$stmt->bind_param('sis', $context, $context_id, $file_name);
		$success = $stmt->execute();
		$stmt->close();
		$this->cm_db->connection->autocommit(true);
		return $success;
	}

	public function clear_badge_artwork_map($context, $context_id, $file_name) {
		if (!$context && !$context_id && !$file_name) return false;
		$query = 'DELETE FROM '.$this->cm_db->table_name('badge_artwork_map');
		$first = true;
		$bind = array('');
		if ($context) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `context` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$context;
		}
		if ($context_id) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `context_id` = ?';
			$first = false;
			$bind[0] .= 'i';
			$bind[] = &$context_id;
		}
		if ($file_name) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `file_name` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$file_name;
		}
		$stmt = $this->cm_db->connection->prepare($query);
		if (!$first) call_user_func_array(array($stmt, 'bind_param'), $bind);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_badge_artwork($name) {
		if (!$name) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('badge_artwork_files').
			' WHERE `file_name` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $name);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$stmt = $this->cm_db->connection->prepare(
				'DELETE FROM '.$this->cm_db->table_name('badge_artwork_fields').
				' WHERE `file_name` = ?'
			);
			$stmt->bind_param('s', $name);
			$stmt->execute();
			$stmt->close();
			$stmt = $this->cm_db->connection->prepare(
				'DELETE FROM '.$this->cm_db->table_name('badge_artwork_map').
				' WHERE `file_name` = ?'
			);
			$stmt->bind_param('s', $name);
			$stmt->execute();
			$stmt->close();
		}
		return $success;
	}

}
