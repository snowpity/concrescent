<?php

namespace App\Lib\Database;

// This decorator class provides convenient MySQLi compatibility
use PDO;
use PDOStatement;

/**
 * @extends PDOStatement
 */
readonly class PDOStatement_wrap
{
	public function __construct(
        public PDOStatement $stmt
    ) {
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
    public function __isset($key)
    {
        return isset($this->stmt->$key);
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

		for($i = 0, $iMax = strlen($types); $i !== $iMax; ++$i) {
			$type = $type_map[$types[$i]];
			$r = $this->stmt->bindParam($i + 1, $vars[$i], $type);
			assert($r);
		}

		return true;
	}

	public function bind_result(mixed &...$vars): bool
	{
		$this->stmt->setFetchMode(PDO::FETCH_BOUND);
        foreach ($vars as $i => $iValue) {
            $r = $this->stmt->bindColumn($i + 1, $vars[$i]);
            assert($r);
        }
        return true;
	}
}
