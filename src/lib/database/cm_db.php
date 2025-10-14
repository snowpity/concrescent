<?php

namespace App\Lib\Database;

use App\Config\Module\Database as DatabaseConfig;
use PDO;
use PDOStatement;

class cm_db {

	public PDO $connection;

	public function __construct(
        private readonly DatabaseConfig $config,
    ) {
		$host = $this->config->host;
		$dbname = $this->config->database;
		// The charset must be utf8mb4 for full Unicode® support
		$this->connection = new PDO(
			"mysql:host=$host;dbname=$dbname;charset=utf8mb4",
            $this->config->username, $this->config->password,
            [
                PDO::MYSQL_ATTR_INIT_COMMAND => 'set time_zone = "'. $this->config->timezone .'"',
            ]
		);
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
		return $this->connection->query($this->translate_query($query));
	}

    /**
     * @return PDOStatement_wrap&PDOStatement
     */
	public function prepare(string $query): PDOStatement_wrap
	{
		$stmt = $this->connection->prepare($this->translate_query($query));
		return new PDOStatement_wrap($stmt);
	}

    /**
     * @return PDOStatement_wrap&PDOStatement
     */
	public function execute(string $query, ?array $params = null): PDOStatement_wrap
	{
		$stmt = $this->prepare($query);
		$stmt->execute($params);
		return $stmt;
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
		return $this->connection->query('SELECT CURRENT_DATE, CURRENT_TIME')->fetch(PDO::FETCH_NUM);
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
		return $this->connection->lastInsertId();
	}
}
