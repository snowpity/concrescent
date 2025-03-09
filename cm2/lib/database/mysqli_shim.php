<?php

class mysqli_stmt
{
	public PDOStatement $pdo_stmt;

	public function __construct(PDOStatement $stmt)
	{
		$this->pdo_stmt = $stmt;
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
			$r = $this->pdo_stmt->bindParam($i + 1, $vars[$i], $type);
			assert($r);
		}

		return true;
	}

	public function execute(?array $params = null): bool
	{
		return $this->pdo_stmt->execute($params);
	}

	public function bind_result(mixed &...$vars): bool
	{
		for($i = 0; $i != count($vars); ++$i)
		{
			$r = $this->pdo_stmt->bindColumn($i + 1, $vars[$i]);
			assert($r);
		}
		return true;
	}

	public function fetch(): ?bool
	{
		return $this->pdo_stmt->fetch(PDO::FETCH_BOUND);
	}

	// vestigial
	public function close(): bool
	{
		return true;
	}
}

class mysqli_result
{
	public PDOStatement $pdo_stmt;

	public function __construct(PDOStatement $stmt)
	{
		$this->pdo_stmt = $stmt;
	}

	public function fetch_row(): array|false
	{
		return $this->pdo_stmt->fetch(PDO::FETCH_NUM);
	}

	// vestigial
	public function close(): void
	{
	}

	// drop-in PDO compat
	public function fetch(...$args): mixed
	{
		return $this->pdo_stmt->fetch(...$args);
	}
	public function fetchAll(...$args): array
	{
		return $this->pdo_stmt->fetchAll(...$args);
	}
	public function fetchColumn(int $column = 0): mixed
	{
		return $this->pdo_stmt->fetchColumn($column);
	}
}

class mysqli
{
	public PDO $pdo;

	public function __construct(string $host, string $username, string $password, string $dbname)
	{
		// The charset must be utf8mb4 for full Unicode® support
		$this->pdo = new PDO(
			"mysql:host=$host;dbname=$dbname;charset=utf8mb4",
			$username, $password
		);
	}

	public function character_set_name(): string
	{
		return $this->pdo->query('SELECT @@character_set_connection;')->fetchColumn();
	}

	// The second parameter, `int $result_mode`, is never used in cm2.
	public function query(string $query): mysqli_result|bool
	{
		$stmt = $this->pdo->query($query);
		if($stmt === false)
		{
			return false;
		}
		return new mysqli_result($stmt);
	}

	public function prepare(string $query): mysqli_stmt|false
	{
		$stmt = $this->pdo->prepare($query);
		if($stmt === false)
		{
			return false;
		}
		return new mysqli_stmt($stmt);
	}

	public function autocommit(bool $enable): bool
	{
		if($enable)
		{
			return $this->pdo->commit();
		}
		return $this->pdo->beginTransaction();
	}

	// drop-in PDO compat
	public function getAttribute(int $attribute): mixed
	{
		return $this->pdo->getAttribute($attribute);
	}
}
