<?php

require_once dirname(__FILE__).'/../../config/config.php';
require_once dirname(__FILE__).'/../util/util.php';
require_once dirname(__FILE__).'/../util/res.php';
require_once dirname(__FILE__).'/database.php';
require_once dirname(__FILE__).'/lists.php';
require_once dirname(__FILE__).'/forms.php';

class cm_staff_db {

	public $mail_depths = array(
		'Executive',
		'Staff',
		'Recursive'
	);
	public $names_on_badge = array(
		'Fandom Name Large, Real Name Small',
		'Real Name Large, Fandom Name Small',
		'Fandom Name Only',
		'Real Name Only'
	);
	public $application_statuses = array(
		'Submitted',
		'Cancelled',
		'Accepted',
		'Waitlisted',
		'Rejected'
	);
	public $mailbox_types = array(
		'Mailbox, No Forwarding',
		'Mailbox, With Forwarding',
		'Forwarding Only'
	);
	public $payment_statuses = array(
		'Incomplete',
		'Cancelled',
		'Rejected',
		'Completed',
		'Refunded'
	);

	public $event_info;
	public $cm_db;
	public $cm_ldb;

	public function __construct($cm_db) {
		$this->event_info = $GLOBALS['cm_config']['event'];
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('staff_departments', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`parent_id` INTEGER NULL,'.
			'`name` VARCHAR(255) NOT NULL,'.
			'`description` TEXT NULL,'.
			'`mail_alias_1` VARCHAR(255) NULL,'.
			'`mail_alias_2` VARCHAR(255) NULL,'.
			'`mail_depth` ENUM('.
				'\'Executive\','.
				'\'Staff\','.
				'\'Recursive\''.
			') NULL,'.
			'`active` BOOLEAN NOT NULL'
		));
		$this->cm_db->table_def('staff_positions', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`parent_id` INTEGER NOT NULL,'.
			'`order` INTEGER NOT NULL,'.
			'`name` VARCHAR(255) NOT NULL,'.
			'`description` TEXT NULL,'.
			'`executive` BOOLEAN NOT NULL,'.
			'`active` BOOLEAN NOT NULL'
		));
		if (
			$this->cm_db->table_is_empty('staff_departments') &&
			$this->cm_db->table_is_empty('staff_positions')
		) {
			$this->create_department(array(
				'name' => 'Board',
				'description' => (
					'A default department automatically created '.
					'by CONcrescent during installation. Feel free '.
					'to modify or delete according to your needs.'
				),
				'mail-alias-1' => ('board@' . $_SERVER['SERVER_NAME']),
				'mail-depth' => 'Staff',
				'positions' => array(
					array('name' => 'President', 'executive' => true),
					array('name' => 'Vice President', 'executive' => true)
				)
			));
			$this->create_department(array(
				'name' => 'Chair',
				'description' => (
					'A default department automatically created '.
					'by CONcrescent during installation. Feel free '.
					'to modify or delete according to your needs.'
				),
				'mail-alias-1' => ('chair@' . $_SERVER['SERVER_NAME']),
				'mail-alias-2' => ('chairs@' . $_SERVER['SERVER_NAME']),
				'mail-depth' => 'Staff',
				'positions' => array(
					array('name' => 'Chair', 'executive' => true),
					array('name' => 'Vice Chair', 'executive' => true)
				)
			));
		}
		$this->cm_db->table_def('staff_badge_types', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`order` INTEGER NOT NULL,'.
			'`name` VARCHAR(255) NOT NULL,'.
			'`description` TEXT NULL,'.
			'`rewards` TEXT NULL,'.
			'`price` DECIMAL(7,2) NOT NULL,'.
			'`active` BOOLEAN NOT NULL,'.
			'`quantity` INTEGER NULL,'.
			'`start_date` DATE NULL,'.
			'`end_date` DATE NULL,'.
			'`min_age` INTEGER NULL,'.
			'`max_age` INTEGER NULL'
		));
		if ($this->cm_db->table_is_empty('staff_badge_types')) {
			$this->create_badge_type(array(
				'name' => 'Staff',
				'description' => (
					'A default badge type automatically created '.
					'by CONcrescent during installation. Feel free '.
					'to modify or delete according to your needs.'
				)
			));
		}
		$this->cm_db->table_def('staff_blacklist', (
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
		$this->cm_db->table_def('staff', (
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
			'`badge_type_id` INTEGER NOT NULL,'.
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
			'`ice_phone_number` VARCHAR(255) NULL,'.
			/* Application Info */
			'`application_status` ENUM('.
				'\'Submitted\','.
				'\'Cancelled\','.
				'\'Accepted\','.
				'\'Waitlisted\','.
				'\'Rejected\''.
			') NOT NULL,'.
			'`mail_alias_1` VARCHAR(255) NULL,'.
			'`mail_alias_2` VARCHAR(255) NULL,'.
			'`mailbox_type` ENUM('.
				'\'Mailbox, No Forwarding\','.
				'\'Mailbox, With Forwarding\','.
				'\'Forwarding Only\''.
			') NULL,'.
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
		$this->cm_db->table_def('staff_assigned_positions', (
			'`staff_id` INTEGER NOT NULL,'.
			'`order` INTEGER NOT NULL,'.
			'`department_id` INTEGER NULL,'.
			'`department_name` VARCHAR(255) NULL,'.
			'`position_id` INTEGER NULL,'.
			'`position_name` VARCHAR(255) NULL,'.
			'PRIMARY KEY (`staff_id`, `order`)'
		));
		$this->cm_ldb = new cm_lists_db($this->cm_db, 'staff_search_index');
	}

	public function get_department($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `parent_id`, `name`, `description`,'.
			' `mail_alias_1`, `mail_alias_2`, `mail_depth`, `active`'.
			' FROM '.$this->cm_db->table_name('staff_departments').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $parent_id, $name, $description,
			$mail_alias_1, $mail_alias_2, $mail_depth, $active
		);
		if ($stmt->fetch()) {
			$result = array(
				'id' => $id,
				'parent-id' => $parent_id,
				'name' => $name,
				'description' => $description,
				'mail-alias-1' => $mail_alias_1,
				'mail-alias-2' => $mail_alias_2,
				'mail-depth' => $mail_depth,
				'active' => !!$active,
				'hierarchy' => array($id => $name),
				'positions' => array(),
				'search-content' => array(
					$name, $description,
					$mail_alias_1, $mail_alias_2
				)
			);
			$stmt->close();

			while ($parent_id && !isset($result['hierarchy'][$parent_id])) {
				$stmt = $this->cm_db->connection->prepare(
					'SELECT `id`, `parent_id`, `name`'.
					' FROM '.$this->cm_db->table_name('staff_departments').
					' WHERE `id` = ? LIMIT 1'
				);
				$stmt->bind_param('i', $parent_id);
				$stmt->execute();
				$stmt->bind_result($id, $parent_id, $name);
				if ($stmt->fetch()) {
					$result['hierarchy'][$id] = $name;
					$result['search-content'][] = $name;
				} else {
					$parent_id = null;
				}
				$stmt->close();
			}
			$result['hierarchy'] = array_keys_values(
				array_reverse($result['hierarchy'], true),
				'id', 'name'
			);

			$stmt = $this->cm_db->connection->prepare(
				'SELECT `id`, `parent_id`, `order`, `name`,'.
				' `description`, `executive`, `active`'.
				' FROM '.$this->cm_db->table_name('staff_positions').
				' WHERE `parent_id` = ?'.
				' ORDER BY `order`'
			);
			$stmt->bind_param('i', $result['id']);
			$stmt->execute();
			$stmt->bind_result(
				$id, $parent_id, $order, $name,
				$description, $executive, $active
			);
			while ($stmt->fetch()) {
				$result['positions'][] = array(
					'id' => $id,
					'parent-id' => $parent_id,
					'order' => $order,
					'name' => $name,
					'description' => $description,
					'executive' => !!$executive,
					'active' => !!$active
				);
				$result['search-content'][] = $name;
				$result['search-content'][] = $description;
			}
			$stmt->close();

			return $result;
		}
		$stmt->close();
		return false;
	}

	public function get_department_map() {
		$departments = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `parent_id`, `name`'.
			' FROM '.$this->cm_db->table_name('staff_departments').
			' ORDER BY `name`'
		);
		$stmt->execute();
		$stmt->bind_result($id, $parent_id, $name);
		while ($stmt->fetch()) {
			$departments[$id] = array(
				'id' => $id,
				'parent-id' => $parent_id,
				'name' => $name
			);
		}
		$stmt->close();
		return $departments;
	}

	public function get_position_map() {
		$positions = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `parent_id`, `name`'.
			' FROM '.$this->cm_db->table_name('staff_positions').
			' ORDER BY `parent_id`, `order`'
		);
		$stmt->execute();
		$stmt->bind_result($id, $parent_id, $name);
		while ($stmt->fetch()) {
			$positions[$id] = array(
				'id' => $id,
				'parent-id' => $parent_id,
				'name' => $name
			);
		}
		$stmt->close();
		return $positions;
	}

	public function list_departments() {
		$departments = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `parent_id`, `name`, `description`,'.
			' `mail_alias_1`, `mail_alias_2`, `mail_depth`, `active`'.
			' FROM '.$this->cm_db->table_name('staff_departments').
			' ORDER BY `name`'
		);
		$stmt->execute();
		$stmt->bind_result(
			$id, $parent_id, $name, $description,
			$mail_alias_1, $mail_alias_2, $mail_depth, $active
		);
		while ($stmt->fetch()) {
			$departments[$id] = array(
				'id' => $id,
				'parent-id' => $parent_id,
				'name' => $name,
				'description' => $description,
				'mail-alias-1' => $mail_alias_1,
				'mail-alias-2' => $mail_alias_2,
				'mail-depth' => $mail_depth,
				'active' => !!$active,
				'hierarchy' => array($id => $name),
				'positions' => array(),
				'search-content' => array(
					$name, $description,
					$mail_alias_1, $mail_alias_2
				)
			);
		}
		$stmt->close();

		foreach (array_keys($departments) as $id) {
			$parent_id = $departments[$id]['parent-id'];
			while ($parent_id && !isset($departments[$id]['hierarchy'][$parent_id]) && isset($departments[$parent_id])) {
				$departments[$id]['hierarchy'][$parent_id] = $departments[$parent_id]['name'];
				$departments[$id]['search-content'][] = $departments[$parent_id]['name'];
				$parent_id = $departments[$parent_id]['parent-id'];
			}
			$departments[$id]['hierarchy'] = array_keys_values(
				array_reverse($departments[$id]['hierarchy'], true),
				'id', 'name'
			);
		}

		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `parent_id`, `order`, `name`,'.
			' `description`, `executive`, `active`'.
			' FROM '.$this->cm_db->table_name('staff_positions').
			' ORDER BY `order`'
		);
		$stmt->execute();
		$stmt->bind_result(
			$id, $parent_id, $order, $name,
			$description, $executive, $active
		);
		while ($stmt->fetch()) {
			$departments[$parent_id]['positions'][] = array(
				'id' => $id,
				'parent-id' => $parent_id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'executive' => !!$executive,
				'active' => !!$active
			);
			$departments[$parent_id]['search-content'][] = $name;
			$departments[$parent_id]['search-content'][] = $description;
		}
		$stmt->close();

		return array_values($departments);
	}

	public function create_department($department) {
		if (!$department) return false;
		$this->cm_db->connection->autocommit(false);

		$parent_id = ((isset($department['parent-id']) && (int)$department['parent-id']) ? (int)$department['parent-id'] : null);
		$name = ($department['name'] ?? '');
		$description = ($department['description'] ?? '');
		$mail_alias_1 = ((isset($department['mail-alias-1']) && $department['mail-alias-1']) ? $department['mail-alias-1'] : null);
		$mail_alias_2 = ((isset($department['mail-alias-2']) && $department['mail-alias-2']) ? $department['mail-alias-2'] : null);
		$mail_depth = ((isset($department['mail-depth']) && $department['mail-depth']) ? $department['mail-depth'] : null);
		$active = (isset($department['active']) ? ($department['active'] ? 1 : 0) : 1);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('staff_departments').' SET '.
			'`parent_id` = ?, `name` = ?, `description` = ?, '.
			'`mail_alias_1` = ?, `mail_alias_2` = ?, `mail_depth` = ?, `active` = ?'
		);
		$stmt->bind_param(
			'isssssi',
			$parent_id, $name, $description,
			$mail_alias_1, $mail_alias_2, $mail_depth, $active
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();

		if ($id !== false && isset($department['positions']) && $department['positions']) {
			$order = 0;
			foreach ($department['positions'] as $position) {
				$order++;
				$name = ($position['name'] ?? '');
				$description = ($position['description'] ?? '');
				$executive = (isset($position['executive']) ? ($position['executive'] ? 1 : 0) : 0);
				$active = (isset($position['active']) ? ($position['active'] ? 1 : 0) : 1);
				$stmt = $this->cm_db->connection->prepare(
					'INSERT INTO '.$this->cm_db->table_name('staff_positions').' SET '.
					'`parent_id` = ?, `order` = ?, `name` = ?, '.
					'`description` = ?, `executive` = ?, `active` = ?'
				);
				$stmt->bind_param(
					'iissii',
					$id, $order, $name,
					$description, $executive, $active
				);
				$stmt->execute();
				$stmt->close();
			}
		}

		$this->cm_db->connection->autocommit(true);
		return $id;
	}

	public function update_department($department) {
		if (!$department || !isset($department['id']) || !$department['id']) return false;
		$this->cm_db->connection->autocommit(false);

		$parent_id = ((isset($department['parent-id']) && (int)$department['parent-id']) ? (int)$department['parent-id'] : null);
		$name = ($department['name'] ?? '');
		$description = ($department['description'] ?? '');
		$mail_alias_1 = ((isset($department['mail-alias-1']) && $department['mail-alias-1']) ? $department['mail-alias-1'] : null);
		$mail_alias_2 = ((isset($department['mail-alias-2']) && $department['mail-alias-2']) ? $department['mail-alias-2'] : null);
		$mail_depth = ((isset($department['mail-depth']) && $department['mail-depth']) ? $department['mail-depth'] : null);
		$active = (isset($department['active']) ? ($department['active'] ? 1 : 0) : 1);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff_departments').' SET '.
			'`parent_id` = ?, `name` = ?, `description` = ?, '.
			'`mail_alias_1` = ?, `mail_alias_2` = ?, `mail_depth` = ?, `active` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'isssssii',
			$parent_id, $name, $description,
			$mail_alias_1, $mail_alias_2, $mail_depth, $active,
			$department['id']
		);
		$success = $stmt->execute();
		$stmt->close();

		if ($success) {
			$stmt = $this->cm_db->connection->prepare(
				'DELETE FROM '.$this->cm_db->table_name('staff_positions').
				' WHERE `parent_id` = ?'
			);
			$stmt->bind_param('i', $department['id']);
			$stmt->execute();
			$stmt->close();
			if (isset($department['positions']) && $department['positions']) {
				$order = 0;
				foreach ($department['positions'] as $position) {
					$order++;
					$name = ($position['name'] ?? '');
					$description = ($position['description'] ?? '');
					$executive = (isset($position['executive']) ? ($position['executive'] ? 1 : 0) : 0);
					$active = (isset($position['active']) ? ($position['active'] ? 1 : 0) : 1);
					$id = ((isset($position['id']) && (int)$position['id']) ? (int)$position['id'] : null);
					$stmt = $this->cm_db->connection->prepare(
						'INSERT INTO '.$this->cm_db->table_name('staff_positions').' SET '.
						'`id` = ?, `parent_id` = ?, `order` = ?, `name` = ?, '.
						'`description` = ?, `executive` = ?, `active` = ?'
					);
					$stmt->bind_param(
						'iiissii',
						$id, $department['id'], $order, $name,
						$description, $executive, $active
					);
					$stmt->execute();
					$stmt->close();
				}
			}
		}

		$this->cm_db->connection->autocommit(true);
		return $success;
	}

	public function delete_department($id) {
		if (!$id) return false;
		$this->cm_db->connection->autocommit(false);

		$stmt = $this->cm_db->connection->prepare(
			'SELECT `parent_id`'.
			' FROM '.$this->cm_db->table_name('staff_departments').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result($parent_id);
		if (!$stmt->fetch()) $parent_id = null;
		$stmt->close();

		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('staff_departments').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();

		if ($success) {
			$stmt = $this->cm_db->connection->prepare(
				'UPDATE '.$this->cm_db->table_name('staff_departments').
				' SET `parent_id` = ?'.
				' WHERE `parent_id` = ?'
			);
			$stmt->bind_param('ii', $parent_id, $id);
			$stmt->execute();
			$stmt->close();

			$stmt = $this->cm_db->connection->prepare(
				'DELETE FROM '.$this->cm_db->table_name('staff_positions').
				' WHERE `parent_id` = ?'
			);
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
		}

		$this->cm_db->connection->autocommit(true);
		return $success;
	}

	public function activate_department($id, $active) {
		if (!$id) return false;
		$active = $active ? 1 : 0;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff_departments').
			' SET `active` = ? WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('ii', $active, $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function get_badge_type($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT b.`id`, b.`order`, b.`name`, b.`description`, b.`rewards`,'.
			' b.`price`, b.`active`, b.`quantity`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('staff').' a1'.
			' WHERE a1.`badge_type_id` = b.`id` AND a1.`application_status` = \'Accepted\') c1,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('staff').' a2'.
			' WHERE a2.`badge_type_id` = b.`id` AND a2.`payment_status` = \'Completed\') c2'.
			' FROM '.$this->cm_db->table_name('staff_badge_types').' b'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $order, $name, $description, $rewards,
			$price, $active, $quantity,
			$start_date, $end_date, $min_age, $max_age,
			$quantity_accepted, $quantity_sold
		);
		if ($stmt->fetch()) {
			$event_start_date = $this->event_info['start_date'];
			$event_end_date   = $this->event_info['end_date'  ];
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$result = array(
				'id' => $id,
				'id-string' => 'SB' . $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'rewards' => ($rewards ? explode("\n", $rewards) : array()),
				'price' => $price,
				'active' => !!$active,
				'quantity' => $quantity,
				'quantity-accepted' => $quantity_accepted,
				'quantity-sold' => $quantity_sold,
				'quantity-remaining' => (is_null($quantity) ? null : ($quantity - $quantity_sold)),
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
			' FROM '.$this->cm_db->table_name('staff_badge_types').
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
			' FROM '.$this->cm_db->table_name('staff_badge_types').
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
			' b.`price`, b.`active`, b.`quantity`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('staff').' a1'.
			' WHERE a1.`badge_type_id` = b.`id` AND a1.`application_status` = \'Accepted\') c1,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('staff').' a2'.
			' WHERE a2.`badge_type_id` = b.`id` AND a2.`payment_status` = \'Completed\') c2'.
			' FROM '.$this->cm_db->table_name('staff_badge_types').' b'
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
			$price, $active, $quantity,
			$start_date, $end_date, $min_age, $max_age,
			$quantity_accepted, $quantity_sold
		);
		$event_start_date = $this->event_info['start_date'];
		$event_end_date   = $this->event_info['end_date'  ];
		while ($stmt->fetch()) {
			if ($unsold_only && !(is_null($quantity) || $quantity > $quantity_sold)) continue;
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$badge_types[] = array(
				'id' => $id,
				'id-string' => 'SB' . $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'rewards' => ($rewards ? explode("\n", $rewards) : array()),
				'price' => $price,
				'active' => !!$active,
				'quantity' => $quantity,
				'quantity-accepted' => $quantity_accepted,
				'quantity-sold' => $quantity_sold,
				'quantity-remaining' => (is_null($quantity) ? null : ($quantity - $quantity_sold)),
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
			$this->cm_db->table_name('staff_badge_types')
		);
		$stmt->execute();
		$stmt->bind_result($order);
		$stmt->fetch();
		$stmt->close();
		$name = ($badge_type['name'] ?? '');
		$description = ($badge_type['description'] ?? '');
		$rewards = (isset($badge_type['rewards']) ? implode("\n", $badge_type['rewards']) : '');
		$price = (isset($badge_type['price']) ? (float)$badge_type['price'] : 0);
		$active = (isset($badge_type['active']) ? ($badge_type['active'] ? 1 : 0) : 1);
		$quantity = ($badge_type['quantity'] ?? null);
		$start_date = ($badge_type['start-date'] ?? null);
		$end_date = ($badge_type['end-date'] ?? null);
		$min_age = ($badge_type['min-age'] ?? null);
		$max_age = ($badge_type['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('staff_badge_types').' SET '.
			'`order` = ?, `name` = ?, `description` = ?, `rewards` = ?, '.
			'`price` = ?, `active` = ?, `quantity` = ?, '.
			'`start_date` = ?, `end_date` = ?, `min_age` = ?, `max_age` = ?'
		);
		$stmt->bind_param(
			'isssdiissii',
			$order, $name, $description, $rewards,
			$price, $active, $quantity,
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
		$price = (isset($badge_type['price']) ? (float)$badge_type['price'] : 0);
		$active = (isset($badge_type['active']) ? ($badge_type['active'] ? 1 : 0) : 1);
		$quantity = ($badge_type['quantity'] ?? null);
		$start_date = ($badge_type['start-date'] ?? null);
		$end_date = ($badge_type['end-date'] ?? null);
		$min_age = ($badge_type['min-age'] ?? null);
		$max_age = ($badge_type['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff_badge_types').' SET '.
			'`name` = ?, `description` = ?, `rewards` = ?, '.
			'`price` = ?, `active` = ?, `quantity` = ?, '.
			'`start_date` = ?, `end_date` = ?, `min_age` = ?, `max_age` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'sssdiissiii',
			$name, $description, $rewards,
			$price, $active, $quantity,
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
			'DELETE FROM '.$this->cm_db->table_name('staff_badge_types').
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
			'UPDATE '.$this->cm_db->table_name('staff_badge_types').
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
			$this->cm_db->table_name('staff_badge_types').
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
					'UPDATE '.$this->cm_db->table_name('staff_badge_types').
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

	public function get_blacklist_entry($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `first_name`, `last_name`, `fandom_name`,'.
			' `email_address`, `phone_number`, `added_by`, `notes`,'.
			' `normalized_real_name`,'.
			' `normalized_reversed_name`,'.
			' `normalized_fandom_name`,'.
			' `normalized_email_address`,'.
			' `normalized_phone_number`'.
			' FROM '.$this->cm_db->table_name('staff_blacklist').
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

	public function list_blacklist_entries() {
		$blacklist = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `first_name`, `last_name`, `fandom_name`,'.
			' `email_address`, `phone_number`, `added_by`, `notes`,'.
			' `normalized_real_name`,'.
			' `normalized_reversed_name`,'.
			' `normalized_fandom_name`,'.
			' `normalized_email_address`,'.
			' `normalized_phone_number`'.
			' FROM '.$this->cm_db->table_name('staff_blacklist').
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

	public function create_blacklist_entry($entry) {
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
			'INSERT INTO '.$this->cm_db->table_name('staff_blacklist').' SET '.
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

	public function update_blacklist_entry($entry) {
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
			'UPDATE '.$this->cm_db->table_name('staff_blacklist').' SET '.
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

	public function delete_blacklist_entry($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('staff_blacklist').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function is_blacklisted($person) {
		if (!$person) return false;
		$first_name = ($person['first-name'] ?? '');
		$last_name = ($person['last-name'] ?? '');
		$fandom_name = ($person['fandom-name'] ?? '');
		$email_address = ($person['email-address'] ?? '');
		$phone_number = ($person['phone-number'] ?? '');
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
			$this->cm_db->table_name('staff_blacklist').
			' WHERE '.implode(' OR ', $query_params).' LIMIT 1'
		);
		call_user_func_array(array($stmt, 'bind_param'), $bind_params);
		$stmt->execute();
		$stmt->bind_result($id);
		$success = $stmt->fetch();
		$stmt->close();
		return $success ? $this->get_blacklist_entry($id) : false;
	}

	public function get_staff_member($id, $uuid = null, $name_map = null, $dept_map = null, $pos_map = null, $fdb = null) {
		if (!$id && !$uuid) return false;
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$dept_map) $dept_map = $this->get_department_map();
		if (!$pos_map) $pos_map = $this->get_position_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'staff');
		$query = (
			'SELECT `id`, `uuid`, `date_created`, `date_modified`,'.
			' `print_count`, `print_first_time`, `print_last_time`,'.
			' `checkin_count`, `checkin_first_time`, `checkin_last_time`,'.
			' `badge_type_id`, `notes`, `first_name`, `last_name`,'.
			' `fandom_name`, `name_on_badge`, `date_of_birth`,'.
			' `subscribed`, `email_address`, `phone_number`,'.
			' `address_1`, `address_2`, `city`, `state`, `zip_code`,'.
			' `country`, `ice_name`, `ice_relationship`,'.
			' `ice_email_address`, `ice_phone_number`,'.
			' `application_status`, `mail_alias_1`,'.
			' `mail_alias_2`, `mailbox_type`,'.
			' `payment_status`, `payment_badge_price`,'.
			' `payment_group_uuid`, `payment_type`,'.
			' `payment_txn_id`, `payment_txn_amt`,'.
			' `payment_date`, `payment_details`'.
			' FROM '.$this->cm_db->table_name('staff')
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
			$badge_type_id, $notes, $first_name, $last_name,
			$fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state, $zip_code,
			$country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number,
			$application_status, $mail_alias_1,
			$mail_alias_2, $mailbox_type,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		if ($stmt->fetch()) {
			$reg_url = get_site_url(true) . '/staff';
			$id_string = 'S' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=' . $qr_data;
			$badge_type_id_string = 'SB' . $badge_type_id;
			$badge_type_name = ($name_map[$badge_type_id] ?? $badge_type_id);
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
			$unsubscribe_link = $reg_url . '/unsubscribe.php?email=' . $email_address;
			$address = trim(trim($address_1) . "\n" . trim($address_2));
			$csz = trim(trim(trim($city) . ' ' . trim($state)) . ' ' . trim($zip_code));
			$address_full = trim(trim(trim($address) . "\n" . trim($csz)) . "\n" . trim($country));
			$review_link = (($payment_group_uuid && $payment_txn_id) ? (
				$reg_url . '/review.php' .
				'?gid=' . $payment_group_uuid .
				'&tid=' . $payment_txn_id
			) : null);
			$search_content = array(
				$id, $uuid, $notes, $first_name, $last_name, $fandom_name,
				$date_of_birth, $email_address, $phone_number,
				$address_1, $address_2, $city, $state, $zip_code, $country,
				$application_status, $mail_alias_1, $mail_alias_2,
				$payment_status, $payment_group_uuid, $payment_txn_id,
				$id_string, $qr_data, $badge_type_name,
				$real_name, $only_name, $large_name, $small_name,
				$display_name, $address, $csz, $address_full
			);
			$result = array(
				'type' => 'staff',
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
				'badge-type-id' => $badge_type_id,
				'badge-type-id-string' => $badge_type_id_string,
				'badge-type-name' => $badge_type_name,
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
				'application-status' => $application_status,
				'mail-alias-1' => $mail_alias_1,
				'mail-alias-2' => $mail_alias_2,
				'mailbox-type' => $mailbox_type,
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

			$stmt = $this->cm_db->connection->prepare(
				'SELECT `department_id`, `department_name`, `position_id`, `position_name`'.
				' FROM '.$this->cm_db->table_name('staff_assigned_positions').
				' WHERE `staff_id` = ?'.
				' ORDER BY `order`'
			);
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->bind_result(
				$department_id, $department_name,
				$position_id, $position_name
			);
			$assigned_positions = array();
			while ($stmt->fetch()) {
				$assigned_position = array();
				if ($position_id && isset($pos_map[$position_id])) {
					$assigned_position['position-id'] = $position_id;
					$assigned_position['position-name'] = $pos_map[$position_id]['name'];
					$department_id = $pos_map[$position_id]['parent-id'];
				} else {
					$assigned_position['position-id'] = null;
					$assigned_position['position-name'] = ($position_name ? $position_name : null);
				}
				if ($department_id && isset($dept_map[$department_id])) {
					$assigned_position['department-id'] = $department_id;
					$assigned_position['department-name'] = $dept_map[$department_id]['name'];
				} else {
					$assigned_position['department-id'] = null;
					$assigned_position['department-name'] = ($department_name ? $department_name : null);
				}
				$assigned_position['position-name-s'] = $assigned_position['department-name'].' '.$assigned_position['position-name'];
				$assigned_position['position-name-h'] = $assigned_position['department-name'].' - '.$assigned_position['position-name'];
				$assigned_positions[] = $assigned_position;
				$result['search-content'][] = $assigned_position['department-name'];
				$result['search-content'][] = $assigned_position['position-name'];
			}
			if ($assigned_positions) {
				$result['assigned-department-id'] = $assigned_positions[0]['department-id'];
				$result['assigned-department-name'] = $assigned_positions[0]['department-name'];
				$result['assigned-position-id'] = $assigned_positions[0]['position-id'];
				$result['assigned-position-name'] = $assigned_positions[0]['position-name'];
				$result['assigned-position-name-s'] = $assigned_positions[0]['position-name-s'];
				$result['assigned-position-name-h'] = $assigned_positions[0]['position-name-h'];
				$result['assigned-department-ids'] = array_column_simple($assigned_positions, 'department-id');
				$result['assigned-department-names'] = array_column_simple($assigned_positions, 'department-name');
				$result['assigned-position-ids'] = array_column_simple($assigned_positions, 'position-id');
				$result['assigned-position-names'] = array_column_simple($assigned_positions, 'position-name');
				$result['assigned-position-names-s'] = array_column_simple($assigned_positions, 'position-name-s');
				$result['assigned-position-names-h'] = array_column_simple($assigned_positions, 'position-name-h');
				$result['assigned-positions'] = $assigned_positions;
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

	public function list_staff_members($gid = null, $tid = null, $name_map = null, $dept_map = null, $pos_map = null, $fdb = null) {
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$dept_map) $dept_map = $this->get_department_map();
		if (!$pos_map) $pos_map = $this->get_position_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'staff');
		$staff_members = array();
		$query = (
			'SELECT `id`, `uuid`, `date_created`, `date_modified`,'.
			' `print_count`, `print_first_time`, `print_last_time`,'.
			' `checkin_count`, `checkin_first_time`, `checkin_last_time`,'.
			' `badge_type_id`, `notes`, `first_name`, `last_name`,'.
			' `fandom_name`, `name_on_badge`, `date_of_birth`,'.
			' `subscribed`, `email_address`, `phone_number`,'.
			' `address_1`, `address_2`, `city`, `state`, `zip_code`,'.
			' `country`, `ice_name`, `ice_relationship`,'.
			' `ice_email_address`, `ice_phone_number`,'.
			' `application_status`, `mail_alias_1`,'.
			' `mail_alias_2`, `mailbox_type`,'.
			' `payment_status`, `payment_badge_price`,'.
			' `payment_group_uuid`, `payment_type`,'.
			' `payment_txn_id`, `payment_txn_amt`,'.
			' `payment_date`, `payment_details`'.
			' FROM '.$this->cm_db->table_name('staff')
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
			$print_count, $print_first_time, $print_last_time,
			$checkin_count, $checkin_first_time, $checkin_last_time,
			$badge_type_id, $notes, $first_name, $last_name,
			$fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state, $zip_code,
			$country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number,
			$application_status, $mail_alias_1,
			$mail_alias_2, $mailbox_type,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$reg_url = get_site_url(true) . '/staff';
		$qr_base_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=';
		while ($stmt->fetch()) {
			$id_string = 'S' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = $qr_base_url . $qr_data;
			$badge_type_id_string = 'SB' . $badge_type_id;
			$badge_type_name = ($name_map[$badge_type_id] ?? $badge_type_id);
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
			$unsubscribe_link = $reg_url . '/unsubscribe.php?email=' . $email_address;
			$address = trim(trim($address_1) . "\n" . trim($address_2));
			$csz = trim(trim(trim($city) . ' ' . trim($state)) . ' ' . trim($zip_code));
			$address_full = trim(trim(trim($address) . "\n" . trim($csz)) . "\n" . trim($country));
			$review_link = (($payment_group_uuid && $payment_txn_id) ? (
				$reg_url . '/review.php' .
				'?gid=' . $payment_group_uuid .
				'&tid=' . $payment_txn_id
			) : null);
			$search_content = array(
				$id, $uuid, $notes, $first_name, $last_name, $fandom_name,
				$date_of_birth, $email_address, $phone_number,
				$address_1, $address_2, $city, $state, $zip_code, $country,
				$application_status, $mail_alias_1, $mail_alias_2,
				$payment_status, $payment_group_uuid, $payment_txn_id,
				$id_string, $qr_data, $badge_type_name,
				$real_name, $only_name, $large_name, $small_name,
				$display_name, $address, $csz, $address_full
			);
			$staff_members[] = array(
				'type' => 'staff',
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
				'badge-type-id' => $badge_type_id,
				'badge-type-id-string' => $badge_type_id_string,
				'badge-type-name' => $badge_type_name,
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
				'application-status' => $application_status,
				'mail-alias-1' => $mail_alias_1,
				'mail-alias-2' => $mail_alias_2,
				'mailbox-type' => $mailbox_type,
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
		foreach ($staff_members as $i => $staff_member) {
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `department_id`, `department_name`, `position_id`, `position_name`'.
				' FROM '.$this->cm_db->table_name('staff_assigned_positions').
				' WHERE `staff_id` = ?'.
				' ORDER BY `order`'
			);
			$stmt->bind_param('i', $staff_member['id']);
			$stmt->execute();
			$stmt->bind_result(
				$department_id, $department_name,
				$position_id, $position_name
			);
			$assigned_positions = array();
			while ($stmt->fetch()) {
				$assigned_position = array();
				if ($position_id && isset($pos_map[$position_id])) {
					$assigned_position['position-id'] = $position_id;
					$assigned_position['position-name'] = $pos_map[$position_id]['name'];
					$department_id = $pos_map[$position_id]['parent-id'];
				} else {
					$assigned_position['position-id'] = null;
					$assigned_position['position-name'] = ($position_name ? $position_name : null);
				}
				if ($department_id && isset($dept_map[$department_id])) {
					$assigned_position['department-id'] = $department_id;
					$assigned_position['department-name'] = $dept_map[$department_id]['name'];
				} else {
					$assigned_position['department-id'] = null;
					$assigned_position['department-name'] = ($department_name ? $department_name : null);
				}
				$assigned_position['position-name-s'] = $assigned_position['department-name'].' '.$assigned_position['position-name'];
				$assigned_position['position-name-h'] = $assigned_position['department-name'].' - '.$assigned_position['position-name'];
				$assigned_positions[] = $assigned_position;
				$staff_members[$i]['search-content'][] = $assigned_position['department-name'];
				$staff_members[$i]['search-content'][] = $assigned_position['position-name'];
			}
			if ($assigned_positions) {
				$staff_members[$i]['assigned-department-id'] = $assigned_positions[0]['department-id'];
				$staff_members[$i]['assigned-department-name'] = $assigned_positions[0]['department-name'];
				$staff_members[$i]['assigned-position-id'] = $assigned_positions[0]['position-id'];
				$staff_members[$i]['assigned-position-name'] = $assigned_positions[0]['position-name'];
				$staff_members[$i]['assigned-position-name-s'] = $assigned_positions[0]['position-name-s'];
				$staff_members[$i]['assigned-position-name-h'] = $assigned_positions[0]['position-name-h'];
				$staff_members[$i]['assigned-department-ids'] = array_column_simple($assigned_positions, 'department-id');
				$staff_members[$i]['assigned-department-names'] = array_column_simple($assigned_positions, 'department-name');
				$staff_members[$i]['assigned-position-ids'] = array_column_simple($assigned_positions, 'position-id');
				$staff_members[$i]['assigned-position-names'] = array_column_simple($assigned_positions, 'position-name');
				$staff_members[$i]['assigned-position-names-s'] = array_column_simple($assigned_positions, 'position-name-s');
				$staff_members[$i]['assigned-position-names-h'] = array_column_simple($assigned_positions, 'position-name-h');
				$staff_members[$i]['assigned-positions'] = $assigned_positions;
			}
			$stmt->close();

			$answers = $fdb->list_answers($staff_member['id']);
			if ($answers) {
				$staff_members[$i]['form-answers'] = $answers;
				foreach ($answers as $qid => $answer) {
					$answer_string = implode("\n", $answer);
					$staff_members[$i]['form-answer-array-' . $qid] = $answer;
					$staff_members[$i]['form-answer-string-' . $qid] = $answer_string;
					$staff_members[$i]['search-content'][] = $answer_string;
				}
			}
		}
		return $staff_members;
	}

	public function create_staff_member($staff_member, $dept_map = null, $pos_map = null, $fdb = null) {
		if (!$staff_member) return false;
		$badge_type_id = ($staff_member['badge-type-id'] ?? null);
		$notes = ($staff_member['notes'] ?? null);
		$first_name = ($staff_member['first-name'] ?? '');
		$last_name = ($staff_member['last-name'] ?? '');
		$fandom_name = ($staff_member['fandom-name'] ?? '');
		$name_on_badge = (($fandom_name && isset($staff_member['name-on-badge'])) ? $staff_member['name-on-badge'] : 'Real Name Only');
		$date_of_birth = ($staff_member['date-of-birth'] ?? null);
		$subscribed = (isset($staff_member['subscribed']) ? ($staff_member['subscribed'] ? 1 : 0) : 0);
		$email_address = ($staff_member['email-address'] ?? '');
		$phone_number = ($staff_member['phone-number'] ?? '');
		$address_1 = ($staff_member['address-1'] ?? '');
		$address_2 = ($staff_member['address-2'] ?? '');
		$city = ($staff_member['city'] ?? '');
		$state = ($staff_member['state'] ?? '');
		$zip_code = ($staff_member['zip-code'] ?? '');
		$country = ($staff_member['country'] ?? '');
		$ice_name = ($staff_member['ice-name'] ?? '');
		$ice_relationship = ($staff_member['ice-relationship'] ?? '');
		$ice_email_address = ($staff_member['ice-email-address'] ?? '');
		$ice_phone_number = ($staff_member['ice-phone-number'] ?? '');
		$application_status = ($staff_member['application-status'] ?? null);
		$mail_alias_1 = ((isset($staff_member['mail-alias-1']) && $staff_member['mail-alias-1']) ? $staff_member['mail-alias-1'] : null);
		$mail_alias_2 = ((isset($staff_member['mail-alias-2']) && $staff_member['mail-alias-2']) ? $staff_member['mail-alias-2'] : null);
		$mailbox_type = ((isset($staff_member['mailbox-type']) && $staff_member['mailbox-type']) ? $staff_member['mailbox-type'] : null);
		$payment_status = ($staff_member['payment-status'] ?? null);
		$payment_badge_price = ($staff_member['payment-badge-price'] ?? null);
		$payment_group_uuid = ($staff_member['payment-group-uuid'] ?? null);
		$payment_type = ($staff_member['payment-type'] ?? null);
		$payment_txn_id = ($staff_member['payment-txn-id'] ?? null);
		$payment_txn_amt = ($staff_member['payment-txn-amt'] ?? null);
		$payment_date = ($staff_member['payment-date'] ?? null);
		$payment_details = ($staff_member['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('staff').' SET '.
			'`uuid` = UUID(), `date_created` = NOW(), `date_modified` = NOW(), '.
			'`badge_type_id` = ?, `notes` = ?, `first_name` = ?, `last_name` = ?, '.
			'`fandom_name` = ?, `name_on_badge` = ?, `date_of_birth` = ?, '.
			'`subscribed` = ?, `email_address` = ?, `phone_number` = ?, '.
			'`address_1` = ?, `address_2` = ?, `city` = ?, `state` = ?, '.
			'`zip_code` = ?, `country` = ?, `ice_name` = ?, `ice_relationship` = ?, '.
			'`ice_email_address` = ?, `ice_phone_number` = ?, '.
			'`application_status` = ?, `mail_alias_1` = ?, '.
			'`mail_alias_2` = ?, `mailbox_type` = ?, '.
			'`payment_status` = ?, `payment_badge_price` = ?, '.
			'`payment_group_uuid` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'
		);
		$stmt->bind_param(
			'issssssisssssssssssssssssdsssdss',
			$badge_type_id, $notes, $first_name, $last_name,
			$fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state,
			$zip_code, $country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number,
			$application_status, $mail_alias_1,
			$mail_alias_2, $mailbox_type,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		if ($id !== false) {
			if ($dept_map && $pos_map && isset($staff_member['assigned-positions'])) {
				$order = 0;
				foreach ($staff_member['assigned-positions'] as $ap) {
					$order++;
					$department_id = (isset($ap['department-id']) && (int)$ap['department-id']) ? (int)$ap['department-id'] : null;
					$department_name = (isset($ap['department-name']) && $ap['department-name']) ? $ap['department-name'] : null;
					$position_id = (isset($ap['position-id']) && (int)$ap['position-id']) ? (int)$ap['position-id'] : null;
					$position_name = (isset($ap['position-name']) && $ap['position-name']) ? $ap['position-name'] : null;
					if ($position_id && isset($pos_map[$position_id])) {
						$position_name = $pos_map[$position_id]['name'];
						$department_id = $pos_map[$position_id]['parent-id'];
					} else {
						$position_id = null;
					}
					if ($department_id && isset($dept_map[$department_id])) {
						$department_name = $dept_map[$department_id]['name'];
					} else {
						$department_id = null;
					}
					$stmt = $this->cm_db->connection->prepare(
						'INSERT INTO '.$this->cm_db->table_name('staff_assigned_positions').' SET '.
						'`staff_id` = ?, `order` = ?, '.
						'`department_id` = ?, `department_name` = ?, '.
						'`position_id` = ?, `position_name` = ?'
					);
					$stmt->bind_param(
						'iiisis',
						$id, $order,
						$department_id, $department_name,
						$position_id, $position_name
					);
					$stmt->execute();
					$stmt->close();
				}
			}
			if ($fdb && isset($staff_member['form-answers'])) {
				$fdb->set_answers($id, $staff_member['form-answers']);
			}
			$staff_member = $this->get_staff_member($id);
			$this->cm_ldb->add_entity($staff_member);
		}
		return $id;
	}

	public function update_staff_member($staff_member, $dept_map = null, $pos_map = null, $fdb = null) {
		if (!$staff_member || !isset($staff_member['id']) || !$staff_member['id']) return false;
		$badge_type_id = ($staff_member['badge-type-id'] ?? null);
		$notes = ($staff_member['notes'] ?? null);
		$first_name = ($staff_member['first-name'] ?? '');
		$last_name = ($staff_member['last-name'] ?? '');
		$fandom_name = ($staff_member['fandom-name'] ?? '');
		$name_on_badge = (($fandom_name && isset($staff_member['name-on-badge'])) ? $staff_member['name-on-badge'] : 'Real Name Only');
		$date_of_birth = ($staff_member['date-of-birth'] ?? null);
		$subscribed = (isset($staff_member['subscribed']) ? ($staff_member['subscribed'] ? 1 : 0) : 0);
		$email_address = ($staff_member['email-address'] ?? '');
		$phone_number = ($staff_member['phone-number'] ?? '');
		$address_1 = ($staff_member['address-1'] ?? '');
		$address_2 = ($staff_member['address-2'] ?? '');
		$city = ($staff_member['city'] ?? '');
		$state = ($staff_member['state'] ?? '');
		$zip_code = ($staff_member['zip-code'] ?? '');
		$country = ($staff_member['country'] ?? '');
		$ice_name = ($staff_member['ice-name'] ?? '');
		$ice_relationship = ($staff_member['ice-relationship'] ?? '');
		$ice_email_address = ($staff_member['ice-email-address'] ?? '');
		$ice_phone_number = ($staff_member['ice-phone-number'] ?? '');
		$application_status = ($staff_member['application-status'] ?? null);
		$mail_alias_1 = ((isset($staff_member['mail-alias-1']) && $staff_member['mail-alias-1']) ? $staff_member['mail-alias-1'] : null);
		$mail_alias_2 = ((isset($staff_member['mail-alias-2']) && $staff_member['mail-alias-2']) ? $staff_member['mail-alias-2'] : null);
		$mailbox_type = ((isset($staff_member['mailbox-type']) && $staff_member['mailbox-type']) ? $staff_member['mailbox-type'] : null);
		$payment_status = ($staff_member['payment-status'] ?? null);
		$payment_badge_price = ($staff_member['payment-badge-price'] ?? null);
		$payment_group_uuid = ($staff_member['payment-group-uuid'] ?? null);
		$payment_type = ($staff_member['payment-type'] ?? null);
		$payment_txn_id = ($staff_member['payment-txn-id'] ?? null);
		$payment_txn_amt = ($staff_member['payment-txn-amt'] ?? null);
		$payment_date = ($staff_member['payment-date'] ?? null);
		$payment_details = ($staff_member['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff').' SET '.
			'`date_modified` = NOW(), '.
			'`badge_type_id` = ?, `notes` = ?, `first_name` = ?, `last_name` = ?, '.
			'`fandom_name` = ?, `name_on_badge` = ?, `date_of_birth` = ?, '.
			'`subscribed` = ?, `email_address` = ?, `phone_number` = ?, '.
			'`address_1` = ?, `address_2` = ?, `city` = ?, `state` = ?, '.
			'`zip_code` = ?, `country` = ?, `ice_name` = ?, `ice_relationship` = ?, '.
			'`ice_email_address` = ?, `ice_phone_number` = ?, '.
			'`application_status` = ?, `mail_alias_1` = ?, '.
			'`mail_alias_2` = ?, `mailbox_type` = ?, '.
			'`payment_status` = ?, `payment_badge_price` = ?, '.
			'`payment_group_uuid` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'issssssisssssssssssssssssdsssdssi',
			$badge_type_id, $notes, $first_name, $last_name,
			$fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state,
			$zip_code, $country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number,
			$application_status, $mail_alias_1,
			$mail_alias_2, $mailbox_type,
			$payment_status, $payment_badge_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details,
			$staff_member['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			if ($dept_map && $pos_map && isset($staff_member['assigned-positions'])) {
				$stmt = $this->cm_db->connection->prepare(
					'DELETE FROM '.$this->cm_db->table_name('staff_assigned_positions').
					' WHERE `staff_id` = ?'
				);
				$stmt->bind_param('i', $staff_member['id']);
				$stmt->execute();
				$stmt->close();
				$order = 0;
				foreach ($staff_member['assigned-positions'] as $ap) {
					$order++;
					$department_id = (isset($ap['department-id']) && (int)$ap['department-id']) ? (int)$ap['department-id'] : null;
					$department_name = (isset($ap['department-name']) && $ap['department-name']) ? $ap['department-name'] : null;
					$position_id = (isset($ap['position-id']) && (int)$ap['position-id']) ? (int)$ap['position-id'] : null;
					$position_name = (isset($ap['position-name']) && $ap['position-name']) ? $ap['position-name'] : null;
					if ($position_id && isset($pos_map[$position_id])) {
						$position_name = $pos_map[$position_id]['name'];
						$department_id = $pos_map[$position_id]['parent-id'];
					} else {
						$position_id = null;
					}
					if ($department_id && isset($dept_map[$department_id])) {
						$department_name = $dept_map[$department_id]['name'];
					} else {
						$department_id = null;
					}
					$stmt = $this->cm_db->connection->prepare(
						'INSERT INTO '.$this->cm_db->table_name('staff_assigned_positions').' SET '.
						'`staff_id` = ?, `order` = ?, '.
						'`department_id` = ?, `department_name` = ?, '.
						'`position_id` = ?, `position_name` = ?'
					);
					$stmt->bind_param(
						'iiisis',
						$staff_member['id'], $order,
						$department_id, $department_name,
						$position_id, $position_name
					);
					$stmt->execute();
					$stmt->close();
				}
			}
			if ($fdb && isset($staff_member['form-answers'])) {
				$fdb->clear_answers($staff_member['id']);
				$fdb->set_answers($staff_member['id'], $staff_member['form-answers']);
			}
			$staff_member = $this->get_staff_member($staff_member['id']);
			$this->cm_ldb->remove_entity($staff_member['id']);
			$this->cm_ldb->add_entity($staff_member);
		}
		return $success;
	}

	public function delete_staff_member($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('staff').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$stmt = $this->cm_db->connection->prepare(
				'DELETE FROM '.$this->cm_db->table_name('staff_assigned_positions').
				' WHERE `staff_id` = ?'
			);
			$stmt->bind_param('i', $id);
			$stmt->execute();
			$stmt->close();
			$this->cm_ldb->remove_entity($id);
		}
		return $success;
	}

	public function already_exists($person) {
		if (!$person) return false;
		$first_name = (isset($person['first-name']) ? strtolower($person['first-name']) : '');
		$last_name = (isset($person['last-name']) ? strtolower($person['last-name']) : '');
		$date_of_birth = (isset($person['date-of-birth']) ? strtolower($person['date-of-birth']) : null);
		$email_address = (isset($person['email-address']) ? strtolower($person['email-address']) : '');
		$stmt = $this->cm_db->connection->prepare(
			'SELECT 1 FROM '.$this->cm_db->table_name('staff').
			' WHERE LCASE(`first_name`) = ? AND LCASE(`last_name`) = ?'.
			' AND LCASE(`date_of_birth`) = ? AND LCASE(`email_address`) = ?'
		);
		$stmt->bind_param(
			'ssss', $first_name, $last_name,
			$date_of_birth, $email_address
		);
		$stmt->execute();
		$stmt->bind_result($x);
		$exists = $stmt->fetch() && $x;
		$stmt->close();
		return $exists;
	}

	public function update_payment_status($id, $status, $type, $txn_id, $txn_amt, $date, $details) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff').' SET '.
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
			$staff_member = $this->get_staff_member($id);
			$this->cm_ldb->remove_entity($id);
			$this->cm_ldb->add_entity($staff_member);
		}
		return $success;
	}

	public function unsubscribe_email_address($email) {
		if (!$email) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff').' SET '.
			'`subscribed` = FALSE WHERE LCASE(`email_address`) = LCASE(?)'
		);
		$stmt->bind_param('s', $email);
		$count = $stmt->execute() ? $this->cm_db->connection->affected_rows : false;
		$stmt->close();
		if ($count) {
			$ids = array();
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `id` FROM '.$this->cm_db->table_name('staff').
				' WHERE LCASE(`email_address`) = LCASE(?)'
			);
			$stmt->bind_param('s', $email);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) $ids[] = $id;
			$stmt->close();
			foreach ($ids as $id) {
				$staff_member = $this->get_staff_member($id);
				$this->cm_ldb->remove_entity($id);
				$this->cm_ldb->add_entity($staff_member);
			}
		}
		return $count;
	}

	public function staff_printed($id, $reset = false) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff').' SET '.
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
			$staff_member = $this->get_staff_member($id);
			$this->cm_ldb->remove_entity($id);
			$this->cm_ldb->add_entity($staff_member);
		}
		return $success;
	}

	public function staff_checked_in($id, $reset = false) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('staff').' SET '.
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
			$staff_member = $this->get_staff_member($id);
			$this->cm_ldb->remove_entity($id);
			$this->cm_ldb->add_entity($staff_member);
		}
		return $success;
	}

	public function get_staff_statistics($granularity = 300, $name_map = null) {
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
			'SELECT UNIX_TIMESTAMP(`date_created`), `badge_type_id`'.
			' FROM '.$this->cm_db->table_name('staff').
			' ORDER BY `date_created`'
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
			'SELECT UNIX_TIMESTAMP(`payment_date`), `badge_type_id`'.
			' FROM '.$this->cm_db->table_name('staff').
			' WHERE `payment_status` = \'Completed\''.
			' AND `payment_date` IS NOT NULL'.
			' ORDER BY `payment_date`'
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
			'SELECT UNIX_TIMESTAMP(`print_first_time`), `badge_type_id`'.
			' FROM '.$this->cm_db->table_name('staff').
			' WHERE `print_first_time` IS NOT NULL'.
			' ORDER BY `print_first_time`'
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
			'SELECT UNIX_TIMESTAMP(`checkin_first_time`), `badge_type_id`'.
			' FROM '.$this->cm_db->table_name('staff').
			' WHERE `checkin_first_time` IS NOT NULL'.
			' ORDER BY `checkin_first_time`'
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
