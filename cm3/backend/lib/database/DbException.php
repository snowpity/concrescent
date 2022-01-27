<?php

namespace CM3_Lib\database;

class DbException extends \RuntimeException
{
    public function __construct($message, private ?array $data, private string $sql)
    {
        parent::__construct($message);
    }

    public function getData()
    {
        return $this->_data;
    }
    public function getSQL()
    {
        return $this->sql;
    }
}
