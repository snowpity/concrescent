<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/mysqli_shim.php';

class cm_db {

	public mysqli $connection;

	public function __construct() {
		$config = $GLOBALS['cm_config']['database'];

		$this->connection = new mysqli(
			$config['host'], $config['username'],
			$config['password'], $config['database']
		);

		// Set the time zone
		$stmt = $this->connection->prepare('set time_zone = ?');
		$stmt->bind_param('s', $config['timezone']);
		$stmt->execute();
	}

	public function translate_query(string $query): string
	{
		$dbtype = $this->connection->getAttribute(PDO::ATTR_DRIVER_NAME);
		if($dbtype === 'mysql')
		{
			return $query;
		}
		return str_replace('`', '"', $query);
	}

	public function query(string $query): PDOStatement
	{
		return $this->connection->pdo->query($this->translate_query($query));
	}

	public function prepare(string $query): PDOStatement
	{
		return $this->connection->pdo->prepare($this->translate_query($query));
	}

	public function execute(string $query, ?array $params = null): PDOStatement
	{
		return $this->prepare($query)->execute($params);
	}

	// The stuff calling this needs to be moved elsewhere, perhaps a separate database-init page.
	// We shouldn't try to create the tables *every time a page is loaded*!
	public function table_def(string $table, string $def): void
	{
		$this->query("CREATE TABLE IF NOT EXISTS `$table` ($def)");
	}

	public function table_is_empty(string $table): bool
	{
		return 0 === $this->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
	}

	public function table_has_row(string $table, string $key, string $value): bool
	{
		return $this->execute("SELECT 1 FROM `$table` WHERE `$key` = ?", [$value])->fetchColumn();
	}

	public function now(): string
	{
		return $this->connection->query('SELECT NOW()')->fetchColumn();
	}

	public function uuid(): string
	{
		return $this->connection->query('SELECT UUID()')->fetchColumn();
	}

	public function curdatetime(): array
	{
		return $this->connection->query('SELECT CURDATE(), CURTIME()')->fetch(PDO::FETCH_NUM);
	}

	public function timezone(): array
	{
		return $this->connection->query('SELECT @@global.time_zone, @@session.time_zone')
			->fetch(PDO::FETCH_NUM);
	}

	public function characterset(): array
	{
		return $this->connection->query('SHOW VARIABLES LIKE \'character\\_set\\_%\'')
			->fetchAll(PDO::FETCH_KEY_PAIR);
	}

	public function last_insert_id(): string|false
	{
		// TODO (Mr. Metric): Check if other database engines we want compat with return a result the callers can use sanely.
		return $this->connection->pdo->lastInsertId();
	}

	public function affected_rows(): int
	{
		// TODO (Mr. Metric): The documentation says:
		// This method returns "0" (zero) with the SQLite driver at all times, and with the PostgreSQL driver only when setting the `PDO::ATTR_CURSOR` statement attribute to `PDO::CURSOR_SCROLL`.
		return $this->connection->pdo->rowCount();
	}
}
