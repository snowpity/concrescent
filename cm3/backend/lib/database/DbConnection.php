<?php

namespace CM3_Lib\database;

class DbConnection
{
    public $table_prefix;
    public $connection;
    public $known_tables; //Tables we know exist. bool -> If the schema has been validated

    public function __construct(array $db_config)
    {
        /* Load configuration */
        $this->table_prefix = $db_config['prefix'];

        /* Connect to database */
        $this->connection = new \mysqli(
            $db_config['host'],
            $db_config['username'],
            $db_config['password'],
            $db_config['database']
        );

        /* Set text encoding */
        $this->connection->set_charset('utf8mb4');

        /* Set time zone */
        if (!is_null($db_config['timezone'])) {
            $stmt = $this->connection->prepare('set time_zone = ?');
            $stmt->bind_param('s', $db_config['timezone']);
            $stmt->execute();
            $stmt->close();
        }

        //TODO: Hash table definition and compare with class data? Store check time to reduce extra overhead...
        /* Load known tables */
        $this->known_tables = array();
        $stmt = $this->connection->prepare(
            'SELECT table_name '.
            'FROM information_schema.tables '.
            'WHERE table_schema = ?'
        );
        $stmt->bind_param('s', $db_config['database']);
        $stmt->execute();
        $stmt->bind_result($table);
        while ($stmt->fetch()) {
            $this->known_tables[$table] = false;
        }
        $stmt->close();
    }

    public function table_name($table)
    {
        return '`' . $this->table_prefix . $table . '`';
    }

    public function table_is_empty($table)
    {
        $tn = $this->table_name($table);
        $result = $this->connection->query('SELECT 1 FROM ' . $tn . ' LIMIT 1');
        if ($result) {
            $is_empty = !$result->num_rows;
            $result->close();
            return $is_empty;
        } else {
            return true;
        }
    }

    public function now()
    {
        $result = $this->connection->query('SELECT NOW()');
        $row = $result->fetch_row();
        $now = $row[0];
        $result->close();
        return $now;
    }

    public function uuid()
    {
        $result = $this->connection->query('SELECT UUID()');
        $row = $result->fetch_row();
        $uuid = $row[0];
        $result->close();
        return $uuid;
    }

    public function curdatetime()
    {
        $result = $this->connection->query('SELECT CURDATE(), CURTIME()');
        $row = $result->fetch_row();
        $date = $row[0];
        $time = $row[1];
        $result->close();
        return array($date, $time);
    }

    public function timezone()
    {
        $result = $this->connection->query('SELECT @@global.time_zone, @@session.time_zone');
        $row = $result->fetch_row();
        $global = $row[0];
        $session = $row[1];
        $result->close();
        return array($global, $session);
    }

    public function characterset()
    {
        $results = array();
        $result = $this->connection->query('SHOW VARIABLES LIKE \'character\\_set\\_%\'');
        while ($row = $result->fetch_row()) {
            $results[$row[0]] = $row[1];
        }
        $result->close();
        return $results;
    }
}
