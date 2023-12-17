<?php

require_once __DIR__ .'/../util/util.php';
require_once __DIR__ .'/../util/res.php';
require_once __DIR__ .'/database.php';

class cm_payment_db {

	public $payment_statuses = array(
		'Incomplete',
		'Cancelled',
		'Rejected',
		'Completed',
		'Refunded'
	);

	public $cm_db;

	public function __construct($cm_db) {
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('payments', (
			'`id` INTEGER NOT NULL AUTO_INCREMENT PRIMARY KEY,'.
			'`uuid` VARCHAR(255) NOT NULL UNIQUE KEY,'.
			'`date_created` DATETIME NOT NULL,'.
			'`date_modified` DATETIME NOT NULL,'.
			'`requested_by` VARCHAR(255) NOT NULL,'.
			'`first_name` VARCHAR(255) NOT NULL,'.
			'`last_name` VARCHAR(255) NOT NULL,'.
			'`email_address` VARCHAR(255) NOT NULL,'.
			'`mail_template` VARCHAR(255) NOT NULL,'.
			'`payment_name` VARCHAR(255) NOT NULL,'.
			'`payment_description` TEXT NULL,'.
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
	}

	public function get_payment($id, $uuid = null) {
		if (!$id && !$uuid) return false;
		$query = (
			'SELECT `id`, `uuid`, `date_created`, `date_modified`,'.
			' `requested_by`, `first_name`, `last_name`,'.
			' `email_address`, `mail_template`, `payment_name`,'.
			' `payment_description`, `payment_price`,'.
			' `payment_status`, `payment_type`,'.
			' `payment_txn_id`, `payment_txn_amt`,'.
			' `payment_date`, `payment_details`'.
			' FROM '.$this->cm_db->table_name('payments')
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
			$requested_by, $first_name, $last_name,
			$email_address, $mail_template, $payment_name,
			$payment_description, $payment_price,
			$payment_status, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		if ($stmt->fetch()) {
			$reg_url = get_site_url(true) . '/payment';
			$id_string = 'P' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=' . $qr_data;
			$real_name = trim(trim($first_name) . ' ' . trim($last_name));
			$payment_price_string = price_string($payment_price);
			$review_link = $reg_url . '/review.php?uid=' . $uuid;
			$search_content = array(
				$id, $uuid, $requested_by, $first_name, $last_name,
				$email_address, $mail_template, $payment_name,
				$payment_description, $payment_status, $payment_txn_id,
				$id_string, $qr_data, $real_name, $payment_price_string
			);
			$result = array(
				'id' => $id,
				'id-string' => $id_string,
				'uuid' => $uuid,
				'qr-data' => $qr_data,
				'qr-url' => $qr_url,
				'date-created' => $date_created,
				'date-modified' => $date_modified,
				'requested-by' => $requested_by,
				'first-name' => $first_name,
				'last-name' => $last_name,
				'real-name' => $real_name,
				'email-address' => $email_address,
				'mail-template' => $mail_template,
				'payment-name' => $payment_name,
				'payment-description' => $payment_description,
				'payment-price' => $payment_price,
				'payment-price-string' => $payment_price_string,
				'payment-status' => $payment_status,
				'payment-type' => $payment_type,
				'payment-txn-id' => $payment_txn_id,
				'payment-txn-amt' => $payment_txn_amt,
				'payment-date' => $payment_date,
				'payment-details' => $payment_details,
				'review-link' => $review_link,
				'search-content' => $search_content
			);
			$stmt->close();
			return $result;
		}
		$stmt->close();
		return false;
	}

	public function list_payments() {
		$payments = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `id`, `uuid`, `date_created`, `date_modified`,'.
			' `requested_by`, `first_name`, `last_name`,'.
			' `email_address`, `mail_template`, `payment_name`,'.
			' `payment_description`, `payment_price`,'.
			' `payment_status`, `payment_type`,'.
			' `payment_txn_id`, `payment_txn_amt`,'.
			' `payment_date`, `payment_details`'.
			' FROM '.$this->cm_db->table_name('payments').
			' ORDER BY `id`'
		);
		$stmt->execute();
		$stmt->bind_result(
			$id, $uuid, $date_created, $date_modified,
			$requested_by, $first_name, $last_name,
			$email_address, $mail_template, $payment_name,
			$payment_description, $payment_price,
			$payment_status, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$reg_url = get_site_url(true) . '/payment';
		$qr_base_url = resource_file_url('barcode.php', true) . '?s=qr&w=300&h=300&d=';
		while ($stmt->fetch()) {
			$id_string = 'P' . $id;
			$qr_data = 'CM*' . $id_string . '*' . strtoupper($uuid);
			$qr_url = $qr_base_url . $qr_data;
			$real_name = trim(trim($first_name) . ' ' . trim($last_name));
			$payment_price_string = price_string($payment_price);
			$review_link = $reg_url . '/review.php?uid=' . $uuid;
			$search_content = array(
				$id, $uuid, $requested_by, $first_name, $last_name,
				$email_address, $mail_template, $payment_name,
				$payment_description, $payment_status, $payment_txn_id,
				$id_string, $qr_data, $real_name, $payment_price_string
			);
			$payments[] = array(
				'id' => $id,
				'id-string' => $id_string,
				'uuid' => $uuid,
				'qr-data' => $qr_data,
				'qr-url' => $qr_url,
				'date-created' => $date_created,
				'date-modified' => $date_modified,
				'requested-by' => $requested_by,
				'first-name' => $first_name,
				'last-name' => $last_name,
				'real-name' => $real_name,
				'email-address' => $email_address,
				'mail-template' => $mail_template,
				'payment-name' => $payment_name,
				'payment-description' => $payment_description,
				'payment-price' => $payment_price,
				'payment-price-string' => $payment_price_string,
				'payment-status' => $payment_status,
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
		return $payments;
	}

	public function create_payment($payment) {
		if (!$payment) return false;
		$requested_by = ($payment['requested-by'] ?? '');
		$first_name = ($payment['first-name'] ?? '');
		$last_name = ($payment['last-name'] ?? '');
		$email_address = ($payment['email-address'] ?? '');
		$mail_template = ($payment['mail-template'] ?? '');
		$payment_name = ($payment['payment-name'] ?? '');
		$payment_description = ($payment['payment-description'] ?? null);
		$payment_price = ($payment['payment-price'] ?? null);
		$payment_status = ($payment['payment-status'] ?? null);
		$payment_type = ($payment['payment-type'] ?? null);
		$payment_txn_id = ($payment['payment-txn-id'] ?? null);
		$payment_txn_amt = ($payment['payment-txn-amt'] ?? null);
		$payment_date = ($payment['payment-date'] ?? null);
		$payment_details = ($payment['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO '.$this->cm_db->table_name('payments').' SET '.
			'`uuid` = UUID(), `date_created` = NOW(), `date_modified` = NOW(), '.
			'`requested_by` = ?, `first_name` = ?, `last_name` = ?, '.
			'`email_address` = ?, `mail_template` = ?, `payment_name` = ?, '.
			'`payment_description` = ?, `payment_price` = ?, '.
			'`payment_status` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'
		);
		$stmt->bind_param(
			'sssssssdsssdss',
			$requested_by, $first_name, $last_name,
			$email_address, $mail_template, $payment_name,
			$payment_description, $payment_price,
			$payment_status, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details
		);
		$id = $stmt->execute() ? $this->cm_db->connection->insert_id : false;
		$stmt->close();
		return $id;
	}

	public function update_payment($payment) {
		if (!$payment || !isset($payment['id']) || !$payment['id']) return false;
		$requested_by = ($payment['requested-by'] ?? '');
		$first_name = ($payment['first-name'] ?? '');
		$last_name = ($payment['last-name'] ?? '');
		$email_address = ($payment['email-address'] ?? '');
		$mail_template = ($payment['mail-template'] ?? '');
		$payment_name = ($payment['payment-name'] ?? '');
		$payment_description = ($payment['payment-description'] ?? null);
		$payment_price = ($payment['payment-price'] ?? null);
		$payment_status = ($payment['payment-status'] ?? null);
		$payment_type = ($payment['payment-type'] ?? null);
		$payment_txn_id = ($payment['payment-txn-id'] ?? null);
		$payment_txn_amt = ($payment['payment-txn-amt'] ?? null);
		$payment_date = ($payment['payment-date'] ?? null);
		$payment_details = ($payment['payment-details'] ?? null);
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('payments').' SET '.
			'`date_modified` = NOW(), '.
			'`requested_by` = ?, `first_name` = ?, `last_name` = ?, '.
			'`email_address` = ?, `mail_template` = ?, `payment_name` = ?, '.
			'`payment_description` = ?, `payment_price` = ?, '.
			'`payment_status` = ?, `payment_type` = ?, '.
			'`payment_txn_id` = ?, `payment_txn_amt` = ?, '.
			'`payment_date` = ?, `payment_details` = ?'.
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param(
			'sssssssdsssdssi',
			$requested_by, $first_name, $last_name,
			$email_address, $mail_template, $payment_name,
			$payment_description, $payment_price,
			$payment_status, $payment_type,
			$payment_txn_id, $payment_txn_amt,
			$payment_date, $payment_details,
			$payment['id']
		);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function delete_payment($id) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.$this->cm_db->table_name('payments').
			' WHERE `id` = ? LIMIT 1'
		);
		$stmt->bind_param('i', $id);
		$success = $stmt->execute();
		$stmt->close();
		return $success;
	}

	public function update_payment_status($id, $status, $type, $txn_id, $txn_amt, $date, $details) {
		if (!$id) return false;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE '.$this->cm_db->table_name('payments').' SET '.
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
		return $success;
	}

}
