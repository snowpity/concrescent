<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/../util/util.php';
require_once __DIR__ .'/../util/res.php';
require_once __DIR__ .'/database.php';
require_once __DIR__ .'/lists.php';
require_once __DIR__ .'/forms.php';

class cm_attendee_db {

	public $names_on_badge = array(
		'Fandom Name Large, Real Name Small',
		'Real Name Large, Fandom Name Small',
		'Fandom Name Only',
		'Real Name Only'
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
		$this->cm_db->table_def('attendee_badge_types', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`order` INTEGER NOT NULL,'.
			'`name` VARCHAR(255) NOT NULL,'.
			'`description` TEXT NULL,'.
			'`rewards` TEXT NULL,'.
			'`price` DECIMAL(7,2) NOT NULL,'.
			'`payable_onsite` BOOLEAN NOT NULL,'.
			'`active` BOOLEAN NOT NULL,'.
			'`quantity` INTEGER NULL,'.
			'`start_date` DATE NULL,'.
			'`end_date` DATE NULL,'.
			'`min_age` INTEGER NULL,'.
			'`max_age` INTEGER NULL,' .
			'`active_override_code` VARCHAR(255) NULL'
		));
		$this->cm_db->table_def('attendee_addons', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`order` INTEGER NOT NULL,'.
			'`name` VARCHAR(255) NOT NULL,'.
			'`description` TEXT NULL,'.
			'`price` DECIMAL(7,2) NOT NULL,'.
			'`payable_onsite` BOOLEAN NOT NULL,'.
			'`active` BOOLEAN NOT NULL,'.
			'`badge_type_ids` TEXT NULL,'.
			'`quantity` INTEGER NULL,'.
			'`start_date` DATE NULL,'.
			'`end_date` DATE NULL,'.
			'`min_age` INTEGER NULL,'.
			'`max_age` INTEGER NULL'
		));
		$this->cm_db->table_def('attendee_promo_codes', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`code` VARCHAR(255) NOT NULL UNIQUE KEY,'.
			'`description` TEXT NULL,'.
			'`price` DECIMAL(7,2) NOT NULL,'.
			'`percentage` BOOLEAN NOT NULL,'.
			'`active` BOOLEAN NOT NULL,'.
			'`badge_type_ids` TEXT NULL,'.
			'`limit_per_customer` INTEGER NULL,'.
			'`start_date` DATE NULL,'.
			'`end_date` DATE NULL'
		));
		$this->cm_db->table_def('attendee_blacklist', (
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
		$this->cm_db->table_def('attendees', (
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
			/* Payment Info */
			'`payment_status` ENUM('.
				'\'Incomplete\','.
				'\'Cancelled\','.
				'\'Rejected\','.
				'\'Completed\','.
				'\'Refunded\''.
			') NOT NULL,'.
			'`payment_badge_price` DECIMAL(7,2) NULL,'.
			'`payment_promo_code` VARCHAR(255) NULL,'.
			'`payment_promo_price` DECIMAL(7,2) NULL,'.
			'`payment_group_uuid` VARCHAR(255) NOT NULL,'.
			'`payment_type` VARCHAR(255) NULL,'.
			'`payment_txn_id` VARCHAR(255) NULL,'.
			'`payment_txn_amt` DECIMAL(7,2) NULL,'.
			'`payment_date` DATETIME NULL,'.
			'`payment_details` TEXT NULL'
		));
		$this->cm_db->table_def('attendee_addon_purchases', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`attendee_id` INTEGER NOT NULL,'.
			'`addon_id` INTEGER NOT NULL,'.
			'`payment_price` DECIMAL(7,2) NOT NULL,'.
			'`payment_status` ENUM('.
				'\'Incomplete\','.
				'\'Cancelled\','.
				'\'Rejected\','.
				'\'Completed\','.
				'\'Refunded\''.
			') NOT NULL,'.
			'`payment_type` VARCHAR(255) NULL,'.
			'`payment_txn_id` VARCHAR(255) NULL,'.
			'`payment_txn_amt` DECIMAL(7,2) NULL,'.
			'`payment_date` DATETIME NULL,'.
			'`payment_details` TEXT NULL'
		));
		$this->cm_ldb = new cm_lists_db($this->cm_db, 'attendee_search_index');
	}

	public function get_badge_type($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT b.`id`, b.`order`, b.`name`, b.`description`, b.`rewards`,'.
			' b.`price`, b.`payable_onsite`, b.`active`, b.`quantity`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('attendees').' a'.
			' WHERE a.`badge_type_id` = b.`id` AND a.`payment_status` = \'Completed\') c'.
			' FROM '.$this->cm_db->table_name('attendee_badge_types').' b'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $order, $name, $description, $rewards,
			$price, $payable_onsite, $active, $quantity,
			$start_date, $end_date, $min_age, $max_age,
			$quantity_sold
		);
		if ($stmt->fetch()) {
			$event_start_date = $this->event_info['start_date'];
			$event_end_date   = $this->event_info['end_date'  ];
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$result = array(
				'id' => $id,
				'id-string' => 'AB' . $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'rewards' => ($rewards ? explode("\n", $rewards) : array()),
				'price' => $price,
				'payable-onsite' => !!$payable_onsite,
				'active' => !!$active,
				'quantity' => $quantity,
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
			' FROM '.$this->cm_db->table_name('attendee_badge_types').
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
			' FROM '.$this->cm_db->table_name('attendee_badge_types').
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

	public function list_badge_types($active_only = false, $unsold_only = false, $onsite_only = false, $override_code = '') {
		$badge_types = array();
		$query = (
			'SELECT b.`id`, b.`order`, b.`name`, b.`description`, b.`rewards`,'.
			' b.`price`, b.`payable_onsite`, b.`active`, b.`quantity`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('attendees').' a'.
			' WHERE a.`badge_type_id` = b.`id` AND a.`payment_status` = \'Completed\') c'.
			' FROM '.$this->cm_db->table_name('attendee_badge_types').' b'
		);
		$first = true;
		if ($active_only) {
			$query .= (
				($first ? ' WHERE' : ' AND').' b.`active`'.
				' AND (b.`start_date` IS NULL OR b.`start_date` <= CURDATE())'.
				' AND (b.`end_date` IS NULL OR b.`end_date` >= CURDATE())'.
				' OR (IFNULL(b.`active_override_code`,\'\') = ? )'
			);
			$first = false;
			if($override_code == '')
				$override_code = 'Todo: Do this properly';

		}
		if ($onsite_only) {
			$query .= ($first ? ' WHERE' : ' AND').' b.`payable_onsite`';
			$first = false;
		}
		$stmt = $this->cm_db->connection->prepare($query . ' ORDER BY b.`order`');
		if ($active_only) {
			$stmt->bind_param(
				's',
				$override_code
			);
		}
		$stmt->execute();
		$stmt->bind_result(
			$id, $order, $name, $description, $rewards,
			$price, $payable_onsite, $active, $quantity,
			$start_date, $end_date, $min_age, $max_age,
			$quantity_sold
		);
		$event_start_date = $this->event_info['start_date'];
		$event_end_date   = $this->event_info['end_date'  ];
		while ($stmt->fetch()) {
			if ($unsold_only && !(is_null($quantity) || $quantity > $quantity_sold)) continue;
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$badge_types[] = array(
				'id' => $id,
				'id-string' => 'AB' . $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'rewards' => ($rewards ? explode("\n", $rewards) : array()),
				'price' => $price,
				'payable-onsite' => !!$payable_onsite,
				'active' => !!$active,
				'quantity' => $quantity,
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
			$this->cm_db->table_name('attendee_badge_types')
		);
		$stmt->execute();
		$stmt->bind_result($order);
		$stmt->fetch();
		$stmt->close();
		$name = ($badge_type['name'] ?? '');
		$description = ($badge_type['description'] ?? '');
		$rewards = (isset($badge_type['rewards']) ? implode("\n", $badge_type['rewards']) : '');
		$price = (isset($badge_type['price']) ? (float)$badge_type['price'] : 0);
		$payable_onsite = (isset($badge_type['payable-onsite']) ? ($badge_type['payable-onsite'] ? 1 : 0) : 0);
		$active = (isset($badge_type['active']) ? ($badge_type['active'] ? 1 : 0) : 1);
		$quantity = ($badge_type['quantity'] ?? null);
		$start_date = ($badge_type['start-date'] ?? null);
		$end_date = ($badge_type['end-date'] ?? null);
		$min_age = ($badge_type['min-age'] ?? null);
		$max_age = ($badge_type['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('attendee_badge_types').' SET '.
			'`order` = ?, `name` = ?, `description` = ?, `rewards` = ?, '.
			'`price` = ?, `payable_onsite` = ?, `active` = ?, `quantity` = ?, '.
			'`start_date` = ?, `end_date` = ?, `min_age` = ?, `max_age` = ?'
		);
		$stmt->bind_param(
			'isssdiiissii',
			$order, $name, $description, $rewards,
			$price, $payable_onsite, $active, $quantity,
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
		$payable_onsite = (isset($badge_type['payable-onsite']) ? ($badge_type['payable-onsite'] ? 1 : 0) : 0);
		$active = (isset($badge_type['active']) ? ($badge_type['active'] ? 1 : 0) : 1);
		$quantity = ($badge_type['quantity'] ?? null);
		$start_date = ($badge_type['start-date'] ?? null);
		$end_date = ($badge_type['end-date'] ?? null);
		$min_age = ($badge_type['min-age'] ?? null);
		$max_age = ($badge_type['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendee_badge_types').' SET '.
			'`name` = ?, `description` = ?, `rewards` = ?, '.
			'`price` = ?, `payable_onsite` = ?, `active` = ?, `quantity` = ?, '.
			'`start_date` = ?, `end_date` = ?, `min_age` = ?, `max_age` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'sssdiiissiii',
			$name, $description, $rewards,
			$price, $payable_onsite, $active, $quantity,
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
			'DELETE FROM '.$this->cm_db->table_name('attendee_badge_types').
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
			'UPDATE '.$this->cm_db->table_name('attendee_badge_types').
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
			$this->cm_db->table_name('attendee_badge_types').
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
					'UPDATE '.$this->cm_db->table_name('attendee_badge_types').
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

	public function addon_applies($addon, $badge_type_id) {
		return ($addon && $addon['badge-type-ids'] && (
			in_array('*', $addon['badge-type-ids']) ||
			in_array($badge_type_id, $addon['badge-type-ids'])
		));
	}

	public function get_addon($id, $name_map = null) {
		if (!$id) return false;
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT b.`id`, b.`order`, b.`name`, b.`description`, b.`price`,'.
			' b.`payable_onsite`, b.`active`, b.`badge_type_ids`, b.`quantity`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('attendee_addon_purchases').' a'.
			' WHERE a.`addon_id` = b.`id` AND a.`payment_status` = \'Completed\') c'.
			' FROM '.$this->cm_db->table_name('attendee_addons').' b'.
			' WHERE b.`id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $order, $name, $description, $price,
			$payable_onsite, $active, $badge_type_ids, $quantity,
			$start_date, $end_date, $min_age, $max_age,
			$quantity_sold
		);
		if ($stmt->fetch()) {
			$event_start_date = $this->event_info['start_date'];
			$event_end_date   = $this->event_info['end_date'  ];
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$result = array(
				'id' => $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'price' => $price,
				'payable-onsite' => !!$payable_onsite,
				'active' => !!$active,
				'badge-type-ids' => ($badge_type_ids ? explode(',', $badge_type_ids) : array()),
				'badge-type-names' => array(),
				'quantity' => $quantity,
				'quantity-sold' => $quantity_sold,
				'quantity-remaining' => (is_null($quantity) ? null : ($quantity - $quantity_sold)),
				'start-date' => $start_date,
				'end-date' => $end_date,
				'min-age' => $min_age,
				'max-age' => $max_age,
				'min-birthdate' => $min_birthdate,
				'max-birthdate' => $max_birthdate,
				'search-content' => array($name, $description)
			);
			foreach ($result['badge-type-ids'] as $btid) {
				$result['badge-type-names'][] = $name_map[$btid] ?? $btid;
			}
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_addons($active_only = false, $unsold_only = false, $onsite_only = false, $name_map = null) {
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		$addons = array();
		$query = (
			'SELECT b.`id`, b.`order`, b.`name`, b.`description`, b.`price`,'.
			' b.`payable_onsite`, b.`active`, b.`badge_type_ids`, b.`quantity`,'.
			' b.`start_date`, b.`end_date`, b.`min_age`, b.`max_age`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('attendee_addon_purchases').' a'.
			' WHERE a.`addon_id` = b.`id` AND a.`payment_status` = \'Completed\') c'.
			' FROM '.$this->cm_db->table_name('attendee_addons').' b'
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
		if ($onsite_only) {
			$query .= ($first ? ' WHERE' : ' AND').' b.`payable_onsite`';
			$first = false;
		}
		$stmt = $this->cm_db->connection->prepare($query . ' ORDER BY b.`order`');
		$stmt->execute();
		$stmt->bind_result(
			$id, $order, $name, $description, $price,
			$payable_onsite, $active, $badge_type_ids, $quantity,
			$start_date, $end_date, $min_age, $max_age,
			$quantity_sold
		);
		$event_start_date = $this->event_info['start_date'];
		$event_end_date   = $this->event_info['end_date'  ];
		while ($stmt->fetch()) {
			if ($unsold_only && !(is_null($quantity) || $quantity > $quantity_sold)) continue;
			$min_birthdate = $max_age ? (((int)$event_start_date - $max_age - 1) . substr($event_start_date, 4)) : null;
			$max_birthdate = $min_age ? (((int)$event_end_date   - $min_age    ) . substr($event_end_date  , 4)) : null;
			$result = array(
				'id' => $id,
				'order' => $order,
				'name' => $name,
				'description' => $description,
				'price' => $price,
				'payable-onsite' => !!$payable_onsite,
				'active' => !!$active,
				'badge-type-ids' => ($badge_type_ids ? explode(',', $badge_type_ids) : array()),
				'badge-type-names' => array(),
				'quantity' => $quantity,
				'quantity-sold' => $quantity_sold,
				'quantity-remaining' => (is_null($quantity) ? null : ($quantity - $quantity_sold)),
				'start-date' => $start_date,
				'end-date' => $end_date,
				'min-age' => $min_age,
				'max-age' => $max_age,
				'min-birthdate' => $min_birthdate,
				'max-birthdate' => $max_birthdate,
				'search-content' => array($name, $description)
			);
			foreach ($result['badge-type-ids'] as $btid) {
				$result['badge-type-names'][] = $name_map[$btid] ?? $btid;
			}
			$addons[] = $result;
		}
		$stmt->close();
		return $addons;
	}

	public function create_addon($addon) {
		if (!$addon) return false;
		$this->cm_db->connection->autocommit(false);
		$stmt = $this->cm_db->connection->prepare(
			'SELECT IFNULL(MAX(`order`),0)+1 FROM '.
			$this->cm_db->table_name('attendee_addons')
		);
		$stmt->execute();
		$stmt->bind_result($order);
		$stmt->fetch();
		$stmt->close();
		$name = ($addon['name'] ?? '');
		$description = ($addon['description'] ?? '');
		$price = (isset($addon['price']) ? (float)$addon['price'] : 0);
		$payable_onsite = (isset($addon['payable-onsite']) ? ($addon['payable-onsite'] ? 1 : 0) : 0);
		$active = (isset($addon['active']) ? ($addon['active'] ? 1 : 0) : 1);
		$badge_type_ids = (isset($addon['badge-type-ids']) ? implode(',', $addon['badge-type-ids']) : '*');
		$quantity = ($addon['quantity'] ?? null);
		$start_date = ($addon['start-date'] ?? null);
		$end_date = ($addon['end-date'] ?? null);
		$min_age = ($addon['min-age'] ?? null);
		$max_age = ($addon['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('attendee_addons').' SET '.
			'`order` = ?, `name` = ?, `description` = ?, `price` = ?, '.
			'`payable_onsite` = ?, `active` = ?, `badge_type_ids` = ?, '.
			'`quantity` = ?, `start_date` = ?, `end_date` = ?, '.
			'`min_age` = ?, `max_age` = ?'
		);
		$stmt->bind_param(
			'issdiisissii',
			$order, $name, $description, $price,
			$payable_onsite, $active, $badge_type_ids,
			$quantity, $start_date, $end_date,
			$min_age, $max_age
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		$this->cm_db->connection->autocommit(true);
		return $id;
	}

	public function update_addon($addon) {
		if (!$addon || !isset($addon['id']) || !$addon['id']) return false;
		$name = ($addon['name'] ?? '');
		$description = ($addon['description'] ?? '');
		$price = (isset($addon['price']) ? (float)$addon['price'] : 0);
		$payable_onsite = (isset($addon['payable-onsite']) ? ($addon['payable-onsite'] ? 1 : 0) : 0);
		$active = (isset($addon['active']) ? ($addon['active'] ? 1 : 0) : 1);
		$badge_type_ids = (isset($addon['badge-type-ids']) ? implode(',', $addon['badge-type-ids']) : '*');
		$quantity = ($addon['quantity'] ?? null);
		$start_date = ($addon['start-date'] ?? null);
		$end_date = ($addon['end-date'] ?? null);
		$min_age = ($addon['min-age'] ?? null);
		$max_age = ($addon['max-age'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendee_addons').' SET '.
			'`name` = ?, `description` = ?, `price` = ?, '.
			'`payable_onsite` = ?, `active` = ?, `badge_type_ids` = ?, '.
			'`quantity` = ?, `start_date` = ?, `end_date` = ?, '.
			'`min_age` = ?, `max_age` = ? WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'ssdiisissiii',
			$name, $description, $price,
			$payable_onsite, $active, $badge_type_ids,
			$quantity, $start_date, $end_date,
			$min_age, $max_age, $addon['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_addon($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('attendee_addons').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function activate_addon($id, $active) {
		if (!$id) return false;
		$active = $active ? 1 : 0;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendee_addons').
			' SET `active` = ? WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('ii', $active, $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function reorder_addon($id, $direction) {
		if (!$id || !$direction) return false;
		$this->cm_db->connection->autocommit(false);
		$ids = array();
		$index = -1;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id` FROM '.
			$this->cm_db->table_name('attendee_addons').
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
					'UPDATE '.$this->cm_db->table_name('attendee_addons').
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

	public function list_addon_purchases($attendee_id, $name_map = null) {
		if (!$attendee_id) return false;
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		$purchases = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT b.`id`, b.`attendee_id`, b.`addon_id`,'.
			' b.`payment_price`, b.`payment_status`, b.`payment_type`,'.
			' b.`payment_txn_id`, b.`payment_txn_amt`,'.
			' b.`payment_date`, b.`payment_details`,'.
			' (SELECT a.`order` FROM '.$this->cm_db->table_name('attendee_addons').' a'.
			' WHERE a.`id` = b.`addon_id`) c'.
			' FROM '.$this->cm_db->table_name('attendee_addon_purchases').' b'.
			' WHERE b.`attendee_id` = ? ORDER BY c'
		);
		$stmt->bind_param('i', $attendee_id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $attendee_id, $addon_id,
			$payment_price, $payment_status, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details,
			$order
		);
		while ($stmt->fetch()) {
			$purchases[] = array(
				'id' => $id,
				'attendee-id' => $attendee_id,
				'addon-id' => $addon_id,
				'payment-price' => $payment_price,
				'payment-status' => $payment_status,
				'payment-type' => $payment_type,
				'payment-txn-id' => $payment_txn_id,
				'payment-txn-amt' => $payment_txn_amt,
				'payment-date' => $payment_date,
				'payment-details' => $payment_details,
				'order' => $order
			);
		}
		$stmt->close();
		foreach ($purchases as $i => $purchase) {
			$addon = $this->get_addon($purchase['addon-id'], $name_map);
			if ($addon) $purchases[$i] += $addon;
		}
		return $purchases;
	}

	public function create_addon_purchases($attendee_id, $addons) {
		if (!$attendee_id) return false;
		$ids = array();
		foreach ($addons as $addon) {
			$addon_id = ($addon['addon-id'] ?? ($addon['id'] ?? null));
			$payment_price = ($addon['payment-price'] ?? ($addon['price'] ?? null));
			$payment_status = ($addon['payment-status'] ?? null);
			$payment_type = ($addon['payment-type'] ?? null);
			$payment_txn_id = ($addon['payment-txn-id'] ?? null);
			$payment_txn_amt = ($addon['payment-txn-amt'] ?? null);
			$payment_date = ($addon['payment-date'] ?? null);
			$payment_details = ($addon['payment-details'] ?? null);
			$stmt = $this->cm_db->connection->prepare(
				'INSERT INTO '.$this->cm_db->table_name('attendee_addon_purchases').' SET '.
				'`attendee_id` = ?, `addon_id` = ?, `payment_price` = ?, '.
				'`payment_status` = ?, `payment_type` = ?, '.
				'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
				'`payment_date` = ?, `payment_details` = ?'
			);
			$stmt->bind_param(
				'iidsssdss',
				$attendee_id, $addon_id, $payment_price,
				$payment_status, $payment_type,
				$payment_txn_id, $payment_txn_amt,
				$payment_date, $payment_details
			);
			$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
			$stmt->close();
			$ids[] = $id;
		}
		return $ids;
	}

	public function delete_addon_purchases($attendee_id) {
		if (!$attendee_id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('attendee_addon_purchases').
			' WHERE `attendee_id` = ?'
		);
		$stmt->bind_param('i', $attendee_id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function update_addon_purchase_payment_status($attendee_id, $status, $type, $txn, $details) {
		if (!$attendee_id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendee_addon_purchases').' SET '.
			'`payment_date` = case when `payment_status` = \'Completed\' then `payment_date` when `payment_status` != \'Completed\' and ? = \'Completed\' then UTC_TIMESTAMP() else NULL end,'.
			'`payment_status` = ?, `payment_type` = ?, '.
			'`payment_details` = ?'.
			' WHERE `attendee_id` = ? and `payment_txn_id` = ?'
		);
		$stmt->bind_param('ssssis', $status, $status, $type, $details, $attendee_id, $txn);
		$success = $stmt->execute();
		if($success === false)
		{
			error_log('Error while attempting to update addon purchase status:\n' . print_r($this->cm_db->connection->error, true));
		}
		$stmt->close();
		return $success;
	}

	public function promo_code_normalize($code) {
		return strtoupper(preg_replace('/[^A-Za-z0-9!@#$%&*?]/', '', $code));
	}

	public function promo_code_price_html($promo_code) {
		if (!isset($promo_code['price']) || !$promo_code['price']) return 'NONE';
		$price = htmlspecialchars(number_format($promo_code['price'], 2, '.', ','));
		$percentage = isset($promo_code['percentage']) && $promo_code['percentage'];
		return $percentage ? ($price . '<b>%</b>') : ('<b>$</b>' . $price);
	}

	public function promo_code_applies($promo_code, $badge_type_id) {
		return ($promo_code && $promo_code['badge-type-ids'] && (
			in_array('*', $promo_code['badge-type-ids']) ||
			in_array($badge_type_id, $promo_code['badge-type-ids'])
		));
	}

	public function apply_promo_code_to_item($promo_code, &$item, $count) {
		if (
			$promo_code &&
			(is_null($promo_code['limit-per-customer']) ||
			 $count < $promo_code['limit-per-customer']) &&
			$this->promo_code_applies($promo_code, $item['badge-type-id'])
		) {
			$badge_price = (float)$item['payment-badge-price'];
			$promo_price = (float)$promo_code['price'];
			$final_price = (
				$promo_code['percentage']
				? ($badge_price * (100.0 - $promo_price) / 100.0)
				: ($badge_price - $promo_price)
			);

			if(isset($item['editing-badge']) && $item['editing-badge'] > 0 )
			{
				//First, find them in the attendees table
				$existingBadge = $this->get_attendee($item['editing-badge'], $item['uuid'] );
				if($existingBadge)
				{
						$badge_price = max(0,$badge_price- $existingBadge['payment-badge-price']);
				}
			}

			if ($final_price < 0) $final_price = 0;
			if ($final_price > $badge_price) $final_price = $badge_price;

			//Only apply promo if it actually results in a price reduction or equality
			if((isset($item['payment-promo-price']) && $item['payment-promo-price'] >= $final_price) || !isset($item['payment-promo-price']))
			{
				$item['payment-promo-code'] = $promo_code['code'];
				$item['payment-promo-price'] = $final_price;
				$item['payment-promo-type'] = $promo_code['percentage'] ? 1 :0;
				$item['payment-promo-amount'] = $promo_price;
			}
			return true;
		} else {
			return false;
		}
	}

	public function apply_promo_code_to_items($promo_code, &$items) {
		$count = 0;
		for ($i = 0, $n = count($items); $i < $n; $i++) {
			if ($this->apply_promo_code_to_item($promo_code, $items[$i], $count)) {
				$count++;
			}
		}
		return $count;
	}

	public function get_promo_code($id, $is_code = false, $active_only = false, $name_map = null) {
		if (!$id) return false;
		if ($is_code) $id = $this->promo_code_normalize($id);
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		$query = (
			'SELECT p.`id`, p.`code`, p.`description`, p.`price`,'.
			' p.`percentage`, p.`active`, p.`badge_type_ids`,'.
			' p.`limit_per_customer`, p.`start_date`, p.`end_date`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('attendees').' a'.
			' WHERE a.`payment_promo_code` = p.`code`'.
			' AND a.`payment_status` = \'Completed\') c'.
			' FROM '.$this->cm_db->table_name('attendee_promo_codes').' p'
		);
		if ($active_only) {
			$query .= (
				' WHERE p.`active`'.
				' AND (p.`start_date` IS NULL OR p.`start_date` <= CURDATE())'.
				' AND (p.`end_date` IS NULL OR p.`end_date` >= CURDATE())'.
				' AND p.`'.($is_code ? 'code' : 'id').'` = ? LIMIT 1'
			);
		} else {
			$query .= (
				' WHERE p.`'.($is_code ? 'code' : 'id').'` = ? LIMIT 1'
			);
		}
		$stmt = $this->cm_db->connection->prepare($query);
		$stmt->bind_param(($is_code ? 's' : 'i'), $id);
		$stmt->execute();
		$stmt->bind_result(
			$id, $code, $description, $price, $percentage,
			$active, $badge_type_ids, $limit_per_customer,
			$start_date, $end_date, $quantity_used
		);
		if ($stmt->fetch()) {
			$result = array(
				'id' => $id,
				'code' => $code,
				'description' => $description,
				'price' => $price,
				'percentage' => !!$percentage,
				'price-html' => '?',
				'active' => !!$active,
				'badge-type-ids' => ($badge_type_ids ? explode(',', $badge_type_ids) : array()),
				'badge-type-names' => array(),
				'limit-per-customer' => $limit_per_customer,
				'start-date' => $start_date,
				'end-date' => $end_date,
				'quantity-used' => $quantity_used,
				'search-content' => array($code, $description)
			);
			$result['price-html'] = $this->promo_code_price_html($result);
			foreach ($result['badge-type-ids'] as $btid) {
				$result['badge-type-names'][] = $name_map[$btid] ?? $btid;
			}
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_promo_codes($name_map = null) {
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		$promo_codes = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT p.`id`, p.`code`, p.`description`, p.`price`,'.
			' p.`percentage`, p.`active`, p.`badge_type_ids`,'.
			' p.`limit_per_customer`, p.`start_date`, p.`end_date`,'.
			' (SELECT COUNT(*) FROM '.$this->cm_db->table_name('attendees').' a'.
			' WHERE a.`payment_promo_code` = p.`code`'.
			' AND a.`payment_status` = \'Completed\') c'.
			' FROM '.$this->cm_db->table_name('attendee_promo_codes').' p'.
			' ORDER BY p.`code`'
		);
		$stmt->execute();
		$stmt->bind_result(
			$id, $code, $description, $price, $percentage,
			$active, $badge_type_ids, $limit_per_customer,
			$start_date, $end_date, $quantity_used
		);
		while ($stmt->fetch()) {
			$result = array(
				'id' => $id,
				'code' => $code,
				'description' => $description,
				'price' => $price,
				'percentage' => !!$percentage,
				'price-html' => '?',
				'active' => !!$active,
				'badge-type-ids' => ($badge_type_ids ? explode(',', $badge_type_ids) : array()),
				'badge-type-names' => array(),
				'limit-per-customer' => $limit_per_customer,
				'start-date' => $start_date,
				'end-date' => $end_date,
				'quantity-used' => $quantity_used,
				'search-content' => array($code, $description)
			);
			$result['price-html'] = $this->promo_code_price_html($result);
			foreach ($result['badge-type-ids'] as $btid) {
				$result['badge-type-names'][] = $name_map[$btid] ?? $btid;
			}
			$promo_codes[] = $result;
		}
		$stmt->close();
		return $promo_codes;
	}

	public function create_promo_code($promo_code) {
		if (!$promo_code || !isset($promo_code['code']) || !$promo_code['code']) return false;
		$code = $this->promo_code_normalize($promo_code['code']);
		$description = ($promo_code['description'] ?? '');
		$price = (isset($promo_code['price']) ? (float)$promo_code['price'] : 0);
		$percentage = (isset($promo_code['percentage']) ? ($promo_code['percentage'] ? 1 : 0) : 0);
		$active = (isset($promo_code['active']) ? ($promo_code['active'] ? 1 : 0) : 1);
		$badge_type_ids = (isset($promo_code['badge-type-ids']) ? implode(',', $promo_code['badge-type-ids']) : '*');
		$limit_per_customer = ($promo_code['limit-per-customer'] ?? null);
		$start_date = ($promo_code['start-date'] ?? null);
		$end_date = ($promo_code['end-date'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('attendee_promo_codes').' SET '.
			'`code` = ?, `description` = ?, `price` = ?, '.
			'`percentage` = ?, `active` = ?, `badge_type_ids` = ?, '.
			'`limit_per_customer` = ?, `start_date` = ?, `end_date` = ?'
		);
		$stmt->bind_param(
			'ssdiisiss',
			$code, $description, $price, $percentage, $active,
			$badge_type_ids, $limit_per_customer, $start_date, $end_date
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		return $id;
	}

	public function update_promo_code($promo_code) {
		if (!$promo_code || !isset($promo_code['id']) || !$promo_code['id'] ||
		    !isset($promo_code['code']) || !$promo_code['code']) return false;
		$code = $this->promo_code_normalize($promo_code['code']);
		$description = ($promo_code['description'] ?? '');
		$price = (isset($promo_code['price']) ? (float)$promo_code['price'] : 0);
		$percentage = (isset($promo_code['percentage']) ? ($promo_code['percentage'] ? 1 : 0) : 0);
		$active = (isset($promo_code['active']) ? ($promo_code['active'] ? 1 : 0) : 1);
		$badge_type_ids = (isset($promo_code['badge-type-ids']) ? implode(',', $promo_code['badge-type-ids']) : '*');
		$limit_per_customer = ($promo_code['limit-per-customer'] ?? null);
		$start_date = ($promo_code['start-date'] ?? null);
		$end_date = ($promo_code['end-date'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendee_promo_codes').' SET '.
			'`code` = ?, `description` = ?, `price` = ?, '.
			'`percentage` = ?, `active` = ?, `badge_type_ids` = ?, '.
			'`limit_per_customer` = ?, `start_date` = ?, `end_date` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'ssdiisissi',
			$code, $description, $price, $percentage, $active,
			$badge_type_ids, $limit_per_customer, $start_date, $end_date,
			$promo_code['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_promo_code($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('attendee_promo_codes').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function activate_promo_code($id, $active) {
		if (!$id) return false;
		$active = $active ? 1 : 0;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendee_promo_codes').
			' SET `active` = ? WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('ii', $active, $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
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
			' FROM '.$this->cm_db->table_name('attendee_blacklist').
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
			' FROM '.$this->cm_db->table_name('attendee_blacklist').
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
			$real_name = trim(trim($first_name ?? '') . ' ' . trim($last_name ?? ''));
			$reversed_name = trim(trim($last_name ?? '') . ' ' . trim($first_name ?? ''));
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
			'INSERT INTO '.$this->cm_db->table_name('attendee_blacklist').' SET '.
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
			'UPDATE '.$this->cm_db->table_name('attendee_blacklist').' SET '.
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
			'DELETE FROM '.$this->cm_db->table_name('attendee_blacklist').
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
			$this->cm_db->table_name('attendee_blacklist').
			' WHERE '.implode(' OR ', $query_params).' LIMIT 1'
		);
		call_user_func_array(array($stmt, 'bind_param'), $bind_params);
		$stmt->execute();
		$stmt->bind_result($id);
		$success = $stmt->fetch();
		$stmt->close();
		return $success ? $this->get_blacklist_entry($id) : false;
	}

	public function lookup_attendee($person) {
		if (!$person) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id` FROM '.$this->cm_db->table_name('attendees').
			' WHERE LCASE(`first_name`) = LCASE(?)'.
			' AND LCASE(`last_name`) = LCASE(?)'.
			' AND (`date_of_birth` = ?'.
			' OR LCASE(`email_address`) = LCASE(?)'.
			' OR LCASE(`phone_number`) = LCASE(?))'.
			' AND `payment_status` = "Completed"'.
			' ORDER BY `payment_txn_amt` DESC LIMIT 1'
		);
		$stmt->bind_param(
			'sssss',
			$person['first-name'], $person['last-name'],
			$person['date-of-birth'], $person['email-address'],
			$person['phone-number']
		);
		$stmt->execute();
		$stmt->bind_result($id);
		if ($stmt->fetch()) {
			$stmt->close();
			return $id;
		}
		$stmt->close();
		return false;
	}



	public function retrieve_attendee_reviewlinks($email) {
		if (!$email) return false;
		$result = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `payment_group_uuid`,  `payment_txn_id` FROM '.$this->cm_db->table_name('attendees').
			' WHERE LCASE(`email_address`) = LCASE(?)'.
			' AND `payment_status` = "Completed"'.
			' GROUP BY `payment_group_uuid`,  `payment_txn_id`'
		);
		$stmt->bind_param(
			's',$email
		);
		$stmt->execute();
		$stmt->bind_result($payment_group_uuid, $payment_txn_id);
		$reg_url = get_site_url(true) . '/register';
		while ($stmt->fetch()) {
			$result[] =  (($payment_group_uuid && $payment_txn_id) ? (
				$reg_url . '/review.php' .
				'?gid=' . $payment_group_uuid .
				'&tid=' . $payment_txn_id
			) : null);
		}
		$stmt->close();
		return $result;
	}

	public function get_attendee($id, $uuid = null, $name_map = null, $fdb = null) {
		if (!$id && !$uuid) return false;
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'attendee');
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
			' `payment_status`, `payment_badge_price`,'.
			' `payment_promo_code`, `payment_promo_price`,'.
			' `payment_group_uuid`, `payment_type`,'.
			' `payment_txn_id`, `payment_txn_amt`,'.
			' `payment_date`, `payment_details`'.
			' FROM '.$this->cm_db->table_name('attendees')
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
			$payment_status, $payment_badge_price,
			$payment_promo_code, $payment_promo_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		if ($stmt->fetch()) {
			$reg_url = get_site_url(true) . '/register';
			$id_string = 'A' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=' . $qr_data;
			$badge_type_id_string = 'AB' . $badge_type_id;
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
				$address_1, $address_2, $city, $state, $zip_code,
				$country, $payment_status, $payment_promo_code,
				$payment_group_uuid, $payment_txn_id,
				$id_string, $qr_data, $badge_type_name,
				$real_name, $only_name, $large_name, $small_name,
				$display_name, $address, $csz, $address_full
			);
			$result = array(
				'type' => 'attendee',
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
				'payment-status' => $payment_status,
				'payment-badge-price' => $payment_badge_price,
				'payment-promo-code' => $payment_promo_code,
				'payment-promo-price' => $payment_promo_price,
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
			$addons = $this->list_addon_purchases($id, $name_map);
			$result['addons'] = array();
			$result['addon-ids'] = array();
			$result['addon-names'] = array();
			if ($addons) {
				$result['addons'] = $addons;
				foreach ($addons as $addon) {
					$result['addon-ids'][] = $addon['addon-id'];
					if (isset($addon['name'])) {
						$result['addon-names'][] = $addon['name'];
						$result['search-content'][] = $addon['name'];
						$result['search-content'][] = $addon['description'];
					}
				}
			}
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

	public function list_attendees($gid = null, $tid = null, $name_map = null, $fdb = null) {
		if (!$name_map) $name_map = $this->get_badge_type_name_map();
		if (!$fdb) $fdb = new cm_forms_db($this->cm_db, 'attendee');
		$attendees = array();
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
			' `payment_status`, `payment_badge_price`,'.
			' `payment_promo_code`, `payment_promo_price`,'.
			' `payment_group_uuid`, `payment_type`,'.
			' `payment_txn_id`, `payment_txn_amt`,'.
			' `payment_date`, `payment_details`'.
			' FROM '.$this->cm_db->table_name('attendees')
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
			$payment_status, $payment_badge_price,
			$payment_promo_code, $payment_promo_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$reg_url = get_site_url(true) . '/register';
		$qr_base_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=';
		while ($stmt->fetch()) {
			$id_string = 'A' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = $qr_base_url . $qr_data;
			$badge_type_id_string = 'AB' . $badge_type_id;
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
				$address_1, $address_2, $city, $state, $zip_code,
				$country, $payment_status, $payment_promo_code,
				$payment_group_uuid, $payment_txn_id,
				$id_string, $qr_data, $badge_type_name,
				$real_name, $only_name, $large_name, $small_name,
				$display_name, $address, $csz, $address_full
			);
			$attendees[] = array(
				'type' => 'attendee',
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
				'payment-status' => $payment_status,
				'payment-badge-price' => $payment_badge_price,
				'payment-promo-code' => $payment_promo_code,
				'payment-promo-price' => $payment_promo_price,
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
		foreach ($attendees as $i => $attendee) {
			$addons = $this->list_addon_purchases($attendee['id'], $name_map);
			if ($addons) {
				$attendees[$i]['addons'] = $addons;
				$attendees[$i]['addon-ids'] = array();
				$attendees[$i]['addon-names'] = array();
				foreach ($addons as $addon) {
					$attendees[$i]['addon-ids'][] = $addon['addon-id'];
					if (isset($addon['name'])) {
						$attendees[$i]['addon-names'][] = $addon['name'];
						$attendees[$i]['search-content'][] = $addon['name'];
						$attendees[$i]['search-content'][] = $addon['description'];
					}
				}
			}
			$answers = $fdb->list_answers($attendee['id']);
			if ($answers) {
				$attendees[$i]['form-answers'] = $answers;
				foreach ($answers as $qid => $answer) {
					$answer_string = implode("\n", $answer);
					$attendees[$i]['form-answer-array-' . $qid] = $answer;
					$attendees[$i]['form-answer-string-' . $qid] = $answer_string;
					$attendees[$i]['search-content'][] = $answer_string;
				}
			}
		}
		return $attendees;
	}

	public function create_attendee($attendee, $fdb = null) {
		if (!$attendee) return false;
		$badge_type_id = ($attendee['badge-type-id'] ?? null);
		$notes = ($attendee['notes'] ?? null);
		$first_name = ($attendee['first-name'] ?? '');
		$last_name = ($attendee['last-name'] ?? '');
		$fandom_name = ($attendee['fandom-name'] ?? '');
		$name_on_badge = (($fandom_name && isset($attendee['name-on-badge'])) ? $attendee['name-on-badge'] : 'Real Name Only');
		$date_of_birth = ($attendee['date-of-birth'] ?? null);
		$subscribed = (isset($attendee['subscribed']) ? ($attendee['subscribed'] ? 1 : 0) : 0);
		$email_address = ($attendee['email-address'] ?? '');
		$phone_number = ($attendee['phone-number'] ?? '');
		$address_1 = ($attendee['address-1'] ?? '');
		$address_2 = ($attendee['address-2'] ?? '');
		$city = ($attendee['city'] ?? '');
		$state = ($attendee['state'] ?? '');
		$zip_code = ($attendee['zip-code'] ?? '');
		$country = ($attendee['country'] ?? '');
		$ice_name = ($attendee['ice-name'] ?? '');
		$ice_relationship = ($attendee['ice-relationship'] ?? '');
		$ice_email_address = ($attendee['ice-email-address'] ?? '');
		$ice_phone_number = ($attendee['ice-phone-number'] ?? '');
		$payment_status = ($attendee['payment-status'] ?? null);
		$payment_badge_price = ($attendee['payment-badge-price'] ?? null);
		$payment_promo_code = ($attendee['payment-promo-code'] ?? null);
		$payment_promo_price = ($attendee['payment-promo-price'] ?? null);
		$payment_group_uuid = ($attendee['payment-group-uuid'] ?? null);
		$payment_type = ($attendee['payment-type'] ?? null);
		$payment_txn_id = ($attendee['payment-txn-id'] ?? null);
		$payment_txn_amt = ($attendee['payment-txn-amt'] ?? null);
		$payment_date = ($attendee['payment-date'] ?? null);
		$payment_details = ($attendee['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('attendees').' SET '.
			'`uuid` = UUID(), `date_created` = NOW(), `date_modified` = NOW(), '.
			'`badge_type_id` = ?, `notes` = ?, `first_name` = ?, `last_name` = ?, '.
			'`fandom_name` = ?, `name_on_badge` = ?, `date_of_birth` = ?, '.
			'`subscribed` = ?, `email_address` = ?, `phone_number` = ?, '.
			'`address_1` = ?, `address_2` = ?, `city` = ?, `state` = ?, '.
			'`zip_code` = ?, `country` = ?, `ice_name` = ?, `ice_relationship` = ?, '.
			'`ice_email_address` = ?, `ice_phone_number` = ?, '.
			'`payment_status` = ?, `payment_badge_price` = ?, '.
			'`payment_promo_code` = ?, `payment_promo_price` = ?, '.
			'`payment_group_uuid` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'
		);
		$stmt->bind_param(
			'issssssisssssssssssssdsdsssdss',
			$badge_type_id, $notes, $first_name, $last_name,
			$fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state,
			$zip_code, $country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number,
			$payment_status, $payment_badge_price,
			$payment_promo_code, $payment_promo_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		if($id === false)
		{
			error_log('Error while attempting to create attendee:\n' . print_r($this->cm_db->connection->error, true));
			error_log('Submitted data:\n' . print_r($attendee,true));
		}
		$stmt->close();
		if ($id !== false) {
			if (isset($attendee['addons'])) {
				$this->create_addon_purchases($id, $attendee['addons']);
			}
			if ($fdb && isset($attendee['form-answers'])) {
				$fdb->set_answers($id, $attendee['form-answers']);
			}
			$attendee = $this->get_attendee($id);
			$this->cm_ldb->add_entity($attendee);
		}
		return $id;
	}

	public function update_attendee($attendee, $fdb = null) {
		if (!$attendee || !isset($attendee['id']) || !$attendee['id']) return false;
		$badge_type_id = ($attendee['badge-type-id'] ?? null);
		$notes = ($attendee['notes'] ?? null);
		$first_name = ($attendee['first-name'] ?? '');
		$last_name = ($attendee['last-name'] ?? '');
		$fandom_name = ($attendee['fandom-name'] ?? '');
		$name_on_badge = (($fandom_name && isset($attendee['name-on-badge'])) ? $attendee['name-on-badge'] : 'Real Name Only');
		$date_of_birth = ($attendee['date-of-birth'] ?? null);
		$subscribed = (isset($attendee['subscribed']) ? ($attendee['subscribed'] ? 1 : 0) : 0);
		$email_address = ($attendee['email-address'] ?? '');
		$phone_number = ($attendee['phone-number'] ?? '');
		$address_1 = ($attendee['address-1'] ?? '');
		$address_2 = ($attendee['address-2'] ?? '');
		$city = ($attendee['city'] ?? '');
		$state = ($attendee['state'] ?? '');
		$zip_code = ($attendee['zip-code'] ?? '');
		$country = ($attendee['country'] ?? '');
		$ice_name = ($attendee['ice-name'] ?? '');
		$ice_relationship = ($attendee['ice-relationship'] ?? '');
		$ice_email_address = ($attendee['ice-email-address'] ?? '');
		$ice_phone_number = ($attendee['ice-phone-number'] ?? '');
		$payment_status = ($attendee['payment-status'] ?? null);
		$payment_badge_price = ($attendee['payment-badge-price'] ?? null);
		$payment_promo_code = ($attendee['payment-promo-code'] ?? null);
		$payment_promo_price = ($attendee['payment-promo-price'] ?? null);
		$payment_group_uuid = ($attendee['payment-group-uuid'] ?? null);
		$payment_type = ($attendee['payment-type'] ?? null);
		$payment_txn_id = ($attendee['payment-txn-id'] ?? null);
		$payment_txn_amt = ($attendee['payment-txn-amt'] ?? null);
		$payment_date = ($attendee['payment-date'] ?? null);
		$payment_details = ($attendee['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendees').' SET '.
			'`date_modified` = NOW(), '.
			'`badge_type_id` = ?, `notes` = ?, `first_name` = ?, `last_name` = ?, '.
			'`fandom_name` = ?, `name_on_badge` = ?, `date_of_birth` = ?, '.
			'`subscribed` = ?, `email_address` = ?, `phone_number` = ?, '.
			'`address_1` = ?, `address_2` = ?, `city` = ?, `state` = ?, '.
			'`zip_code` = ?, `country` = ?, `ice_name` = ?, `ice_relationship` = ?, '.
			'`ice_email_address` = ?, `ice_phone_number` = ?, '.
			'`payment_status` = ?, `payment_badge_price` = ?, '.
			'`payment_promo_code` = ?, `payment_promo_price` = ?, '.
			'`payment_group_uuid` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'issssssisssssssssssssdsdsssdssi',
			$badge_type_id, $notes, $first_name, $last_name,
			$fandom_name, $name_on_badge, $date_of_birth,
			$subscribed, $email_address, $phone_number,
			$address_1, $address_2, $city, $state,
			$zip_code, $country, $ice_name, $ice_relationship,
			$ice_email_address, $ice_phone_number,
			$payment_status, $payment_badge_price,
			$payment_promo_code, $payment_promo_price,
			$payment_group_uuid, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details,
			$attendee['id']
		);
		$success = $stmt->execute();
		if (!$success)
		{
			error_log('Error while attempting to update attendee:\n' . print_r($this->cm_db->connection->error, true));
			error_log('Submitted data:\n' . print_r($attendee,true));
		}
		$stmt->close();
		if ($success) {
			if (isset($attendee['addons'])) {
				$this->delete_addon_purchases($attendee['id']);
				$this->create_addon_purchases($attendee['id'], $attendee['addons']);
			}
			if ($fdb && isset($attendee['form-answers'])) {
				$fdb->clear_answers($attendee['id']);
				$fdb->set_answers($attendee['id'], $attendee['form-answers']);
			}
			$attendee = $this->get_attendee($attendee['id']);
			$this->cm_ldb->remove_entity($attendee['id']);
			$this->cm_ldb->add_entity($attendee);
		}
		return $success;
	}

	public function delete_attendee($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('attendees').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		if ($success) {
			$this->delete_addon_purchases($id);
			$this->cm_ldb->remove_entity($id);
		}
		return $success;
	}

	public function update_attendee_notes($id, $newnote){
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendees').' SET '.
			'`notes` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('si', $newnote,  $id);
		$success = $stmt->execute();
		if (!$success)
		{
			error_log('Error while attempting to update attendee note:\n' . print_r($this->cm_db->connection->error, true));
			error_log('Submitted data:\n' . print_r(array('id' =>$id, 'notes'  => $newnote),true));
		}
		$stmt->close();
		return $success;
	}
	public function update_payment_status($id, $status, $type, $txn, $details) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendees').' SET '.
			'`payment_date` = case when  `payment_status` = \'Completed\' then `payment_date` when `payment_status` != \'Completed\' and ? = \'Completed\' then UTC_TIMESTAMP() else  `payment_date` end ,'.
			'`payment_status` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_details` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('sssssi', $status, $status, $type, $txn, $details,  $id);
		$success = $stmt->execute();
		if (!$success)
		{
			error_log('Error while attempting to update attendee payment status:\n' . print_r($this->cm_db->connection->error, true));
			error_log('Submitted data:\n' . print_r(array('id' =>$id, 'status' => $status,'type'=> $type, 'txn' => $txn, 'details'  => $details),true));
		}
		$stmt->close();
		if ($success) {
			$this->update_addon_purchase_payment_status($id, $status, $type, $txn, $details);
			$attendee = $this->get_attendee($id);
			$this->cm_ldb->remove_entity($id);
			$this->cm_ldb->add_entity($attendee);
		}
		return $success;
	}
	public function get_payment_status($id, &$status, &$type, &$txn, &$details) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'select '.
			'`payment_status`, `payment_type`, '.
			'`payment_txn_id`, `payment_details`'.
			'FROM '.$this->cm_db->table_name('attendees').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->bind_result(
			$status, $type, $txn, $details
		);
		$stmt->fetch();
		$stmt->close();
		return $success;
	}

	public function unsubscribe_email_address($email) {
		if (!$email) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendees').' SET '.
			'`subscribed` = FALSE WHERE LCASE(`email_address`) = LCASE(?)'
		);
		$stmt->bind_param('s', $email);
		$count = $stmt->execute() ? $this->cm_db->connection->affected_rows : false;
		$stmt->close();
		if ($count) {
			$ids = array();
			$stmt = $this->cm_db->connection->prepare(
				'SELECT `id` FROM '.$this->cm_db->table_name('attendees').
				' WHERE LCASE(`email_address`) = LCASE(?)'
			);
			$stmt->bind_param('s', $email);
			$stmt->execute();
			$stmt->bind_result($id);
			while ($stmt->fetch()) $ids[] = $id;
			$stmt->close();
			foreach ($ids as $id) {
				$attendee = $this->get_attendee($id);
				$this->cm_ldb->remove_entity($id);
				$this->cm_ldb->add_entity($attendee);
			}
		}
		return $count;
	}

	public function attendee_printed($id, $reset = false) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendees').' SET '.
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
			$attendee = $this->get_attendee($id);
			$this->cm_ldb->remove_entity($id);
			$this->cm_ldb->add_entity($attendee);
		}
		return $success;
	}

	public function attendee_checked_in($id, $reset = false) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('attendees').' SET '.
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
			$attendee = $this->get_attendee($id);
			$this->cm_ldb->remove_entity($id);
			$this->cm_ldb->add_entity($attendee);
		}
		return $success;
	}

	public function get_attendee_statistics($granularity = 300, $name_map = null) {
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
			' FROM '.$this->cm_db->table_name('attendees').
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
			' FROM '.$this->cm_db->table_name('attendees').
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
			' FROM '.$this->cm_db->table_name('attendees').
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
			' FROM '.$this->cm_db->table_name('attendees').
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
