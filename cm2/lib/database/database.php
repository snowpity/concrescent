<?php

require_once __DIR__ .'/../../config/config.php';

// This decorator class provides convenient MySQLi compatibility
class PDOStatement_wrap
{
	public PDOStatement $stmt;

	public function __construct(PDOStatement $stmt)
	{
		$this->stmt = $stmt;
	}

	public function __call($method, $args)
	{
		return call_user_func_array([$this->stmt, $method], $args);
	}
	public function __get($key)
	{
		return $this->stmt->$key;
	}
	public function __set($key, $val)
	{
		return $this->stmt->$key = $val;
	}

	public function bind_param(string $types, &...$vars): bool
	{
		assert(strlen($types) === count($vars));

		$type_map = [
			'i' => PDO::PARAM_INT,
			'd' => PDO::PARAM_STR, // `double` ain't supported by PDO :[
			's' => PDO::PARAM_STR,
			'b' => PDO::PARAM_LOB,
		];

		for($i = 0; $i !== strlen($types); ++$i)
		{
			$type = $type_map[$types[$i]];
			$r = $this->stmt->bindParam($i + 1, $vars[$i], $type);
			assert($r);
		}

		return true;
	}

	public function bind_result(mixed &...$vars): bool
	{
		$this->stmt->setFetchMode(PDO::FETCH_BOUND);
		for($i = 0; $i !== count($vars); ++$i)
		{
			$r = $this->stmt->bindColumn($i + 1, $vars[$i]);
			assert($r);
		}
		return true;
	}
}

class cm_db {

	public PDO $connection;

	public function __construct() {
		$config = $GLOBALS['cm_config']['database'];

		$host = $config['host'];
		$dbname = $config['database'];
		// The charset must be utf8mb4 for full Unicode® support
		$this->connection = new PDO(
			"mysql:host=$host;dbname=$dbname;charset=utf8mb4",
			$config['username'], $config['password']
		);

		// Set the time zone
		$this->connection->prepare('set time_zone = ?')->execute([$config['timezone']]);
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

	public function prepare(string $query): PDOStatement_wrap
	{
		$stmt = $this->connection->prepare($this->translate_query($query));
		return new PDOStatement_wrap($stmt);
	}

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
		return $this->connection->lastInsertId();
	}

	public function affected_rows(): int
	{
		// TODO (Mr. Metric): The documentation says:
		// This method returns "0" (zero) with the SQLite driver at all times, and with the PostgreSQL driver only when setting the `PDO::ATTR_CURSOR` statement attribute to `PDO::CURSOR_SCROLL`.
		return $this->connection->rowCount();
	}
}
