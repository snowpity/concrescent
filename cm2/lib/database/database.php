<?php

require_once __DIR__ .'/../../config/config.php';

class cm_db {

	public mysqli $connection;
	public array $known_tables;

	public function __construct() {
		/* Load configuration */
		$config = $GLOBALS['cm_config']['database'];

		/* Connect to database */
		$this->connection = new mysqli(
			$config['host'], $config['username'],
			$config['password'], $config['database']
		);

		/* Set text encoding */
		$this->connection->set_charset('utf8mb4');

		/* Set time zone */
		$stmt = $this->connection->prepare('set time_zone = ?');
		$stmt->bind_param('s', $config['timezone']);
		$stmt->execute();
		$stmt->close();

		/* Load known tables */
		$this->known_tables = array();
		$stmt = $this->connection->prepare(
			'SELECT table_name '.
			'FROM information_schema.tables '.
			'WHERE table_schema = ?'
		);
		$stmt->bind_param('s', $config['database']);
		$stmt->execute();
		$stmt->bind_result($table);
		while ($stmt->fetch()) {
			$this->known_tables[$table] = true;
		}
		$stmt->close();
	}

	public function table_def($table, $def) {
		if (!isset($this->known_tables[$table])) {
			$this->known_tables[$table] = true;
			$this->connection->query(
				'CREATE TABLE IF NOT EXISTS '.
				'`' . $table . '` '.
				'(' . $def . ')'
			);
		}
	}

	public function table_is_empty($table): bool
	{
		$result = $this->connection->query("SELECT 1 FROM `$table` LIMIT 1");
		if ($result) {
			$is_empty = !$result->num_rows;
			$result->close();
			return $is_empty;
		} else {
			return true;
		}
	}

	public function now() {
		$result = $this->connection->query('SELECT NOW()');
		$row = $result->fetch_row();
		$now = $row[0];
		$result->close();
		return $now;
	}

	public function uuid() {
		$result = $this->connection->query('SELECT UUID()');
		$row = $result->fetch_row();
		$uuid = $row[0];
		$result->close();
		return $uuid;
	}

	public function curdatetime() {
		$result = $this->connection->query('SELECT CURDATE(), CURTIME()');
		$row = $result->fetch_row();
		$date = $row[0];
		$time = $row[1];
		$result->close();
		return array($date, $time);
	}

	public function timezone() {
		$result = $this->connection->query('SELECT @@global.time_zone, @@session.time_zone');
		$row = $result->fetch_row();
		$global = $row[0];
		$session = $row[1];
		$result->close();
		return array($global, $session);
	}

	public function characterset() {
		$results = array();
		$result = $this->connection->query('SHOW VARIABLES LIKE \'character\\_set\\_%\'');
		while ($row = $result->fetch_row()) {
			$results[$row[0]] = $row[1];
		}
		$result->close();
		return $results;
	}

}
