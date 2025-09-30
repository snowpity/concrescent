<?php

namespace App\Lib\Database;

// This decorator class provides convenient MySQLi compatibility
use PDO;
use PDOStatement;

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
