<?php

use JetBrains\PhpStorm\NoReturn;

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../util/util.php';
require_once __DIR__ .'/../util/res.php';
require_once __DIR__ .'/database.php';
require_once __DIR__ .'/lists.php';
require_once __DIR__ .'/forms.php';

class cm_application_db {

	public array $max_prereg_discounts = array(
		'No Discount',
		'Price per Applicant',
		'Price per Assignment',
		'Total Price'
	);
	public array $application_statuses = array(
		'Submitted',
		'Cancelled',
		'Accepted',
		'Waitlisted',
		'Rejected'
	);
	public array $payment_statuses = array(
		'Incomplete',
		'Cancelled',
		'Rejected',
		'Completed',
		'Refunded'
	);
	public array $names_on_badge = array(
		'Fandom Name Large, Real Name Small',
		'Real Name Large, Fandom Name Small',
		'Fandom Name Only',
		'Real Name Only'
	);

	public mixed $event_info;
	public cm_db $cm_db;
	public ?string $ctx_uc;
	public ?string $ctx_lc;
	public mixed $ctx_info;
	public ?cm_lists_db $cm_anldb;
	public ?cm_lists_db $cm_atldb;

	public function __construct(cm_db $cm_db, $context) {
		$this->event_info = $GLOBALS['cm_config']['event'];
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('rooms_and_tables', (
			'`id` VARCHAR(255) NOT NULL PRIMARY KEY,'.
			'`x1` DECIMAL(7,6) NOT NULL,'.
			'`y1` DECIMAL(7,6) NOT NULL,'.
			'`x2` DECIMAL(7,6) NOT NULL,'.
			'`y2` DECIMAL(7,6) NOT NULL'
		));
		$this->cm_db->table_def('room_and_table_assignments', (
			'`context` VARCHAR(255) NOT NULL,'.
			'`context_id` INTEGER NOT NULL,'.
			'`room_or_table_id` VARCHAR(255) NOT NULL,'.
			'`start_time` DATETIME NOT NULL,'.
			'`end_time` DATETIME NOT NULL'
		));
		if ($context) {
			$this->ctx_uc = strtoupper($context);
			$this->ctx_lc = strtolower($context);
			$this->ctx_info = $GLOBALS['cm_config']['application_types'][$this->ctx_uc];
			$this->cm_anldb = new cm_lists_db($this->cm_db, 'application_search_index_'.$this->ctx_lc);
			$this->cm_atldb = new cm_lists_db($this->cm_db, 'applicant_search_index_'.$this->ctx_lc);
			$this->cm_db->table_def('application_badge_types_'.$this->ctx_lc, (
				'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
				'`order` INTEGER NOT NULL,'.
				'`name` VARCHAR(255) NOT NULL,'.
				'`description` TEXT NULL,'.
				'`rewards` TEXT NULL,'.
				'`max_applicant_count` INTEGER NULL,'.
				'`max_assignment_count` INTEGER NULL,'.
				'`base_price` DECIMAL(7,2) NOT NULL,'.
				'`base_applicant_count` INTEGER NOT NULL,'.
				'`base_assignment_count` INTEGER NOT NULL,'.
				'`price_per_applicant` DECIMAL(7,2) NOT NULL,'.
				'`price_per_assignment` DECIMAL(7,2) NOT NULL,'.
				'`max_prereg_discount` ENUM('.
					'\'No Discount\','.
					'\'Price per Applicant\','.
					'\'Price per Assignment\','.
					'\'Total Price\''.
				') NOT NULL,'.
				'`use_permit` BOOLEAN NOT NULL,'.
				'`require_permit` BOOLEAN NOT NULL,'.
				'`require_contract` BOOLEAN NOT NULL,'.
				'`active` BOOLEAN NOT NULL,'.
				'`max_total_applications` INTEGER NULL,'.
				'`max_total_applicants` INTEGER NULL,'.
				'`max_total_assignments` INTEGER NULL,'.
				'`start_date` DATE NULL,'.
				'`end_date` DATE NULL,'.
				'`min_age` INTEGER NULL,'.
				'`max_age` INTEGER NULL'
			));
			$this->cm_db->table_def('application_blacklist_'.$this->ctx_lc, (
				'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
				'`business_name` VARCHAR(255) NULL,'.
				'`application_name` VARCHAR(255) NULL,'.
				'`added_by` VARCHAR(255) NULL,'.
				'`notes` TEXT NULL,'.
				'`normalized_business_name` VARCHAR(255) NULL,'.
				'`normalized_application_name` VARCHAR(255) NULL'
			));
			$this->cm_db->table_def('applicant_blacklist_'.$this->ctx_lc, (
				'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
				'`first_name` VARCHAR(255) NULL,'.
				'`last_name` VARCHAR(255) NULL,'.
				'`fandom_name` VARCHAR(255) NULL,'.
				'`email_address` VARCHAR(255) NULL,'.
				'`phone_number` VARCHAR(255) NULL,'.
				'`added_by` VARCHAR(255) NULL,'.
				'`notes` TEXT NULL,'.
				'`normalized_real_name` VARCHAR(255) NULL,'.
				'`normalized_reversed_name` VARCHAR(255) NULL,'.
				'`normalized_fandom_name` VARCHAR(255) NULL,'.
				'`normalized_email_address` VARCHAR(255) NULL,'.
				'`normalized_phone_number` VARCHAR(255) NULL'
			));
			$this->cm_db->table_def('applications_'.$this->ctx_lc, (
				/* Record Info */
				'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
				'`uuid` VARCHAR(255) NOT NULL UNIQUE KEY,'.
				'`date_created` DATETIME NOT NULL,'.
				'`date_modified` DATETIME NOT NULL,'.
				'`badge_type_id` INTEGER NOT NULL,'.
				'`notes` TEXT NULL,'.
				/* Contact Info */
				'`contact_first_name` VARCHAR(255) NOT NULL,'.
				'`contact_last_name` VARCHAR(255) NOT NULL,'.
				'`contact_subscribed` BOOLEAN NOT NULL,'.
				'`contact_email_address` VARCHAR(255) NOT NULL,'.
				'`contact_phone_number` VARCHAR(255) NULL,'.
				'`contact_address_1` VARCHAR(255) NULL,'.
				'`contact_address_2` VARCHAR(255) NULL,'.
				'`contact_city` VARCHAR(255) NULL,'.
				'`contact_state` VARCHAR(255) NULL,'.
				'`contact_zip_code` VARCHAR(255) NULL,'.
				'`contact_country` VARCHAR(255) NULL,'.
				/* Application Info */
				'`business_name` VARCHAR(255) NOT NULL,'.
				'`application_name` VARCHAR(255) NOT NULL,'.
				'`applicant_count` INTEGER NOT NULL,'.
				'`assignment_count` INTEGER NOT NULL,'.
				'`application_status` ENUM('.
					'\'Submitted\','.
					'\'Cancelled\','.
					'\'Accepted\','.
					'\'Waitlisted\','.
					'\'Rejected\''.
				') NOT NULL,'.
				'`permit_number` VARCHAR(255) NULL,'.
				/* Payment Info */
				'`payment_status` ENUM('.
					'\'Incomplete\','.
					'\'Cancelled\','.
					'\'Rejected\','.
					'\'Completed\','.
					'\'Refunded\''.
				') NOT NULL,'.
				'`payment_badge_price` DECIMAL(7,2) NULL,'.
				'`payment_group_uuid` VARCHAR(255) NOT NULL,'.
				'`payment_type` VARCHAR(255) NULL,'.
				'`payment_txn_id` VARCHAR(255) NULL,'.
				'`payment_txn_amt` DECIMAL(7,2) NULL,'.
				'`payment_date` DATETIME NULL,'.
				'`payment_details` TEXT NULL'
			));
			$this->cm_db->table_def('applicants_'.$this->ctx_lc, (
				/* Record Info */
				'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
				'`uuid` VARCHAR(255) NOT NULL UNIQUE KEY,'.
				'`date_created` DATETIME NOT NULL,'.
				'`date_modified` DATETIME NOT NULL,'.
				'`print_count` INTEGER NULL,'.
				'`print_first_time` DATETIME NULL,'.
				'`print_last_time` DATETIME NULL,'.
				'`checkin_count` INTEGER NULL,'.
				'`checkin_first_time` DATETIME NULL,'.
				'`checkin_last_time` DATETIME NULL,'.
				'`application_id` INTEGER NOT NULL,'.
				'`attendee_id` INTEGER NULL,'.
				'`notes` TEXT NULL,'.
				/* Personal Info */
				'`first_name` VARCHAR(255) NOT NULL,'.
				'`last_name` VARCHAR(255) NOT NULL,'.
				'`fandom_name` VARCHAR(255) NULL,'.
				'`name_on_badge` ENUM('.
					'\'Fandom Name Large, Real Name Small\','.
					'\'Real Name Large, Fandom Name Small\','.
					'\'Fandom Name Only\','.
					'\'Real Name Only\''.
				') NOT NULL,'.
				'`date_of_birth` DATE NOT NULL,'.
				/* Contact Info */
				'`subscribed` BOOLEAN NOT NULL,'.
				'`email_address` VARCHAR(255) NOT NULL,'.
				'`phone_number` VARCHAR(255) NULL,'.
				'`address_1` VARCHAR(255) NULL,'.
				'`address_2` VARCHAR(255) NULL,'.
				'`city` VARCHAR(255) NULL,'.
				'`state` VARCHAR(255) NULL,'.
				'`zip_code` VARCHAR(255) NULL,'.
				'`country` VARCHAR(255) NULL,'.
				/* Emergency Contact Info */
				'`ice_name` VARCHAR(255) NULL,'.
				'`ice_relationship` VARCHAR(255) NULL,'.
				'`ice_email_address` VARCHAR(255) NULL,'.
				'`ice_phone_number` VARCHAR(255) NULL'
			));
		} else {
			$this->ctx_uc = null;
			$this->ctx_lc = null;
			$this->ctx_info = null;
			$this->cm_anldb = null;
			$this->cm_atldb = null;
		}
	}

	public function get_room_or_table($id, $expand = false) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `x1`, `y1`, `x2`, `y2`'.
			' FROM '.$this->cm_db->table_name('rooms_and_tables').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $id);
		$stmt->execute();
		$stmt->bind_result($id, $x1, $y1, $x2, $y2);
		if ($stmt->fetch()) {
			$result = array(
				'id' => $id,
				'x1' => $x1,
				'y1' => $y1,
				'x2' => $x2,
				'y2' => $y2
			);
			$stmt->close();
			if ($expand) {
				$assignments = $this->list_room_and_table_assignments($id);
				$result['assignments'] = $assignments;
			}
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_rooms_and_tables($expand = false) {
		$rooms_and_tables = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `x1`, `y1`, `x2`, `y2`'.
			' FROM '.$this->cm_db->table_name('rooms_and_tables')
		);
		$stmt->execute();
		$stmt->bind_result($id, $x1, $y1, $x2, $y2);
		while ($stmt->fetch()) {
			$rooms_and_tables[] = array(
				'id' => $id,
				'x1' => $x1,
				'y1' => $y1,
				'x2' => $x2,
				'y2' => $y2
			);
		}
		$stmt->close();
		if ($expand) {
			foreach ($rooms_and_tables as $i => $room_or_table) {
				$id = $room_or_table['id'];
				$assignments = $this->list_room_and_table_assignments($id);
				$rooms_and_tables[$i]['assignments'] = $assignments;
			}
		}
		usort($rooms_and_tables, function($a, $b) {
			return strnatcasecmp($a['id'], $b['id']);
		});
		return $rooms_and_tables;
	}

	public function list_room_and_table_assignments($id = null, $context = null) {
		$assignments = array();
		$query = (
			'SELECT `context`, `context_id`, `room_or_table_id`, `start_time`, `end_time`'.
			' FROM '.$this->cm_db->table_name('room_and_table_assignments')
		);
		$first = true;
		$bind = array('');
		if ($id) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `room_or_table_id` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$id;
		}
		if ($context) {
			$ctx_uc = strtoupper($context);
			$query .= ($first ? ' WHERE' : ' AND') . ' `context` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$ctx_uc;
		}
		$stmt = $this->cm_db->connection->prepare($query);
		if (!$first) call_user_func_array(array($stmt, 'bind_param'), $bind);
		$stmt->execute();
		$stmt->bind_result(
			$context, $context_id, $room_or_table_id, $start_time, $end_time
		);
		while ($stmt->fetch()) {
			$assignments[] = array(
				'context' => $context,
				'context-id' => $context_id,
				'room-or-table-id' => $room_or_table_id,
				'start-time' => $start_time,
				'end-time' => $end_time
			);
		}
		$stmt->close();
		foreach ($assignments as $i => $assignment) {
			$table_name = 'applications_'.strtolower($assignment['context']);
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `business_name`, `application_name`'.
				' FROM '.$this->cm_db->table_name($table_name).
				' WHERE `id` = ? LIMIT 1'
			);
			$stmt->bind_param('i', $assignment['context-id']);
			$stmt->execute();
			$stmt->bind_result($business_name, $application_name);
			if ($stmt->fetch()) {
				$assignments[$i]['business-name'] = $business_name;
				$assignments[$i]['application-name'] = $application_name;
			}
			$stmt->close();
		}
		usort($assignments, function($a, $b) {
			if (($cmp = strnatcasecmp($a['room-or-table-id'], $b['room-or-table-id']))) return $cmp;
			if (($cmp = strnatcasecmp($a['start-time'], $b['start-time']))) return $cmp;
			if (($cmp = strnatcasecmp($a['end-time'], $b['end-time']))) return $cmp;
			if (($cmp = strnatcasecmp($a['context'], $b['context']))) return $cmp;
			if (($cmp = strnatcasecmp(
				($a['application-name'] ?? ''),
				($b['application-name'] ?? '')
			))) return $cmp;
			return $a['context-id'] - $b['context-id'];
		});
		return $assignments;
	}

	public function create_room_or_table($rt) {
		if (!$rt || !isset($rt['id']) || !$rt['id']) return false;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('rooms_and_tables').' SET '.
			'`id` = ?, `x1` = ?, `y1` = ?, `x2` = ?, `y2` = ?'.
			' ON DUPLICATE KEY UPDATE '.
			'`id` = ?, `x1` = ?, `y1` = ?, `x2` = ?, `y2` = ?'
		);
		$stmt->bind_param(
			'sddddsdddd',
			$rt['id'], $rt['x1'], $rt['y1'], $rt['x2'], $rt['y2'],
			$rt['id'], $rt['x1'], $rt['y1'], $rt['x2'], $rt['y2']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function update_room_or_table($id, $rt) {
		if (!$id || !$rt || !isset($rt['id']) || !$rt['id']) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('rooms_and_tables').' SET '.
			'`id` = ?, `x1` = ?, `y1` = ?, `x2` = ?, `y2` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'sdddds',
			$rt['id'], $rt['x1'], $rt['y1'], $rt['x2'], $rt['y2'],
			$id
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_room_or_table($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('rooms_and_tables').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function upload_rooms_and_tables($file) {
		if (!$file) return false;
		$in = fopen($file, 'r');
		if (!$in) return false;
		while (($row = fgetcsv($in))) {
			if (count($row) < 5) continue;
			$this->create_room_or_table(array(
				'x1' => (float)$row[1],
				'y1' => (float)$row[2],
				'x2' => (float)$row[3],
				'y2' => (float)$row[4],
				'id' => trim($row[0])
			));
		}
		fclose($in);
		return true;
	}

	#[NoReturn]
	public function download_rooms_and_tables() {
		header('Content-Type: text/csv');
		header('Content-Disposition: attachment; filename=rooms-and-tables.csv');
		header('Pragma: no-cache');
		header('Expires: 0');
		$out = fopen('php://output', 'w');
		$rooms_and_tables = $this->list_rooms_and_tables();
		foreach ($rooms_and_tables as $rt) {
			$row = array($rt['id'], $rt['x1'], $rt['y1'], $rt['x2'], $rt['y2']);
			fputcsv($out, $row);
		}
		fclose($out);
		exit(0);
	}

	public function delete_rooms_and_tables() {
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('rooms_and_tables')
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function get_badge_type($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT b.`id`, b.`order`, b.`name`, b.`description`, b.`rewards`,'.
			' b.`max_applicant_count`, b.`max_assignment_count`, b.`base_price`,'.
			' b.`base_applicant_count`, b.`base_assignment_count`,'.
			' b.`price_per_applicant`, b.`price_per_assignment`,'.
			' b.`max_prereg_discount`, b.`use_permit`, b.`require_permit`,'.
			' b.`require_contract`, b.`active`, b.`max_total_applications`,'.
			' b.`max_total_applicants`, b.`max_total_assignments`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a1'.
			' WHERE a1.`badge_type_id` = b.`id` AND a1.`application_status` = \'Accepted\') c1,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a2'.
			' WHERE a2.`badge_type_id` = b.`id` AND a2.`payment_status` = \'Completed\') c2,'.
			' (SELECT IFNULL(SUM(a3.`applicant_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a3'.
			' WHERE a3.`badge_type_id` = b.`id` AND a3.`application_status` = \'Accepted\') c3,'.
			' (SELECT IFNULL(SUM(a4.`applicant_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a4'.
			' WHERE a4.`badge_type_id` = b.`id` AND a4.`payment_status` = \'Completed\') c4,'.
			' (SELECT IFNULL(SUM(a5.`assignment_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a5'.
			' WHERE a5.`badge_type_id` = b.`id` AND a5.`application_status` = \'Accepted\') c5,'.
			' (SELECT IFNULL(SUM(a6.`assignment_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a6'.
			' WHERE a6.`badge_type_id` = b.`id` AND a6.`payment_status` = \'Completed\') c6'.
			' FROM '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).' b'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $order, $name, $description, $rewards,
			$max_applicant_count, $max_assignment_count, $base_price,
			$base_applicant_count, $base_assignment_count,
			$price_per_applicant, $price_per_assignment,
			$max_prereg_discount, $use_permit, $require_permit,
			$require_contract, $active, $max_total_applications,
			$max_total_applicants, $max_total_assignments,
			$start_date, $end_date, $min_age, $max_age,
			$applications_accepted, $applications_sold,
			$applicants_accepted, $applicants_sold,
			$assignments_accepted, $assignments_sold
		);
		if ($stmt->fetch()) {
			$event_start_date = $this->event_info['start_date'];
			$event_end_date   = $this->event_info['end_date'  ];
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$result = array(
				'id' => $id,
				'id-string' => $this->ctx_uc . 'B' . $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'rewards' => ($rewards ? explode("\n", $rewards) : array()),
				'max-applicant-count' => $max_applicant_count,
				'max-assignment-count' => $max_assignment_count,
				'base-price' => $base_price,
				'base-applicant-count' => $base_applicant_count,
				'base-assignment-count' => $base_assignment_count,
				'price-per-applicant' => $price_per_applicant,
				'price-per-assignment' => $price_per_assignment,
				'max-prereg-discount' => $max_prereg_discount,
				'use-permit' => !!$use_permit,
				'require-permit' => !!$require_permit,
				'require-contract' => !!$require_contract,
				'active' => !!$active,
				'max-total-applications' => $max_total_applications,
				'applications-accepted' => $applications_accepted,
				'applications-sold' => $applications_sold,
				'applications-remaining' => (is_null($max_total_applications) ? null : ($max_total_applications - $applications_sold)),
				'max-total-applicants' => $max_total_applicants,
				'applicants-accepted' => $applicants_accepted,
				'applicants-sold' => $applicants_sold,
				'applicants-remaining' => (is_null($max_total_applicants) ? null : ($max_total_applicants - $applicants_sold)),
				'max-total-assignments' => $max_total_assignments,
				'assignments-accepted' => $assignments_accepted,
				'assignments-sold' => $assignments_sold,
				'assignments-remaining' => (is_null($max_total_assignments) ? null : ($max_total_assignments - $assignments_sold)),
				'start-date' => $start_date,
				'end-date' => $end_date,
				'min-age' => $min_age,
				'max-age' => $max_age,
				'min-birthdate' => $min_birthdate,
				'max-birthdate' => $max_birthdate,
				'search-content' => array($name, $description, $rewards)
			);
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function get_badge_type_name_map() {
		$badge_types = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `name`'.
			' FROM '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).
			' ORDER BY `order`'
		);
		$stmt->execute();
		$stmt->bind_result($id, $name);
		while ($stmt->fetch()) {
			$badge_types[$id] = $name;
		}
		$stmt->close();
		return $badge_types;
	}

	public function list_badge_type_names() {
		$badge_types = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `name`'.
			' FROM '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).
			' ORDER BY `order`'
		);
		$stmt->execute();
		$stmt->bind_result($id, $name);
		while ($stmt->fetch()) {
			$badge_types[] = array(
				'id' => $id,
				'name' => $name
			);
		}
		$stmt->close();
		return $badge_types;
	}

	public function list_badge_types($active_only = false, $unsold_only = false) {
		$badge_types = array();
		$query = (
			'SELECT b.`id`, b.`order`, b.`name`, b.`description`, b.`rewards`,'.
			' b.`max_applicant_count`, b.`max_assignment_count`, b.`base_price`,'.
			' b.`base_applicant_count`, b.`base_assignment_count`,'.
			' b.`price_per_applicant`, b.`price_per_assignment`,'.
			' b.`max_prereg_discount`, b.`use_permit`, b.`require_permit`,'.
			' b.`require_contract`, b.`active`, b.`max_total_applications`,'.
			' b.`max_total_applicants`, b.`max_total_assignments`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a1'.
			' WHERE a1.`badge_type_id` = b.`id` AND a1.`application_status` = \'Accepted\') c1,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a2'.
			' WHERE a2.`badge_type_id` = b.`id` AND a2.`payment_status` = \'Completed\') c2,'.
			' (SELECT IFNULL(SUM(a3.`applicant_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a3'.
			' WHERE a3.`badge_type_id` = b.`id` AND a3.`application_status` = \'Accepted\') c3,'.
			' (SELECT IFNULL(SUM(a4.`applicant_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a4'.
			' WHERE a4.`badge_type_id` = b.`id` AND a4.`payment_status` = \'Completed\') c4,'.
			' (SELECT IFNULL(SUM(a5.`assignment_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a5'.
			' WHERE a5.`badge_type_id` = b.`id` AND a5.`application_status` = \'Accepted\') c5,'.
			' (SELECT IFNULL(SUM(a6.`assignment_count`), 0) FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a6'.
			' WHERE a6.`badge_type_id` = b.`id` AND a6.`payment_status` = \'Completed\') c6'.
			' FROM '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).' b'
		);
		$first = true;
		if ($active_only) {
			$query .= (
				($first ? ' WHERE' : ' AND').' b.`active`'.
				' AND (b.`start_date` IS NULL OR b.`start_date` <= CURDATE())'.
				' AND (b.`end_date` IS NULL OR b.`end_date` >= CURDATE())'
			);
			$first = false;
		}
		$stmt = $this->cm_db->connection->prepare($query . ' ORDER BY b.`order`');
		$stmt->execute();
		$stmt->bind_result(
			$id, $order, $name, $description, $rewards,
			$max_applicant_count, $max_assignment_count, $base_price,
			$base_applicant_count, $base_assignment_count,
			$price_per_applicant, $price_per_assignment,
			$max_prereg_discount, $use_permit, $require_permit,
			$require_contract, $active, $max_total_applications,
			$max_total_applicants, $max_total_assignments,
			$start_date, $end_date, $min_age, $max_age,
			$applications_accepted, $applications_sold,
			$applicants_accepted, $applicants_sold,
			$assignments_accepted, $assignments_sold
		);
		$event_start_date = $this->event_info['start_date'];
		$event_end_date   = $this->event_info['end_date'  ];
		while ($stmt->fetch()) {
			if ($unsold_only) {
				if (!( is_null($max_total_applications) || $max_total_applications > $applications_sold )) continue;
				if (!( is_null($max_total_applicants  ) || $max_total_applicants   > $applicants_sold   )) continue;
				if (!( is_null($max_total_assignments ) || $max_total_assignments  > $assignments_sold  )) continue;
			}
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$badge_types[] = array(
				'id' => $id,
				'id-string' => $this->ctx_uc . 'B' . $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'rewards' => ($rewards ? explode("\n", $rewards) : array()),
				'max-applicant-count' => $max_applicant_count,
				'max-assignment-count' => $max_assignment_count,
				'base-price' => $base_price,
				'base-applicant-count' => $base_applicant_count,
				'base-assignment-count' => $base_assignment_count,
				'price-per-applicant' => $price_per_applicant,
				'price-per-assignment' => $price_per_assignment,
				'max-prereg-discount' => $max_prereg_discount,
				'use-permit' => !!$use_permit,
				'require-permit' => !!$require_permit,
				'require-contract' => !!$require_contract,
				'active' => !!$active,
				'max-total-applications' => $max_total_applications,
				'applications-accepted' => $applications_accepted,
				'applications-sold' => $applications_sold,
				'applications-remaining' => (is_null($max_total_applications) ? null : ($max_total_applications - $applications_sold)),
				'max-total-applicants' => $max_total_applicants,
				'applicants-accepted' => $applicants_accepted,
				'applicants-sold' => $applicants_sold,
				'applicants-remaining' => (is_null($max_total_applicants) ? null : ($max_total_applicants - $applicants_sold)),
				'max-total-assignments' => $max_total_assignments,
				'assignments-accepted' => $assignments_accepted,
				'assignments-sold' => $assignments_sold,
				'assignments-remaining' => (is_null($max_total_assignments) ? null : ($max_total_assignments - $assignments_sold)),
				'start-date' => $start_date,
				'end-date' => $end_date,
				'min-age' => $min_age,
				'max-age' => $max_age,
				'min-birthdate' => $min_birthdate,
				'max-birthdate' => $max_birthdate,
				'search-content' => array($name, $description, $rewards)
			);
		}
		$stmt->close();
		return $badge_types;
	}

	public function create_badge_type($badge_type) {
		if (!$badge_type) return false;
		$this->cm_db->connection->autocommit(false);
		$stmt = $this->cm_db->connection->prepare(
			'SELECT IFNULL(MAX(`order`),0)+1 FROM '.
			$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc)
		);
		$stmt->execute();
		$stmt->bind_result($order);
		$stmt->fetch();
		$stmt->close();
		$name = ($badge_type['name'] ?? '');
		$description = ($badge_type['description'] ?? '');
		$rewards = (isset($badge_type['rewards']) ? implode("\n", $badge_type['rewards']) : '');
		$max_applicant_count = ($badge_type['max-applicant-count'] ?? null);
		$max_assignment_count = ($badge_type['max-assignment-count'] ?? null);
		$base_price = (isset($badge_type['base-price']) ? (float)$badge_type['base-price'] : 0);
		$base_applicant_count = ($badge_type['base-applicant-count'] ?? 0);
		$base_assignment_count = ($badge_type['base-assignment-count'] ?? 0);
		$price_per_applicant = (isset($badge_type['price-per-applicant']) ? (float)$badge_type['price-per-applicant'] : 0);
		$price_per_assignment = (isset($badge_type['price-per-assignment']) ? (float)$badge_type['price-per-assignment'] : 0);
		$max_prereg_discount = ($badge_type['max-prereg-discount'] ?? 'No Discount');
		$use_permit = (isset($badge_type['use-permit']) ? ($badge_type['use-permit'] ? 1 : 0) : 0);
		$require_permit = (isset($badge_type['require-permit']) ? ($badge_type['require-permit'] ? 1 : 0) : 0);
		$require_contract = (isset($badge_type['require-contract']) ? ($badge_type['require-contract'] ? 1 : 0) : 0);
		$active = (isset($badge_type['active']) ? ($badge_type['active'] ? 1 : 0) : 1);
		$max_total_applications = ($badge_type['max-total-applications'] ?? null);
		$max_total_applicants = ($badge_type['max-total-applicants'] ?? null);
		$max_total_assignments = ($badge_type['max-total-assignments'] ?? null);
		$start_date = ($badge_type['start-date'] ?? null);
		$end_date = ($badge_type['end-date'] ?? null);
		$min_age = ($badge_type['min-age'] ?? null);
		$max_age = ($badge_type['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).' SET '.
			'`order` = ?, `name` = ?, `description` = ?, `rewards` = ?, '.
			'`max_applicant_count` = ?, `max_assignment_count` = ?, `base_price` = ?, '.
			'`base_applicant_count` = ?, `base_assignment_count` = ?, '.
			'`price_per_applicant` = ?, `price_per_assignment` = ?, '.
			'`max_prereg_discount` = ?, `use_permit` = ?, `require_permit` = ?, '.
			'`require_contract` = ?, `active` = ?, `max_total_applications` = ?, '.
			'`max_total_applicants` = ?, `max_total_assignments` = ?, '.
			'`start_date` = ?, `end_date` = ?, `min_age` = ?, `max_age` = ?'
		);
		$stmt->bind_param(
			'isssiidiiddsiiiiiiissii',
			$order, $name, $description, $rewards,
			$max_applicant_count, $max_assignment_count, $base_price,
			$base_applicant_count, $base_assignment_count,
			$price_per_applicant, $price_per_assignment,
			$max_prereg_discount, $use_permit, $require_permit,
			$require_contract, $active, $max_total_applications,
			$max_total_applicants, $max_total_assignments,
			$start_date, $end_date, $min_age, $max_age
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		$this->cm_db->connection->autocommit(true);
		return $id;
	}

	public function update_badge_type($badge_type) {
		if (!$badge_type || !isset($badge_type['id']) || !$badge_type['id']) return false;
		$name = ($badge_type['name'] ?? '');
		$description = ($badge_type['description'] ?? '');
		$rewards = (isset($badge_type['rewards']) ? implode("\n", $badge_type['rewards']) : '');
		$max_applicant_count = ($badge_type['max-applicant-count'] ?? null);
		$max_assignment_count = ($badge_type['max-assignment-count'] ?? null);
		$base_price = (isset($badge_type['base-price']) ? (float)$badge_type['base-price'] : 0);
		$base_applicant_count = ($badge_type['base-applicant-count'] ?? 0);
		$base_assignment_count = ($badge_type['base-assignment-count'] ?? 0);
		$price_per_applicant = (isset($badge_type['price-per-applicant']) ? (float)$badge_type['price-per-applicant'] : 0);
		$price_per_assignment = (isset($badge_type['price-per-assignment']) ? (float)$badge_type['price-per-assignment'] : 0);
		$max_prereg_discount = ($badge_type['max-prereg-discount'] ?? 'No Discount');
		$use_permit = (isset($badge_type['use-permit']) ? ($badge_type['use-permit'] ? 1 : 0) : 0);
		$require_permit = (isset($badge_type['require-permit']) ? ($badge_type['require-permit'] ? 1 : 0) : 0);
		$require_contract = (isset($badge_type['require-contract']) ? ($badge_type['require-contract'] ? 1 : 0) : 0);
		$active = (isset($badge_type['active']) ? ($badge_type['active'] ? 1 : 0) : 1);
		$max_total_applications = ($badge_type['max-total-applications'] ?? null);
		$max_total_applicants = ($badge_type['max-total-applicants'] ?? null);
		$max_total_assignments = ($badge_type['max-total-assignments'] ?? null);
		$start_date = ($badge_type['start-date'] ?? null);
		$end_date = ($badge_type['end-date'] ?? null);
		$min_age = ($badge_type['min-age'] ?? null);
		$max_age = ($badge_type['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).' SET '.
			'`name` = ?, `description` = ?, `rewards` = ?, '.
			'`max_applicant_count` = ?, `max_assignment_count` = ?, `base_price` = ?, '.
			'`base_applicant_count` = ?, `base_assignment_count` = ?, '.
			'`price_per_applicant` = ?, `price_per_assignment` = ?, '.
			'`max_prereg_discount` = ?, `use_permit` = ?, `require_permit` = ?, '.
			'`require_contract` = ?, `active` = ?, `max_total_applications` = ?, '.
			'`max_total_applicants` = ?, `max_total_assignments` = ?, '.
			'`start_date` = ?, `end_date` = ?, `min_age` = ?, `max_age` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'sssiidiiddsiiiiiiissiii',
			$name, $description, $rewards,
			$max_applicant_count, $max_assignment_count, $base_price,
			$base_applicant_count, $base_assignment_count,
			$price_per_applicant, $price_per_assignment,
			$max_prereg_discount, $use_permit, $require_permit,
			$require_contract, $active, $max_total_applications,
			$max_total_applicants, $max_total_assignments,
			$start_date, $end_date, $min_age, $max_age,
			$badge_type['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_badge_type($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function activate_badge_type($id, $active) {
		if (!$id) return false;
		$active = $active ? 1 : 0;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).
			' SET `active` = ? WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('ii', $active, $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function reorder_badge_type($id, $direction) {
		if (!$id || !$direction) return false;
		$this->cm_db->connection->autocommit(false);
		$ids = array();
		$index = -1;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id` FROM '.
			$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).
			' ORDER BY `order`'
		);
		$stmt->execute();
		$stmt->bind_result($cid);
		while ($stmt->fetch()) {
			$cindex = count($ids);
			$ids[] = $cid;
			if ($id == $cid) $index = $cindex;
		}
		$stmt->close();
		if ($index >= 0) {
			while ($direction < 0 && $index > 0) {
				$ids[$index] = $ids[$index - 1];
				$ids[$index - 1] = $id;
				$direction++;
				$index--;
			}
			while ($direction > 0 && $index < (count($ids) - 1)) {
				$ids[$index] = $ids[$index + 1];
				$ids[$index + 1] = $id;
				$direction--;
				$index++;
			}
			foreach ($ids as $cindex => $cid) {
				$stmt = $this->cm_db->connection->prepare(
					'UPDATE '.$this->cm_db->table_name('application_badge_types_'.$this->ctx_lc).
					' SET `order` = ? WHERE `id` = ? LIMIT 1'
				);
				$ni = $cindex + 1;
				$stmt->bind_param('ii', $ni, $cid);
				$stmt->execute();
				$stmt->close();
			}
		}
		$this->cm_db->connection->autocommit(true);
		return ($index >= 0);
	}

	public function get_application_blacklist_entry($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `business_name`, `application_name`, `added_by`, `notes`,'.
			' `normalized_business_name`, `normalized_application_name`'.
			' FROM '.$this->cm_db->table_name('application_blacklist_'.$this->ctx_lc).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $business_name, $application_name, $added_by, $notes,
			$normalized_business_name, $normalized_application_name
		);
		if ($stmt->fetch()) {
			$result = array(
				'id' => $id,
				'business-name' => $business_name,
				'application-name' => $application_name,
				'added-by' => $added_by,
				'notes' => $notes,
				'normalized-business-name' => $normalized_business_name,
				'normalized-application-name' => $normalized_application_name,
				'search-content' => array($business_name, $application_name, $added_by, $notes)
			);
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_application_blacklist_entries() {
		$blacklist = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `business_name`, `application_name`, `added_by`, `notes`,'.
			' `normalized_business_name`, `normalized_application_name`'.
			' FROM '.$this->cm_db->table_name('application_blacklist_'.$this->ctx_lc).
			' ORDER BY `business_name`, `application_name`'
		);
		$stmt->execute();
		$stmt->bind_result(
			$id, $business_name, $application_name, $added_by, $notes,
			$normalized_business_name, $normalized_application_name
		);
		while ($stmt->fetch()) {
			$blacklist[] = array(
				'id' => $id,
				'business-name' => $business_name,
				'application-name' => $application_name,
				'added-by' => $added_by,
				'notes' => $notes,
				'normalized-business-name' => $normalized_business_name,
				'normalized-application-name' => $normalized_application_name,
				'search-content' => array($business_name, $application_name, $added_by, $notes)
			);
		}
		$stmt->close();
		return $blacklist;
	}

	public function create_application_blacklist_entry($entry) {
		if (!$entry) return false;
		$business_name = ($entry['business-name'] ?? '');
		$application_name = ($entry['application-name'] ?? '');
		$added_by = ($entry['added-by'] ?? '');
		$notes = ($entry['notes'] ?? '');
		$normalized_business_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $business_name));
		$normalized_application_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $application_name));
		if (!$business_name) $business_name = null;
		if (!$application_name) $application_name = null;
		if (!$added_by) $added_by = null;
		if (!$notes) $notes = null;
		if (!$normalized_business_name) $normalized_business_name = null;
		if (!$normalized_application_name) $normalized_application_name = null;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('application_blacklist_'.$this->ctx_lc).' SET '.
			'`business_name` = ?, `application_name` = ?, `added_by` = ?, `notes` = ?, '.
			'`normalized_business_name` = ?, `normalized_application_name` = ?'
		);
		$stmt->bind_param(
			'ssssss',
			$business_name, $application_name, $added_by, $notes,
			$normalized_business_name, $normalized_application_name
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		return $id;
	}

	public function update_application_blacklist_entry($entry) {
		if (!$entry || !isset($entry['id']) || !$entry['id']) return false;
		$business_name = ($entry['business-name'] ?? '');
		$application_name = ($entry['application-name'] ?? '');
		$added_by = ($entry['added-by'] ?? '');
		$notes = ($entry['notes'] ?? '');
		$normalized_business_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $business_name));
		$normalized_application_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $application_name));
		if (!$business_name) $business_name = null;
		if (!$application_name) $application_name = null;
		if (!$added_by) $added_by = null;
		if (!$notes) $notes = null;
		if (!$normalized_business_name) $normalized_business_name = null;
		if (!$normalized_application_name) $normalized_application_name = null;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('application_blacklist_'.$this->ctx_lc).' SET '.
			'`business_name` = ?, `application_name` = ?, `added_by` = ?, `notes` = ?, '.
			'`normalized_business_name` = ?, `normalized_application_name` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'ssssssi',
			$business_name, $application_name, $added_by, $notes,
			$normalized_business_name, $normalized_application_name,
			$entry['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_application_blacklist_entry($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('application_blacklist_'.$this->ctx_lc).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function is_application_blacklisted($application) {
		if (!$application) return false;
		$business_name = ($application['business-name'] ?? '');
		$application_name = ($application['application-name'] ?? '');
		$normalized_business_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $business_name));
		$normalized_application_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $application_name));
		$query_params = array();
		$bind_params = array('');
		if ($normalized_business_name) {
			$query_params[] = '`normalized_business_name` = ?';
			$query_params[] = '`normalized_application_name` = ?';
			$bind_params[0] .= 'ss';
			$bind_params[] = &$normalized_business_name;
			$bind_params[] = &$normalized_business_name;
		}
		if ($normalized_application_name) {
			$query_params[] = '`normalized_business_name` = ?';
			$query_params[] = '`normalized_application_name` = ?';
			$bind_params[0] .= 'ss';
			$bind_params[] = &$normalized_application_name;
			$bind_params[] = &$normalized_application_name;
		}
		if (!$query_params) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id` FROM '.
			$this->cm_db->table_name('application_blacklist_'.$this->ctx_lc).
			' WHERE '.implode(' OR ', $query_params).' LIMIT 1'
		);
		call_user_func_array(array($stmt, 'bind_param'), $bind_params);
		$stmt->execute();
		$stmt->bind_result($id);
		$success = $stmt->fetch();
		$stmt->close();
		return $success ? $this->get_application_blacklist_entry($id) : false;
	}

	public function get_applicant_blacklist_entry($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `first_name`, `last_name`, `fandom_name`,'.
			' `email_address`, `phone_number`, `added_by`, `notes`,'.
			' `normalized_real_name`,'.
			' `normalized_reversed_name`,'.
			' `normalized_fandom_name`,'.
			' `normalized_email_address`,'.
			' `normalized_phone_number`'.
			' FROM '.$this->cm_db->table_name('applicant_blacklist_'.$this->ctx_lc).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $first_name, $last_name, $fandom_name,
			$email_address, $phone_number, $added_by, $notes,
			$normalized_real_name,
			$normalized_reversed_name,
			$normalized_fandom_name,
			$normalized_email_address,
			$normalized_phone_number
		);
		if ($stmt->fetch()) {
			$real_name = trim(trim($first_name) . ' ' . trim($last_name));
			$reversed_name = trim(trim($last_name) . ' ' . trim($first_name));
			$result = array(
				'id' => $id,
				'first-name' => $first_name,
				'last-name' => $last_name,
				'real-name' => $real_name,
				'reversed-name' => $reversed_name,
				'fandom-name' => $fandom_name,
				'email-address' => $email_address,
				'phone-number' => $phone_number,
				'added-by' => $added_by,
				'notes' => $notes,
				'normalized-real-name' => $normalized_real_name,
				'normalized-reversed-name' => $normalized_reversed_name,
				'normalized-fandom-name' => $normalized_fandom_name,
				'normalized-email-address' => $normalized_email_address,
				'normalized-phone-number' => $normalized_phone_number,
				'search-content' => array(
					$first_name, $last_name, $real_name, $reversed_name,
					$fandom_name, $email_address, $phone_number,
					$added_by, $notes
				)
			);
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_applicant_blacklist_entries() {
		$blacklist = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `first_name`, `last_name`, `fandom_name`,'.
			' `email_address`, `phone_number`, `added_by`, `notes`,'.
			' `normalized_real_name`,'.
			' `normalized_reversed_name`,'.
			' `normalized_fandom_name`,'.
			' `normalized_email_address`,'.
			' `normalized_phone_number`'.
			' FROM '.$this->cm_db->table_name('applicant_blacklist_'.$this->ctx_lc).
			' ORDER BY `first_name`, `last_name`'
		);
		$stmt->execute();
		$stmt->bind_result(
			$id, $first_name, $last_name, $fandom_name,
			$email_address, $phone_number, $added_by, $notes,
			$normalized_real_name,
			$normalized_reversed_name,
			$normalized_fandom_name,
			$normalized_email_address,
			$normalized_phone_number
		);
		while ($stmt->fetch()) {
			$real_name = trim(trim($first_name) . ' ' . trim($last_name));
			$reversed_name = trim(trim($last_name) . ' ' . trim($first_name));
			$blacklist[] = array(
				'id' => $id,
				'first-name' => $first_name,
				'last-name' => $last_name,
				'real-name' => $real_name,
				'reversed-name' => $reversed_name,
				'fandom-name' => $fandom_name,
				'email-address' => $email_address,
				'phone-number' => $phone_number,
				'added-by' => $added_by,
				'notes' => $notes,
				'normalized-real-name' => $normalized_real_name,
				'normalized-reversed-name' => $normalized_reversed_name,
				'normalized-fandom-name' => $normalized_fandom_name,
				'normalized-email-address' => $normalized_email_address,
				'normalized-phone-number' => $normalized_phone_number,
				'search-content' => array(
					$first_name, $last_name, $real_name, $reversed_name,
					$fandom_name, $email_address, $phone_number,
					$added_by, $notes
				)
			);
		}
		$stmt->close();
		return $blacklist;
	}

	public function create_applicant_blacklist_entry($entry) {
		if (!$entry) return false;
		$first_name = ($entry['first-name'] ?? '');
		$last_name = ($entry['last-name'] ?? '');
		$fandom_name = ($entry['fandom-name'] ?? '');
		$email_address = ($entry['email-address'] ?? '');
		$phone_number = ($entry['phone-number'] ?? '');
		$added_by = ($entry['added-by'] ?? '');
		$notes = ($entry['notes'] ?? '');
		$normalized_real_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $first_name . $last_name));
		$normalized_reversed_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $last_name . $first_name));
		$normalized_fandom_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $fandom_name));
		$normalized_email_address = strtoupper(preg_replace('/\\+.*@|[^A-Za-z0-9]+/', '', $email_address));
		$normalized_phone_number = preg_replace('/[^0-9]+/', '', $phone_number);
		if (!$first_name) $first_name = null;
		if (!$last_name) $last_name = null;
		if (!$fandom_name) $fandom_name = null;
		if (!$email_address) $email_address = null;
		if (!$phone_number) $phone_number = null;
		if (!$added_by) $added_by = null;
		if (!$notes) $notes = null;
		if (!$normalized_real_name) $normalized_real_name = null;
		if (!$normalized_reversed_name) $normalized_reversed_name = null;
		if (!$normalized_fandom_name) $normalized_fandom_name = null;
		if (!$normalized_email_address) $normalized_email_address = null;
		if (!$normalized_phone_number) $normalized_phone_number = null;
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('applicant_blacklist_'.$this->ctx_lc).' SET '.
			'`first_name` = ?, `last_name` = ?, `fandom_name` = ?, '.
			'`email_address` = ?, `phone_number` = ?, `added_by` = ?, `notes` = ?, '.
			'`normalized_real_name` = ?, '.
			'`normalized_reversed_name` = ?, '.
			'`normalized_fandom_name` = ?, '.
			'`normalized_email_address` = ?, '.
			'`normalized_phone_number` = ?'
		);
		$stmt->bind_param(
			'ssssssssssss',
			$first_name, $last_name, $fandom_name,
			$email_address, $phone_number, $added_by, $notes,
			$normalized_real_name,
			$normalized_reversed_name,
			$normalized_fandom_name,
			$normalized_email_address,
			$normalized_phone_number
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		return $id;
	}

	public function update_applicant_blacklist_entry($entry) {
		if (!$entry || !isset($entry['id']) || !$entry['id']) return false;
		$first_name = ($entry['first-name'] ?? '');
		$last_name = ($entry['last-name'] ?? '');
		$fandom_name = ($entry['fandom-name'] ?? '');
		$email_address = ($entry['email-address'] ?? '');
		$phone_number = ($entry['phone-number'] ?? '');
		$added_by = ($entry['added-by'] ?? '');
		$notes = ($entry['notes'] ?? '');
		$normalized_real_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $first_name . $last_name));
		$normalized_reversed_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $last_name . $first_name));
		$normalized_fandom_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $fandom_name));
		$normalized_email_address = strtoupper(preg_replace('/\\+.*@|[^A-Za-z0-9]+/', '', $email_address));
		$normalized_phone_number = preg_replace('/[^0-9]+/', '', $phone_number);
		if (!$first_name) $first_name = null;
		if (!$last_name) $last_name = null;
		if (!$fandom_name) $fandom_name = null;
		if (!$email_address) $email_address = null;
		if (!$phone_number) $phone_number = null;
		if (!$added_by) $added_by = null;
		if (!$notes) $notes = null;
		if (!$normalized_real_name) $normalized_real_name = null;
		if (!$normalized_reversed_name) $normalized_reversed_name = null;
		if (!$normalized_fandom_name) $normalized_fandom_name = null;
		if (!$normalized_email_address) $normalized_email_address = null;
		if (!$normalized_phone_number) $normalized_phone_number = null;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applicant_blacklist_'.$this->ctx_lc).' SET '.
			'`first_name` = ?, `last_name` = ?, `fandom_name` = ?, '.
			'`email_address` = ?, `phone_number` = ?, `added_by` = ?, `notes` = ?, '.
			'`normalized_real_name` = ?, '.
			'`normalized_reversed_name` = ?, '.
			'`normalized_fandom_name` = ?, '.
			'`normalized_email_address` = ?, '.
			'`normalized_phone_number` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'ssssssssssssi',
			$first_name, $last_name, $fandom_name,
			$email_address, $phone_number, $added_by, $notes,
			$normalized_real_name,
			$normalized_reversed_name,
			$normalized_fandom_name,
			$normalized_email_address,
			$normalized_phone_number,
			$entry['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_applicant_blacklist_entry($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('applicant_blacklist_'.$this->ctx_lc).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function is_applicant_blacklisted($applicant) {
		if (!$applicant) return false;
		$first_name = ($applicant['first-name'] ?? '');
		$last_name = ($applicant['last-name'] ?? '');
		$fandom_name = ($applicant['fandom-name'] ?? '');
		$email_address = ($applicant['email-address'] ?? '');
		$phone_number = ($applicant['phone-number'] ?? '');
		$normalized_real_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $first_name . $last_name));
		$normalized_reversed_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $last_name . $first_name));
		$normalized_fandom_name = strtoupper(preg_replace('/[^A-Za-z0-9]+/', '', $fandom_name));
		$normalized_email_address = strtoupper(preg_replace('/\\+.*@|[^A-Za-z0-9]+/', '', $email_address));
		$normalized_phone_number = preg_replace('/[^0-9]+/', '', $phone_number);
		$query_params = array();
		$bind_params = array('');
		if ($normalized_real_name) {
			$query_params[] = '`normalized_real_name` = ?';
			$query_params[] = '`normalized_reversed_name` = ?';
			$query_params[] = '`normalized_fandom_name` = ?';
			$bind_params[0] .= 'sss';
			$bind_params[] = &$normalized_real_name;
			$bind_params[] = &$normalized_real_name;
			$bind_params[] = &$normalized_real_name;
		}
		if ($normalized_reversed_name) {
			$query_params[] = '`normalized_real_name` = ?';
			$query_params[] = '`normalized_reversed_name` = ?';
			$query_params[] = '`normalized_fandom_name` = ?';
			$bind_params[0] .= 'sss';
			$bind_params[] = &$normalized_reversed_name;
			$bind_params[] = &$normalized_reversed_name;
			$bind_params[] = &$normalized_reversed_name;
		}
		if ($normalized_fandom_name) {
			$query_params[] = '`normalized_real_name` = ?';
			$query_params[] = '`normalized_reversed_name` = ?';
			$query_params[] = '`normalized_fandom_name` = ?';
			$bind_params[0] .= 'sss';
			$bind_params[] = &$normalized_fandom_name;
			$bind_params[] = &$normalized_fandom_name;
			$bind_params[] = &$normalized_fandom_name;
		}
		if ($normalized_email_address) {
			$query_params[] = '`normalized_email_address` = ?';
			$bind_params[0] .= 's';
			$bind_params[] = &$normalized_email_address;
		}
		if ($normalized_phone_number) {
			$query_params[] = '`normalized_phone_number` = ?';
			$bind_params[0] .= 's';
			$bind_params[] = &$normalized_phone_number;
		}
		if (!$query_params) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id` FROM '.
			$this->cm_db->table_name('applicant_blacklist_'.$this->ctx_lc).
			' WHERE '.implode(' OR ', $query_params).' LIMIT 1'
		);
		call_user_func_array(array($stmt, 'bind_param'), $bind_params);
		$stmt->execute();
		$stmt->bind_result($id);
		$success = $stmt->fetch();
		$stmt->close();
		return $success ? $this->get_applicant_blacklist_entry($id) : false;
	}

	public function get_application($id, $uuid = null, $expand = false, $name_map = null, $fdb = null) {
		if (!$id && !$uuid) return false;
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'application-'.$this->ctx_lc);
		$query = (
			'SELECT a.`id`, a.`uuid`, a.`date_created`, a.`date_modified`,'.
			' a.`badge_type_id`, a.`notes`, a.`contact_first_name`,'.
			' a.`contact_last_name`, a.`contact_subscribed`,'.
			' a.`contact_email_address`, a.`contact_phone_number`,'.
			' a.`contact_address_1`, a.`contact_address_2`, a.`contact_city`,'.
			' a.`contact_state`, a.`contact_zip_code`, a.`contact_country`,'.
			' a.`business_name`, a.`application_name`, a.`applicant_count`,'.
			' a.`assignment_count`, a.`application_status`, a.`permit_number`,'.
			' (SELECT MIN(b1.`date_of_birth`) FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' b1 WHERE b1.`application_id` = a.`id`) c1,'.
			' (SELECT MAX(b2.`date_of_birth`) FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' b2 WHERE b2.`application_id` = a.`id`) c2,'.
			' a.`payment_status`, a.`payment_badge_price`,'.
			' a.`payment_group_uuid`, a.`payment_type`,'.
			' a.`payment_txn_id`, a.`payment_txn_amt`,'.
			' a.`payment_date`, a.`payment_details`'.
			' FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a'
		);
		if ($id) {
			if ($uuid) $query .= ' WHERE `id` = ? AND `uuid` = ? LIMIT 1';
			else $query .= ' WHERE `id` = ? LIMIT 1';
		} else {
			$query .= ' WHERE `uuid` = ? LIMIT 1';
		}
		$stmt = $this->cm_db->connection->prepare($query);
		if ($id) {
			if ($uuid) $stmt->bind_param('is', $id, $uuid);
			else $stmt->bind_param('i', $id);
		} else {
			$stmt->bind_param('s', $uuid);
		}
		$stmt->execute();
		$stmt->bind_result(
			$id, $uuid, $date_created, $date_modified,
			$badge_type_id, $notes, $contact_first_name,
			$contact_last_name, $contact_subscribed,
			$contact_email_address, $contact_phone_number,
			$contact_address_1, $contact_address_2, $contact_city,
			$contact_state, $contact_zip_code, $contact_country,
			$business_name, $application_name, $applicant_count,
			$assignment_count, $application_status, $permit_number,
			$min_birthdate, $max_birthdate,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		if ($stmt->fetch()) {
			$reg_url = get_site_url(true) . '/apply';
			$id_string = $this->ctx_uc . 'A' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=' . $qr_data;
			$badge_type_id_string = $this->ctx_uc . 'B' . $badge_type_id;
			$badge_type_name = ($name_map[$badge_type_id] ?? $badge_type_id);
			$contact_real_name = trim(trim($contact_first_name) . ' ' . trim($contact_last_name));
			$contact_email_address_subscribed = ($contact_subscribed ? $contact_email_address : null);
			$contact_unsubscribe_link = $reg_url . '/unsubscribe.php?c=' . $this->ctx_lc . '&email=' . $contact_email_address;
			$contact_address = trim(trim($contact_address_1) . "\n" . trim($contact_address_2));
			$contact_csz = trim(trim(trim($contact_city) . ' ' . trim($contact_state)) . ' ' . trim($contact_zip_code));
			$contact_address_full = trim(trim(trim($contact_address) . "\n" . trim($contact_csz)) . "\n" . trim($contact_country));
			$min_age = $max_birthdate ? calculate_age($this->event_info['start_date'], $max_birthdate) : null;
			$max_age = $min_birthdate ? calculate_age($this->event_info['start_date'], $min_birthdate) : null;
			$review_link = (($payment_group_uuid && $payment_txn_id) ? (
				$reg_url . '/review.php?c=' . $this->ctx_lc .
				'&gid=' . $payment_group_uuid .
				'&tid=' . $payment_txn_id
			) : null);
			$search_content = array(
				$id, $uuid, $notes, $contact_first_name, $contact_last_name,
				$contact_email_address, $contact_phone_number,
				$contact_address_1, $contact_address_2, $contact_city,
				$contact_state, $contact_zip_code, $contact_country,
				$business_name, $application_name,
				$application_status, $permit_number,
				$payment_status, $payment_group_uuid, $payment_txn_id,
				$id_string, $qr_data, $badge_type_name,
				$contact_real_name, $contact_address,
				$contact_csz, $contact_address_full
			);
			$result = array(
				'type' => 'application',
				'app-ctx' => $this->ctx_uc,
				'id' => $id,
				'id-string' => $id_string,
				'uuid' => $uuid,
				'qr-data' => $qr_data,
				'qr-url' => $qr_url,
				'date-created' => $date_created,
				'date-modified' => $date_modified,
				'badge-type-id' => $badge_type_id,
				'badge-type-id-string' => $badge_type_id_string,
				'badge-type-name' => $badge_type_name,
				'notes' => $notes,
				'contact-first-name' => $contact_first_name,
				'contact-last-name' => $contact_last_name,
				'contact-real-name' => $contact_real_name,
				'contact-subscribed' => !!$contact_subscribed,
				'contact-email-address' => $contact_email_address,
				'contact-email-address-subscribed' => $contact_email_address_subscribed,
				'contact-unsubscribe-link' => $contact_unsubscribe_link,
				'contact-phone-number' => $contact_phone_number,
				'contact-address-1' => $contact_address_1,
				'contact-address-2' => $contact_address_2,
				'contact-address' => $contact_address,
				'contact-city' => $contact_city,
				'contact-state' => $contact_state,
				'contact-zip-code' => $contact_zip_code,
				'contact-csz' => $contact_csz,
				'contact-country' => $contact_country,
				'contact-address-full' => $contact_address_full,
				'business-name' => $business_name,
				'application-name' => $application_name,
				'applicant-count' => $applicant_count,
				'assignment-count' => $assignment_count,
				'application-status' => $application_status,
				'permit-number' => $permit_number,
				'min-birthdate' => $min_birthdate,
				'max-birthdate' => $max_birthdate,
				'min-age' => $min_age,
				'max-age' => $max_age,
				'payment-status' => $payment_status,
				'payment-badge-price' => $payment_badge_price,
				'payment-group-uuid' => $payment_group_uuid,
				'payment-type' => $payment_type,
				'payment-txn-id' => $payment_txn_id,
				'payment-txn-amt' => $payment_txn_amt,
				'payment-date' => $payment_date,
				'payment-details' => $payment_details,
				'review-link' => $review_link,
				'search-content' => $search_content
			);
			$stmt->close();

			if ($expand) {
				$applicants = $this->list_applicants($id, false, $name_map, $fdb);
				$result['applicants'] = $applicants;
			}

			$stmt = $this->cm_db->connection->prepare(
				'SELECT `context`, `context_id`, `room_or_table_id`, `start_time`, `end_time`'.
				' FROM '.$this->cm_db->table_name('room_and_table_assignments').
				' WHERE `context` = ? AND `context_id` = ?'.
				' ORDER BY `start_time`, `end_time`, `room_or_table_id`'
			);
			$stmt->bind_param('si', $this->ctx_uc, $id);
			$stmt->execute();
			$stmt->bind_result(
				$context, $context_id,
				$room_or_table_id, $start_time, $end_time
			);
			$assigned_rooms_and_tables = array();
			while ($stmt->fetch()) {
				$assigned_rooms_and_tables[] = array(
					'context' => $context,
					'context-id' => $context_id,
					'room-or-table-id' => $room_or_table_id,
					'start-time' => $start_time,
					'end-time' => $end_time
				);
				$result['search-content'][] = $room_or_table_id;
			}
			if ($assigned_rooms_and_tables) {
				$result['assigned-room-or-table-id'] = $assigned_rooms_and_tables[0]['room-or-table-id'];
				$result['assigned-start-time'] = $assigned_rooms_and_tables[0]['start-time'];
				$result['assigned-end-time'] = $assigned_rooms_and_tables[0]['end-time'];
				$result['assigned-room-and-table-ids'] = array_column_simple($assigned_rooms_and_tables, 'room-or-table-id');
				$result['assigned-start-times'] = array_column_simple($assigned_rooms_and_tables, 'start-time');
				$result['assigned-end-times'] = array_column_simple($assigned_rooms_and_tables, 'end-time');
				$result['assigned-rooms-and-tables'] = $assigned_rooms_and_tables;
			}
			$stmt->close();

			$answers = $fdb->list_answers($id);
			if ($answers) {
				$result['form-answers'] = $answers;
				foreach ($answers as $qid => $answer) {
					$answer_string = implode("\n", $answer);
					$result['form-answer-array-' . $qid] = $answer;
					$result['form-answer-string-' . $qid] = $answer_string;
					$result['search-content'][] = $answer_string;
				}
			}
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_applications($gid = null, $tid = null, $expand = false, $name_map = null, $fdb = null) {
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'application-'.$this->ctx_lc);
		$applications = array();
		$query = (
			'SELECT a.`id`, a.`uuid`, a.`date_created`, a.`date_modified`,'.
			' a.`badge_type_id`, a.`notes`, a.`contact_first_name`,'.
			' a.`contact_last_name`, a.`contact_subscribed`,'.
			' a.`contact_email_address`, a.`contact_phone_number`,'.
			' a.`contact_address_1`, a.`contact_address_2`, a.`contact_city`,'.
			' a.`contact_state`, a.`contact_zip_code`, a.`contact_country`,'.
			' a.`business_name`, a.`application_name`, a.`applicant_count`,'.
			' a.`assignment_count`, a.`application_status`, a.`permit_number`,'.
			' (SELECT MIN(b1.`date_of_birth`) FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' b1 WHERE b1.`application_id` = a.`id`) c1,'.
			' (SELECT MAX(b2.`date_of_birth`) FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' b2 WHERE b2.`application_id` = a.`id`) c2,'.
			' a.`payment_status`, a.`payment_badge_price`,'.
			' a.`payment_group_uuid`, a.`payment_type`,'.
			' a.`payment_txn_id`, a.`payment_txn_amt`,'.
			' a.`payment_date`, a.`payment_details`'.
			' FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' a'
		);
		$first = true;
		$bind = array('');
		if ($gid) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `payment_group_uuid` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$gid;
		}
		if ($tid) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `payment_txn_id` = ?';
			$first = false;
			$bind[0] .= 's';
			$bind[] = &$tid;
		}
		$stmt = $this->cm_db->connection->prepare($query . ' ORDER BY `id`');
		if (!$first) call_user_func_array(array($stmt, 'bind_param'), $bind);
		$stmt->execute();
		$stmt->bind_result(
			$id, $uuid, $date_created, $date_modified,
			$badge_type_id, $notes, $contact_first_name,
			$contact_last_name, $contact_subscribed,
			$contact_email_address, $contact_phone_number,
			$contact_address_1, $contact_address_2, $contact_city,
			$contact_state, $contact_zip_code, $contact_country,
			$business_name, $application_name, $applicant_count,
			$assignment_count, $application_status, $permit_number,
			$min_birthdate, $max_birthdate,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$reg_url = get_site_url(true) . '/apply';
		$qr_base_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=';
		while ($stmt->fetch()) {
			$id_string = $this->ctx_uc . 'A' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = $qr_base_url . $qr_data;
			$badge_type_id_string = $this->ctx_uc . 'B' . $badge_type_id;
			$badge_type_name = ($name_map[$badge_type_id] ?? $badge_type_id);
			$contact_real_name = trim(trim($contact_first_name) . ' ' . trim($contact_last_name));
			$contact_email_address_subscribed = ($contact_subscribed ? $contact_email_address : null);
			$contact_unsubscribe_link = $reg_url . '/unsubscribe.php?email=' . $contact_email_address;
			$contact_address = trim(trim($contact_address_1) . "\n" . trim($contact_address_2));
			$contact_csz = trim(trim(trim($contact_city) . ' ' . trim($contact_state)) . ' ' . trim($contact_zip_code));
			$contact_address_full = trim(trim(trim($contact_address) . "\n" . trim($contact_csz)) . "\n" . trim($contact_country));
			$min_age = $max_birthdate ? calculate_age($this->event_info['start_date'], $max_birthdate) : null;
			$max_age = $min_birthdate ? calculate_age($this->event_info['start_date'], $min_birthdate) : null;
			$review_link = (($payment_group_uuid && $payment_txn_id) ? (
				$reg_url . '/review.php?c=' . $this->ctx_lc .
				'&gid=' . $payment_group_uuid .
				'&tid=' . $payment_txn_id
			) : null);
			$search_content = array(
				$id, $uuid, $notes, $contact_first_name, $contact_last_name,
				$contact_email_address, $contact_phone_number,
				$contact_address_1, $contact_address_2, $contact_city,
				$contact_state, $contact_zip_code, $contact_country,
				$business_name, $application_name,
				$application_status, $permit_number,
				$payment_status, $payment_group_uuid, $payment_txn_id,
				$id_string, $qr_data, $badge_type_name,
				$contact_real_name, $contact_address,
				$contact_csz, $contact_address_full
			);
			$applications[] = array(
				'type' => 'application',
				'app-ctx' => $this->ctx_uc,
				'id' => $id,
				'id-string' => $id_string,
				'uuid' => $uuid,
				'qr-data' => $qr_data,
				'qr-url' => $qr_url,
				'date-created' => $date_created,
				'date-modified' => $date_modified,
				'badge-type-id' => $badge_type_id,
				'badge-type-id-string' => $badge_type_id_string,
				'badge-type-name' => $badge_type_name,
				'notes' => $notes,
				'contact-first-name' => $contact_first_name,
				'contact-last-name' => $contact_last_name,
				'contact-real-name' => $contact_real_name,
				'contact-subscribed' => !!$contact_subscribed,
				'contact-email-address' => $contact_email_address,
				'contact-email-address-subscribed' => $contact_email_address_subscribed,
				'contact-unsubscribe-link' => $contact_unsubscribe_link,
				'contact-phone-number' => $contact_phone_number,
				'contact-address-1' => $contact_address_1,
				'contact-address-2' => $contact_address_2,
				'contact-address' => $contact_address,
				'contact-city' => $contact_city,
				'contact-state' => $contact_state,
				'contact-zip-code' => $contact_zip_code,
				'contact-csz' => $contact_csz,
				'contact-country' => $contact_country,
				'contact-address-full' => $contact_address_full,
				'business-name' => $business_name,
				'application-name' => $application_name,
				'applicant-count' => $applicant_count,
				'assignment-count' => $assignment_count,
				'application-status' => $application_status,
				'permit-number' => $permit_number,
				'min-birthdate' => $min_birthdate,
				'max-birthdate' => $max_birthdate,
				'min-age' => $min_age,
				'max-age' => $max_age,
				'payment-status' => $payment_status,
				'payment-badge-price' => $payment_badge_price,
				'payment-group-uuid' => $payment_group_uuid,
				'payment-type' => $payment_type,
				'payment-txn-id' => $payment_txn_id,
				'payment-txn-amt' => $payment_txn_amt,
				'payment-date' => $payment_date,
				'payment-details' => $payment_details,
				'review-link' => $review_link,
				'search-content' => $search_content
			);
		}
		$stmt->close();
		foreach ($applications as $i => $application) {
			if ($expand) {
				$applicants = $this->list_applicants($application['id'], false, $name_map, $fdb);
				$applications[$i]['applicants'] = $applicants;
			}

			$stmt = $this->cm_db->connection->prepare(
				'SELECT `context`, `context_id`, `room_or_table_id`, `start_time`, `end_time`'.
				' FROM '.$this->cm_db->table_name('room_and_table_assignments').
				' WHERE `context` = ? AND `context_id` = ?'.
				' ORDER BY `start_time`, `end_time`, `room_or_table_id`'
			);
			$stmt->bind_param('si', $this->ctx_uc, $application['id']);
			$stmt->execute();
			$stmt->bind_result(
				$context, $context_id,
				$room_or_table_id, $start_time, $end_time
			);
			$assigned_rooms_and_tables = array();
			while ($stmt->fetch()) {
				$assigned_rooms_and_tables[] = array(
					'context' => $context,
					'context-id' => $context_id,
					'room-or-table-id' => $room_or_table_id,
					'start-time' => $start_time,
					'end-time' => $end_time
				);
				$applications[$i]['search-content'][] = $room_or_table_id;
			}
			if ($assigned_rooms_and_tables) {
				$applications[$i]['assigned-room-or-table-id'] = $assigned_rooms_and_tables[0]['room-or-table-id'];
				$applications[$i]['assigned-start-time'] = $assigned_rooms_and_tables[0]['start-time'];
				$applications[$i]['assigned-end-time'] = $assigned_rooms_and_tables[0]['end-time'];
				$applications[$i]['assigned-room-and-table-ids'] = array_column_simple($assigned_rooms_and_tables, 'room-or-table-id');
				$applications[$i]['assigned-start-times'] = array_column_simple($assigned_rooms_and_tables, 'start-time');
				$applications[$i]['assigned-end-times'] = array_column_simple($assigned_rooms_and_tables, 'end-time');
				$applications[$i]['assigned-rooms-and-tables'] = $assigned_rooms_and_tables;
			}
			$stmt->close();

			$answers = $fdb->list_answers($application['id']);
			if ($answers) {
				$applications[$i]['form-answers'] = $answers;
				foreach ($answers as $qid => $answer) {
					$answer_string = implode("\n", $answer);
					$applications[$i]['form-answer-array-' . $qid] = $answer;
					$applications[$i]['form-answer-string-' . $qid] = $answer_string;
					$applications[$i]['search-content'][] = $answer_string;
				}
			}
		}
		return $applications;
	}

	public function create_application($application, $fdb = null) {
		if (!$application) return false;
		$badge_type_id = ($application['badge-type-id'] ?? null);
		$notes = ($application['notes'] ?? null);
		$contact_first_name = ($application['contact-first-name'] ?? '');
		$contact_last_name = ($application['contact-last-name'] ?? '');
		$contact_subscribed = (isset($application['contact-subscribed']) ? ($application['contact-subscribed'] ? 1 : 0) : 0);
		$contact_email_address = ($application['contact-email-address'] ?? '');
		$contact_phone_number = ($application['contact-phone-number'] ?? '');
		$contact_address_1 = ($application['contact-address-1'] ?? '');
		$contact_address_2 = ($application['contact-address-2'] ?? '');
		$contact_city = ($application['contact-city'] ?? '');
		$contact_state = ($application['contact-state'] ?? '');
		$contact_zip_code = ($application['contact-zip-code'] ?? '');
		$contact_country = ($application['contact-country'] ?? '');
		$business_name = ($application['business-name'] ?? '');
		$application_name = ($application['application-name'] ?? '');
		$applicant_count = ($application['applicant-count'] ?? null);
		$assignment_count = ($application['assignment-count'] ?? null);
		$application_status = ($application['application-status'] ?? null);
		$permit_number = ($application['permit-number'] ?? null);
		$payment_status = ($application['payment-status'] ?? null);
		$payment_badge_price = ($application['payment-badge-price'] ?? null);
		$payment_group_uuid = ($application['payment-group-uuid'] ?? null);
		$payment_type = ($application['payment-type'] ?? null);
		$payment_txn_id = ($application['payment-txn-id'] ?? null);
		$payment_txn_amt = ($application['payment-txn-amt'] ?? null);
		$payment_date = ($application['payment-date'] ?? null);
		$payment_details = ($application['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' SET '.
			'`uuid` = UUID(), `date_created` = NOW(), `date_modified` = NOW(), '.
			'`badge_type_id` = ?, `notes` = ?, `contact_first_name` = ?, '.
			'`contact_last_name` = ?, `contact_subscribed` = ?, '.
			'`contact_email_address` = ?, `contact_phone_number` = ?, '.
			'`contact_address_1` = ?, `contact_address_2` = ?, `contact_city` = ?, '.
			'`contact_state` = ?, `contact_zip_code` = ?, `contact_country` = ?, '.
			'`business_name` = ?, `application_name` = ?, `applicant_count` = ?, '.
			'`assignment_count` = ?, `application_status` = ?, `permit_number` = ?, '.
			'`payment_status` = ?, `payment_badge_price` = ?, '.
			'`payment_group_uuid` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'
		);
		$stmt->bind_param(
			'isssissssssssssiisssdsssdss',
			$badge_type_id, $notes, $contact_first_name,
			$contact_last_name, $contact_subscribed,
			$contact_email_address, $contact_phone_number,
			$contact_address_1, $contact_address_2, $contact_city,
			$contact_state, $contact_zip_code, $contact_country,
			$business_name, $application_name, $applicant_count,
			$assignment_count, $application_status, $permit_number,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		if ($id !== false) {
			if (isset($application['assigned-rooms-and-tables'])) {
				foreach ($application['assigned-rooms-and-tables'] as $art) {
					$room_or_table_id = (isset($art['room-or-table-id']) && $art['room-or-table-id']) ? $art['room-or-table-id'] : null;
					$start_time = (isset($art['start-time']) && $art['start-time']) ? $art['start-time'] : null;
					$end_time = (isset($art['end-time']) && $art['end-time']) ? $art['end-time'] : null;
					if ($room_or_table_id) {
						$stmt = $this->cm_db->connection->prepare(
							'INSERT INTO '.
							$this->cm_db->table_name('room_and_table_assignments').
							' SET `context` = ?, `context_id` = ?, '.
							'`room_or_table_id` = ?, `start_time` = ?, `end_time` = ?'
						);
						$stmt->bind_param(
							'sisss',
							$this->ctx_uc, $id,
							$room_or_table_id, $start_time, $end_time
						);
						$stmt->execute();
						$stmt->close();
					}
				}
			}
			if ($fdb && isset($application['form-answers'])) {
				$fdb->set_answers($id, $application['form-answers']);
			}
			$application = $this->get_application($id, null, true);
			$this->cm_anldb->add_entity($application);
		}
		return $id;
	}

	public function update_application($application, $fdb = null) {
		if (!$application || !isset($application['id']) || !$application['id']) return false;
		$badge_type_id = ($application['badge-type-id'] ?? null);
		$notes = ($application['notes'] ?? null);
		$contact_first_name = ($application['contact-first-name'] ?? '');
		$contact_last_name = ($application['contact-last-name'] ?? '');
		$contact_subscribed = (isset($application['contact-subscribed']) ? ($application['contact-subscribed'] ? 1 : 0) : 0);
		$contact_email_address = ($application['contact-email-address'] ?? '');
		$contact_phone_number = ($application['contact-phone-number'] ?? '');
		$contact_address_1 = ($application['contact-address-1'] ?? '');
		$contact_address_2 = ($application['contact-address-2'] ?? '');
		$contact_city = ($application['contact-city'] ?? '');
		$contact_state = ($application['contact-state'] ?? '');
		$contact_zip_code = ($application['contact-zip-code'] ?? '');
		$contact_country = ($application['contact-country'] ?? '');
		$business_name = ($application['business-name'] ?? '');
		$application_name = ($application['application-name'] ?? '');
		$applicant_count = ($application['applicant-count'] ?? null);
		$assignment_count = ($application['assignment-count'] ?? null);
		$application_status = ($application['application-status'] ?? null);
		$permit_number = ($application['permit-number'] ?? null);
		$payment_status = ($application['payment-status'] ?? null);
		$payment_badge_price = ($application['payment-badge-price'] ?? null);
		$payment_group_uuid = ($application['payment-group-uuid'] ?? null);
		$payment_type = ($application['payment-type'] ?? null);
		$payment_txn_id = ($application['payment-txn-id'] ?? null);
		$payment_txn_amt = ($application['payment-txn-amt'] ?? null);
		$payment_date = ($application['payment-date'] ?? null);
		$payment_details = ($application['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' SET '.
			'`date_modified` = NOW(), '.
			'`badge_type_id` = ?, `notes` = ?, `contact_first_name` = ?, '.
			'`contact_last_name` = ?, `contact_subscribed` = ?, '.
			'`contact_email_address` = ?, `contact_phone_number` = ?, '.
			'`contact_address_1` = ?, `contact_address_2` = ?, `contact_city` = ?, '.
			'`contact_state` = ?, `contact_zip_code` = ?, `contact_country` = ?, '.
			'`business_name` = ?, `application_name` = ?, `applicant_count` = ?, '.
			'`assignment_count` = ?, `application_status` = ?, `permit_number` = ?, '.
			'`payment_status` = ?, `payment_badge_price` = ?, '.
			'`payment_group_uuid` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'isssissssssssssiisssdsssdssi',
			$badge_type_id, $notes, $contact_first_name,
			$contact_last_name, $contact_subscribed,
			$contact_email_address, $contact_phone_number,
			$contact_address_1, $contact_address_2, $contact_city,
			$contact_state, $contact_zip_code, $contact_country,
			$business_name, $application_name, $applicant_count,
			$assignment_count, $application_status, $permit_number,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details,
			$application['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			if (isset($application['assigned-rooms-and-tables'])) {
				$stmt = $this->cm_db->connection->prepare(
					'DELETE FROM '.$this->cm_db->table_name('room_and_table_assignments').
					' WHERE `context` = ? AND `context_id` = ?'
				);
				$stmt->bind_param('si', $this->ctx_uc, $application['id']);
				$stmt->execute();
				$stmt->close();
				foreach ($application['assigned-rooms-and-tables'] as $art) {
					$room_or_table_id = (isset($art['room-or-table-id']) && $art['room-or-table-id']) ? $art['room-or-table-id'] : null;
					$start_time = (isset($art['start-time']) && $art['start-time']) ? $art['start-time'] : null;
					$end_time = (isset($art['end-time']) && $art['end-time']) ? $art['end-time'] : null;
					if ($room_or_table_id) {
						$stmt = $this->cm_db->connection->prepare(
							'INSERT INTO '.
							$this->cm_db->table_name('room_and_table_assignments').
							' SET `context` = ?, `context_id` = ?, '.
							'`room_or_table_id` = ?, `start_time` = ?, `end_time` = ?'
						);
						$stmt->bind_param(
							'sisss',
							$this->ctx_uc, $application['id'],
							$room_or_table_id, $start_time, $end_time
						);
						$stmt->execute();
						$stmt->close();
					}
				}
			}
			if ($fdb && isset($application['form-answers'])) {
				$fdb->clear_answers($application['id']);
				$fdb->set_answers($application['id'], $application['form-answers']);
			}
			$application = $this->get_application($application['id'], null, true);
			$this->cm_anldb->remove_entity($application['id']);
			$this->cm_anldb->add_entity($application);
		}
		return $success;
	}

	public function delete_application($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$applicant_ids = array();
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `id` FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).
				' WHERE `application_id` = ?'
			);
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->bind_result($applicant_id);
			while ($stmt->fetch()) {
				$applicant_ids[] = $applicant_id;
			}
			$stmt->close();
			foreach ($applicant_ids as $applicant_id) {
				$this->delete_applicant($applicant_id);
			}

			$stmt = $this->cm_db->connection->prepare(
				'DELETE FROM '.$this->cm_db->table_name('room_and_table_assignments').
				' WHERE `context` = ? AND `context_id` = ?'
			);
			$stmt->bind_param('si', $this->ctx_uc, $id);
			$stmt->execute();
			$stmt->close();
			$this->cm_anldb->remove_entity($id);
		}
		return $success;
	}

	public function already_exists($application) {
		if (!$application) return false;
		$application_name = (isset($application['application-name']) ? strtolower($application['application-name']) : '');
		$stmt = $this->cm_db->connection->prepare(
			'SELECT 1 FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).
			' WHERE LCASE(`application_name`) = ?'
		);
		$stmt->bind_param('s', $application_name);
		$stmt->execute();
		$stmt->bind_result($x);
		$exists = $stmt->fetch() && $x;
		$stmt->close();
		return $exists;
	}

	public function generate_invoice($application, $atdb = null) {
		$ctx_info = $this->ctx_info;
		if (!$ctx_info) return false;
		$badge = $this->get_badge_type($application['badge-type-id']);
		if (!$badge) return false;

		$applications = array();
		$assignments = array();
		$applicants = array();
		$discounts = array();

		$applications[] = array(
			'application-id' => $application['id'],
			'name' => $ctx_info['nav_prefix'] . ' Application Fee',
			'details' => $badge['name'],
			'price' => $badge['base-price'],
			'price-string' => price_string($badge['base-price'])
		);

		$free_assignments = $badge['base-assignment-count'];
		if (isset($application['assigned-rooms-and-tables']) && $application['assigned-rooms-and-tables']) {
			$count = count($application['assigned-rooms-and-tables']);
			foreach ($application['assigned-rooms-and-tables'] as $index => $art) {
				$assignments[] = array(
					'application-id' => $application['id'],
					'name' => $ctx_info['nav_prefix'] . ' ' . $ctx_info['assignment_term'][0] . ' Fee',
					'details' => $art['room-or-table-id'] . ' (' . ($index + 1) . ' of ' . $count . ')',
					'price' => ($index < $free_assignments) ? 0 : $badge['price-per-assignment'],
					'price-string' => ($index < $free_assignments) ? 'INCLUDED' : price_string($badge['price-per-assignment'])
				);
			}
		} else {
			$count = $application['assignment-count'];
			if ((float)$badge['price-per-assignment']) {
				for ($index = 0; $index < $count; $index++) {
					$assignments[] = array(
						'application-id' => $application['id'],
						'name' => $ctx_info['nav_prefix'] . ' ' . $ctx_info['assignment_term'][0] . ' Fee',
						'details' => '(' . ($index + 1) . ' of ' . $count . ')',
						'price' => ($index < $free_assignments) ? 0 : $badge['price-per-assignment'],
						'price-string' => ($index < $free_assignments) ? 'INCLUDED' : price_string($badge['price-per-assignment'])
					);
				}
			}
		}

		$free_applicants = $badge['base-applicant-count'] * $count;
		if (isset($application['applicants']) && $application['applicants']) {
			$count = count($application['applicants']);
			foreach ($application['applicants'] as $index => $applicant) {
				$applicants[] = array(
					'application-id' => $application['id'],
					'name' => $ctx_info['nav_prefix'] . ' Badge Fee',
					'details' => $applicant['display-name'] . ' (' . ($index + 1) . ' of ' . $count . ')',
					'price' => ($index < $free_applicants) ? 0 : $badge['price-per-applicant'],
					'price-string' => ($index < $free_applicants) ? 'INCLUDED' : price_string($badge['price-per-applicant'])
				);
			}
		} else {
			$count = $application['applicant-count'];
			if ((float)$badge['price-per-applicant']) {
				for ($index = 0; $index < $count; $index++) {
					$applicants[] = array(
						'application-id' => $application['id'],
						'name' => $ctx_info['nav_prefix'] . ' Badge Fee',
						'details' => '(' . ($index + 1) . ' of ' . $count . ')',
						'price' => ($index < $free_applicants) ? 0 : $badge['price-per-applicant'],
						'price-string' => ($index < $free_applicants) ? 'INCLUDED' : price_string($badge['price-per-applicant'])
					);
				}
			}
		}

		if (isset($application['applicants']) && $application['applicants'] && $atdb) {
			$name_map = $atdb->get_badge_type_name_map();
			$fdb = new cm_forms_db($this->cm_db, 'attendee');

			$total_price = 0;
			foreach ($applications as $a) $total_price += $a['price'];
			foreach ($assignments as $a) $total_price += $a['price'];
			foreach ($applicants as $a) $total_price += $a['price'];

			$max_discount = 0;
			switch ($badge['max-prereg-discount']) {
				case 'Price per Applicant' : $max_discount = $badge['price-per-applicant' ]; break;
				case 'Price per Assignment': $max_discount = $badge['price-per-assignment']; break;
				case 'Total Price'         : $max_discount = $total_price                  ; break;
			}

			foreach ($application['applicants'] as $applicant) {
				if (isset($applicant['attendee-id']) && $applicant['attendee-id']) {
					$attendee = $atdb->get_attendee($applicant['attendee-id'], false, $name_map, $fdb);
					if ($attendee && $attendee['payment-status'] == 'Completed') {
						$discount = min($attendee['payment-txn-amt'], $max_discount, $total_price);
						if ($discount > 0) {
							$discounts[] = array(
								'application-id' => $application['id'],
								'name' => 'Attendee Preregistration Discount',
								'details' => $attendee['display-name'],
								'price' => -$discount,
								'price-string' => '-' . price_string($discount)
							);
							$total_price -= $discount;
						}
					}
				}
			}
		}

		return array_merge($applications, $assignments, $applicants, $discounts);
	}

	public function update_permit_number($id, $permit_number) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' SET '.
			'`permit_number` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('si', $permit_number, $id);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$application = $this->get_application($id, null, true);
			$this->cm_anldb->remove_entity($id);
			$this->cm_anldb->add_entity($application);
		}
		return $success;
	}

	public function update_payment_status($id, $status, $type, $txn_id, $txn_amt, $date, $details) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' SET '.
			'`payment_status` = ?, `payment_type` = ?, `payment_txn_id` = ?, '.
			'`payment_txn_amt` = ?, `payment_date` = ?, `payment_details` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'ssssssi',
			$status, $type, $txn_id,
			$txn_amt, $date, $details, $id
		);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$application = $this->get_application($id, null, true);
			$this->cm_anldb->remove_entity($id);
			$this->cm_anldb->add_entity($application);
		}
		return $success;
	}

	public function get_applicant($id, $uuid = null, $expand = false, $name_map = null, $fdb = null) {
		if (!$id && !$uuid) return false;
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'application-'.$this->ctx_lc);
		$query = (
			'SELECT `id`, `uuid`, `date_created`, `date_modified`,'.
			' `print_count`, `print_first_time`, `print_last_time`,'.
			' `checkin_count`, `checkin_first_time`, `checkin_last_time`,'.
			' `application_id`, `attendee_id`, `notes`, `first_name`,'.
			' `last_name`, `fandom_name`, `name_on_badge`, `date_of_birth`,'.
			' `subscribed`, `email_address`, `phone_number`,'.
			' `address_1`, `address_2`, `city`, `state`, `zip_code`,'.
			' `country`, `ice_name`, `ice_relationship`,'.
			' `ice_email_address`, `ice_phone_number`'.
			' FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc)
		);
		if ($id) {
			if ($uuid) $query .= ' WHERE `id` = ? AND `uuid` = ? LIMIT 1';
			else $query .= ' WHERE `id` = ? LIMIT 1';
		} else {
			$query .= ' WHERE `uuid` = ? LIMIT 1';
		}
		$stmt = $this->cm_db->connection->prepare($query);
		if ($id) {
			if ($uuid) $stmt->bind_param('is', $id, $uuid);
			else $stmt->bind_param('i', $id);
		} else {
			$stmt->bind_param('s', $uuid);
		}
		$stmt->execute();
		$stmt->bind_result(
			$id, $uuid, $date_created, $date_modified,
			$print_count, $print_first_time, $print_last_time,
			$checkin_count, $checkin_first_time, $checkin_last_time,
			$application_id, $attendee_id, $notes, $first_name,
			$last_name, $fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state, $zip_code,
			$country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number
		);
		if ($stmt->fetch()) {
			$reg_url = get_site_url(true) . '/apply';
			$id_string = $this->ctx_uc . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=' . $qr_data;
			$application_id_string = $application_id ? ($this->ctx_uc . 'A' . $application_id) : null;
			$attendee_id_string = $attendee_id ? ('A' . $attendee_id) : null;
			$real_name = trim(trim($first_name) . ' ' . trim($last_name));
			$only_name = $real_name;
			$large_name = '';
			$small_name = '';
			$display_name = $real_name;
			if ($fandom_name) {
				switch ($name_on_badge) {
					case 'Fandom Name Large, Real Name Small':
						$only_name = '';
						$large_name = $fandom_name;
						$small_name = $real_name;
						$display_name = trim($fandom_name) . ' (' . trim($real_name) . ')';
						break;
					case 'Real Name Large, Fandom Name Small':
						$only_name = '';
						$large_name = $real_name;
						$small_name = $fandom_name;
						$display_name = trim($real_name) . ' (' . trim($fandom_name) . ')';
						break;
					case 'Fandom Name Only':
						$only_name = $fandom_name;
						$display_name = $fandom_name;
						break;
				}
			}
			$age = calculate_age($this->event_info['start_date'], $date_of_birth);
			$email_address_subscribed = ($subscribed ? $email_address : null);
			$unsubscribe_link = $reg_url . '/unsubscribe.php?c=' . $this->ctx_lc . '&email=' . $email_address;
			$address = trim(trim($address_1) . "\n" . trim($address_2));
			$csz = trim(trim(trim($city) . ' ' . trim($state)) . ' ' . trim($zip_code));
			$address_full = trim(trim(trim($address) . "\n" . trim($csz)) . "\n" . trim($country));
			$search_content = array(
				$id, $uuid, $notes, $first_name, $last_name, $fandom_name,
				$date_of_birth, $email_address, $phone_number,
				$address_1, $address_2, $city, $state, $zip_code, $country,
				$id_string, $qr_data, $real_name, $only_name, $large_name,
				$small_name, $display_name, $address, $csz, $address_full
			);
			$result = array(
				'type' => 'applicant',
				'app-ctx' => $this->ctx_uc,
				'id' => $id,
				'id-string' => $id_string,
				'uuid' => $uuid,
				'qr-data' => $qr_data,
				'qr-url' => $qr_url,
				'date-created' => $date_created,
				'date-modified' => $date_modified,
				'print-count' => $print_count,
				'print-first-time' => $print_first_time,
				'print-last-time' => $print_last_time,
				'checkin-count' => $checkin_count,
				'checkin-first-time' => $checkin_first_time,
				'checkin-last-time' => $checkin_last_time,
				'application-id' => $application_id,
				'application-id-string' => $application_id_string,
				'attendee-id' => $attendee_id,
				'attendee-id-string' => $attendee_id_string,
				'notes' => $notes,
				'first-name' => $first_name,
				'last-name' => $last_name,
				'real-name' => $real_name,
				'fandom-name' => $fandom_name,
				'name-on-badge' => $name_on_badge,
				'only-name' => $only_name,
				'large-name' => $large_name,
				'small-name' => $small_name,
				'display-name' => $display_name,
				'date-of-birth' => $date_of_birth,
				'age' => $age,
				'subscribed' => !!$subscribed,
				'email-address' => $email_address,
				'email-address-subscribed' => $email_address_subscribed,
				'unsubscribe-link' => $unsubscribe_link,
				'phone-number' => $phone_number,
				'address-1' => $address_1,
				'address-2' => $address_2,
				'address' => $address,
				'city' => $city,
				'state' => $state,
				'zip-code' => $zip_code,
				'csz' => $csz,
				'country' => $country,
				'address-full' => $address_full,
				'ice-name' => $ice_name,
				'ice-relationship' => $ice_relationship,
				'ice-email-address' => $ice_email_address,
				'ice-phone-number' => $ice_phone_number,
				'search-content' => $search_content
			);
			$stmt->close();
			if ($expand && $application_id) {
				$application = $this->get_application($application_id, null, false, $name_map, $fdb);
				if ($application) {
					$result['search-content'] = array_merge($result['search-content'], $application['search-content']);
					$result['application'] = $application;
					$result += $application;
				}
			}
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_applicants($application_id = null, $expand = false, $name_map = null, $fdb = null) {
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'application-'.$this->ctx_lc);
		$applicants = array();
		$query = (
			'SELECT `id`, `uuid`, `date_created`, `date_modified`,'.
			' `print_count`, `print_first_time`, `print_last_time`,'.
			' `checkin_count`, `checkin_first_time`, `checkin_last_time`,'.
			' `application_id`, `attendee_id`, `notes`, `first_name`,'.
			' `last_name`, `fandom_name`, `name_on_badge`, `date_of_birth`,'.
			' `subscribed`, `email_address`, `phone_number`,'.
			' `address_1`, `address_2`, `city`, `state`, `zip_code`,'.
			' `country`, `ice_name`, `ice_relationship`,'.
			' `ice_email_address`, `ice_phone_number`'.
			' FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc)
		);
		$first = true;
		$bind = array('');
		if ($application_id) {
			$query .= ($first ? ' WHERE' : ' AND') . ' `application_id` = ?';
			$first = false;
			$bind[0] .= 'i';
			$bind[] = &$application_id;
		}
		$stmt = $this->cm_db->connection->prepare($query . ' ORDER BY `id`');
		if (!$first) call_user_func_array(array($stmt, 'bind_param'), $bind);
		$stmt->execute();
		$stmt->bind_result(
			$id, $uuid, $date_created, $date_modified,
			$print_count, $print_first_time, $print_last_time,
			$checkin_count, $checkin_first_time, $checkin_last_time,
			$application_id, $attendee_id, $notes, $first_name,
			$last_name, $fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state, $zip_code,
			$country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number
		);
		$reg_url = get_site_url(true) . '/apply';
		$qr_base_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=';
		while ($stmt->fetch()) {
			$id_string = $this->ctx_uc . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = $qr_base_url . $qr_data;
			$application_id_string = $application_id ? ($this->ctx_uc . 'A' . $application_id) : null;
			$attendee_id_string = $attendee_id ? ('A' . $attendee_id) : null;
			$real_name = trim(trim($first_name) . ' ' . trim($last_name));
			$only_name = $real_name;
			$large_name = '';
			$small_name = '';
			$display_name = $real_name;
			if ($fandom_name) {
				switch ($name_on_badge) {
					case 'Fandom Name Large, Real Name Small':
						$only_name = '';
						$large_name = $fandom_name;
						$small_name = $real_name;
						$display_name = trim($fandom_name) . ' (' . trim($real_name) . ')';
						break;
					case 'Real Name Large, Fandom Name Small':
						$only_name = '';
						$large_name = $real_name;
						$small_name = $fandom_name;
						$display_name = trim($real_name) . ' (' . trim($fandom_name) . ')';
						break;
					case 'Fandom Name Only':
						$only_name = $fandom_name;
						$display_name = $fandom_name;
						break;
				}
			}
			$age = calculate_age($this->event_info['start_date'], $date_of_birth);
			$email_address_subscribed = ($subscribed ? $email_address : null);
			$unsubscribe_link = $reg_url . '/unsubscribe.php?c=' . $this->ctx_lc . '&email=' . $email_address;
			$address = trim(trim($address_1) . "\n" . trim($address_2));
			$csz = trim(trim(trim($city) . ' ' . trim($state)) . ' ' . trim($zip_code));
			$address_full = trim(trim(trim($address) . "\n" . trim($csz)) . "\n" . trim($country));
			$search_content = array(
				$id, $uuid, $notes, $first_name, $last_name, $fandom_name,
				$date_of_birth, $email_address, $phone_number,
				$address_1, $address_2, $city, $state, $zip_code, $country,
				$id_string, $qr_data, $real_name, $only_name, $large_name,
				$small_name, $display_name, $address, $csz, $address_full
			);
			$applicants[] = array(
				'type' => 'applicant',
				'app-ctx' => $this->ctx_uc,
				'id' => $id,
				'id-string' => $id_string,
				'uuid' => $uuid,
				'qr-data' => $qr_data,
				'qr-url' => $qr_url,
				'date-created' => $date_created,
				'date-modified' => $date_modified,
				'print-count' => $print_count,
				'print-first-time' => $print_first_time,
				'print-last-time' => $print_last_time,
				'checkin-count' => $checkin_count,
				'checkin-first-time' => $checkin_first_time,
				'checkin-last-time' => $checkin_last_time,
				'application-id' => $application_id,
				'application-id-string' => $application_id_string,
				'attendee-id' => $attendee_id,
				'attendee-id-string' => $attendee_id_string,
				'notes' => $notes,
				'first-name' => $first_name,
				'last-name' => $last_name,
				'real-name' => $real_name,
				'fandom-name' => $fandom_name,
				'name-on-badge' => $name_on_badge,
				'only-name' => $only_name,
				'large-name' => $large_name,
				'small-name' => $small_name,
				'display-name' => $display_name,
				'date-of-birth' => $date_of_birth,
				'age' => $age,
				'subscribed' => !!$subscribed,
				'email-address' => $email_address,
				'email-address-subscribed' => $email_address_subscribed,
				'unsubscribe-link' => $unsubscribe_link,
				'phone-number' => $phone_number,
				'address-1' => $address_1,
				'address-2' => $address_2,
				'address' => $address,
				'city' => $city,
				'state' => $state,
				'zip-code' => $zip_code,
				'csz' => $csz,
				'country' => $country,
				'address-full' => $address_full,
				'ice-name' => $ice_name,
				'ice-relationship' => $ice_relationship,
				'ice-email-address' => $ice_email_address,
				'ice-phone-number' => $ice_phone_number,
				'search-content' => $search_content
			);
		}
		$stmt->close();
		if ($expand) {
			foreach ($applicants as $i => $applicant) {
				if ($applicant['application-id']) {
					$application = $this->get_application($applicant['application-id'], null, false, $name_map, $fdb);
					if ($application) {
						$applicant['search-content'] = array_merge($applicant['search-content'], $application['search-content']);
						$applicant['application'] = $application;
						$applicants[$i] = $applicant + $application;
					}
				}
			}
		}
		return $applicants;
	}

	public function create_applicant($applicant) {
		if (!$applicant) return false;
		$application_id = ($applicant['application-id'] ?? null);
		$attendee_id = ($applicant['attendee-id'] ?? null);
		$notes = ($applicant['notes'] ?? null);
		$first_name = ($applicant['first-name'] ?? '');
		$last_name = ($applicant['last-name'] ?? '');
		$fandom_name = ($applicant['fandom-name'] ?? '');
		$name_on_badge = (($fandom_name && isset($applicant['name-on-badge'])) ? $applicant['name-on-badge'] : 'Real Name Only');
		$date_of_birth = ($applicant['date-of-birth'] ?? null);
		$subscribed = (isset($applicant['subscribed']) ? ($applicant['subscribed'] ? 1 : 0) : 0);
		$email_address = ($applicant['email-address'] ?? '');
		$phone_number = ($applicant['phone-number'] ?? '');
		$address_1 = ($applicant['address-1'] ?? '');
		$address_2 = ($applicant['address-2'] ?? '');
		$city = ($applicant['city'] ?? '');
		$state = ($applicant['state'] ?? '');
		$zip_code = ($applicant['zip-code'] ?? '');
		$country = ($applicant['country'] ?? '');
		$ice_name = ($applicant['ice-name'] ?? '');
		$ice_relationship = ($applicant['ice-relationship'] ?? '');
		$ice_email_address = ($applicant['ice-email-address'] ?? '');
		$ice_phone_number = ($applicant['ice-phone-number'] ?? '');
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' SET '.
			'`uuid` = UUID(), `date_created` = NOW(), `date_modified` = NOW(), '.
			'`application_id` = ?, `attendee_id` = ?, `notes` = ?, '.
			'`first_name` = ?, `last_name` = ?, `fandom_name` = ?, '.
			'`name_on_badge` = ?, `date_of_birth` = ?, `subscribed` = ?, '.
			'`email_address` = ?, `phone_number` = ?, `address_1` = ?, '.
			'`address_2` = ?, `city` = ?, `state` = ?, `zip_code` = ?, '.
			'`country` = ?, `ice_name` = ?, `ice_relationship` = ?, '.
			'`ice_email_address` = ?, `ice_phone_number` = ?'
		);
		$stmt->bind_param(
			'iissssssissssssssssss',
			$application_id, $attendee_id, $notes,
			$first_name, $last_name, $fandom_name,
			$name_on_badge, $date_of_birth, $subscribed,
			$email_address, $phone_number, $address_1,
			$address_2, $city, $state, $zip_code,
			$country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		if ($id !== false) {
			$applicant = $this->get_applicant($id, null, true);
			$this->cm_atldb->add_entity($applicant);
		}
		return $id;
	}

	public function update_applicant($applicant) {
		if (!$applicant || !isset($applicant['id']) || !$applicant['id']) return false;
		$application_id = ($applicant['application-id'] ?? null);
		$attendee_id = ($applicant['attendee-id'] ?? null);
		$notes = ($applicant['notes'] ?? null);
		$first_name = ($applicant['first-name'] ?? '');
		$last_name = ($applicant['last-name'] ?? '');
		$fandom_name = ($applicant['fandom-name'] ?? '');
		$name_on_badge = (($fandom_name && isset($applicant['name-on-badge'])) ? $applicant['name-on-badge'] : 'Real Name Only');
		$date_of_birth = ($applicant['date-of-birth'] ?? null);
		$subscribed = (isset($applicant['subscribed']) ? ($applicant['subscribed'] ? 1 : 0) : 0);
		$email_address = ($applicant['email-address'] ?? '');
		$phone_number = ($applicant['phone-number'] ?? '');
		$address_1 = ($applicant['address-1'] ?? '');
		$address_2 = ($applicant['address-2'] ?? '');
		$city = ($applicant['city'] ?? '');
		$state = ($applicant['state'] ?? '');
		$zip_code = ($applicant['zip-code'] ?? '');
		$country = ($applicant['country'] ?? '');
		$ice_name = ($applicant['ice-name'] ?? '');
		$ice_relationship = ($applicant['ice-relationship'] ?? '');
		$ice_email_address = ($applicant['ice-email-address'] ?? '');
		$ice_phone_number = ($applicant['ice-phone-number'] ?? '');
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' SET '.
			'`date_modified` = NOW(), '.
			'`application_id` = ?, `attendee_id` = ?, `notes` = ?, '.
			'`first_name` = ?, `last_name` = ?, `fandom_name` = ?, '.
			'`name_on_badge` = ?, `date_of_birth` = ?, `subscribed` = ?, '.
			'`email_address` = ?, `phone_number` = ?, `address_1` = ?, '.
			'`address_2` = ?, `city` = ?, `state` = ?, `zip_code` = ?, '.
			'`country` = ?, `ice_name` = ?, `ice_relationship` = ?, '.
			'`ice_email_address` = ?, `ice_phone_number` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'iissssssissssssssssssi',
			$application_id, $attendee_id, $notes,
			$first_name, $last_name, $fandom_name,
			$name_on_badge, $date_of_birth, $subscribed,
			$email_address, $phone_number, $address_1,
			$address_2, $city, $state, $zip_code,
			$country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number,
			$applicant['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$applicant = $this->get_applicant($applicant['id'], null, true);
			$this->cm_atldb->remove_entity($applicant['id']);
			$this->cm_atldb->add_entity($applicant);
		}
		return $success;
	}

	public function delete_applicant($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$this->cm_atldb->remove_entity($id);
		}
		return $success;
	}

	public function unsubscribe_email_address($email) {
		if (!$email) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' SET '.
			'`contact_subscribed` = FALSE WHERE LCASE(`contact_email_address`) = LCASE(?)'
		);
		$stmt->bind_param('s', $email);
		$ancount = $stmt->execute() ? $this->cm_db->connection->affected_rows : 0;
		$stmt->close();
		if ($ancount) {
			$ids = array();
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `id` FROM '.$this->cm_db->table_name('applications_'.$this->ctx_lc).
				' WHERE LCASE(`contact_email_address`) = LCASE(?)'
			);
			$stmt->bind_param('s', $email);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) $ids[] = $id;
			$stmt->close();
			foreach ($ids as $id) {
				$application = $this->get_application($id, null, true);
				$this->cm_anldb->remove_entity($id);
				$this->cm_anldb->add_entity($application);
			}
		}
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' SET '.
			'`subscribed` = FALSE WHERE LCASE(`email_address`) = LCASE(?)'
		);
		$stmt->bind_param('s', $email);
		$atcount = $stmt->execute() ? $this->cm_db->connection->affected_rows : 0;
		$stmt->close();
		if ($atcount) {
			$ids = array();
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `id` FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).
				' WHERE LCASE(`email_address`) = LCASE(?)'
			);
			$stmt->bind_param('s', $email);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) $ids[] = $id;
			$stmt->close();
			foreach ($ids as $id) {
				$applicant = $this->get_applicant($id, null, true);
				$this->cm_atldb->remove_entity($id);
				$this->cm_atldb->add_entity($applicant);
			}
		}
		return $ancount + $atcount;
	}

	public function applicant_printed($id, $reset = false) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' SET '.
			($reset ? (
				'`print_count` = NULL, '.
				'`print_first_time` = NULL, '.
				'`print_last_time` = NULL'
			) : (
				'`print_count` = IFNULL(`print_count`, 0) + 1, '.
				'`print_first_time` = IFNULL(`print_first_time`, NOW()), '.
				'`print_last_time` = NOW()'
			)).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$applicant = $this->get_applicant($id, null, true);
			$this->cm_atldb->remove_entity($id);
			$this->cm_atldb->add_entity($applicant);
		}
		return $success;
	}

	public function applicant_checked_in($id, $reset = false) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' SET '.
			($reset ? (
				'`checkin_count` = NULL, '.
				'`checkin_first_time` = NULL, '.
				'`checkin_last_time` = NULL'
			) : (
				'`checkin_count` = IFNULL(`checkin_count`, 0) + 1, '.
				'`checkin_first_time` = IFNULL(`checkin_first_time`, NOW()), '.
				'`checkin_last_time` = NOW()'
			)).
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$applicant = $this->get_applicant($id, null, true);
			$this->cm_atldb->remove_entity($id);
			$this->cm_atldb->add_entity($applicant);
		}
		return $success;
	}

	public function get_applicant_statistics($granularity = 300, $name_map = null) {
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		$timestamps = array();
		$counters = array();
		$timelines = array();
		foreach ($name_map as $k => $v) {
			$counters[$k] = array(0, 0, 0, 0);
			$timelines[$k] = array(array(), array(), array(), array());
		}
		$counters['*'] = array(0, 0, 0, 0);
		$timelines['*'] = array(array(), array(), array(), array());

		$stmt = $this->cm_db->connection->prepare(
			'SELECT UNIX_TIMESTAMP(at.`date_created`), an.`badge_type_id`'.
			' FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' at'.
			' LEFT JOIN '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' an'.
			' ON at.`application_id` = an.`id`'.
			' ORDER BY at.`date_created`'
		);
		$stmt->execute();
		$stmt->bind_result($timestamp, $btid);
		while ($stmt->fetch()) {
			$timestamp -= $timestamp % $granularity;
			$timestamp *= 1000;
			$timestamps[$timestamp] = $timestamp;
			$timelines[$btid][0][$timestamp] = ++$counters[$btid][0];
			$timelines['*'][0][$timestamp] = ++$counters['*'][0];
		}
		$stmt->close();

		$stmt = $this->cm_db->connection->prepare(
			'SELECT UNIX_TIMESTAMP(an.`payment_date`), an.`badge_type_id`'.
			' FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' at'.
			' LEFT JOIN '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' an'.
			' ON at.`application_id` = an.`id`'.
			' WHERE an.`payment_status` = \'Completed\''.
			' AND an.`payment_date` IS NOT NULL'.
			' ORDER BY an.`payment_date`'
		);
		$stmt->execute();
		$stmt->bind_result($timestamp, $btid);
		while ($stmt->fetch()) {
			$timestamp -= $timestamp % $granularity;
			$timestamp *= 1000;
			$timestamps[$timestamp] = $timestamp;
			$timelines[$btid][1][$timestamp] = ++$counters[$btid][1];
			$timelines['*'][1][$timestamp] = ++$counters['*'][1];
		}
		$stmt->close();

		$stmt = $this->cm_db->connection->prepare(
			'SELECT UNIX_TIMESTAMP(at.`print_first_time`), an.`badge_type_id`'.
			' FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' at'.
			' LEFT JOIN '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' an'.
			' ON at.`application_id` = an.`id`'.
			' WHERE at.`print_first_time` IS NOT NULL'.
			' ORDER BY at.`print_first_time`'
		);
		$stmt->execute();
		$stmt->bind_result($timestamp, $btid);
		while ($stmt->fetch()) {
			$timestamp -= $timestamp % $granularity;
			$timestamp *= 1000;
			$timestamps[$timestamp] = $timestamp;
			$timelines[$btid][2][$timestamp] = ++$counters[$btid][2];
			$timelines['*'][2][$timestamp] = ++$counters['*'][2];
		}
		$stmt->close();

		$stmt = $this->cm_db->connection->prepare(
			'SELECT UNIX_TIMESTAMP(at.`checkin_first_time`), an.`badge_type_id`'.
			' FROM '.$this->cm_db->table_name('applicants_'.$this->ctx_lc).' at'.
			' LEFT JOIN '.$this->cm_db->table_name('applications_'.$this->ctx_lc).' an'.
			' ON at.`application_id` = an.`id`'.
			' WHERE at.`checkin_first_time` IS NOT NULL'.
			' ORDER BY at.`checkin_first_time`'
		);
		$stmt->execute();
		$stmt->bind_result($timestamp, $btid);
		while ($stmt->fetch()) {
			$timestamp -= $timestamp % $granularity;
			$timestamp *= 1000;
			$timestamps[$timestamp] = $timestamp;
			$timelines[$btid][3][$timestamp] = ++$counters[$btid][3];
			$timelines['*'][3][$timestamp] = ++$counters['*'][3];
		}
		$stmt->close();

		ksort($timestamps);
		return array(
			'timestamps' => $timestamps,
			'counters' => $counters,
			'timelines' => $timelines
		);
	}

}
