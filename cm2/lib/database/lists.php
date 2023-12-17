<?php

require_once __DIR__ .'/database.php';

class cm_lists_db {

	public $index_table_name;
	public $cm_db;

	public function __construct($cm_db, $index_table_name) {
		$this->index_table_name = $index_table_name;
		$this->cm_db = $cm_db;
		$this->cm_db->table_def($this->index_table_name, (
			'`id` INTEGER NOT NULL,'.
			'`key` VARCHAR(255) NOT NULL,'.
			'`value` TEXT NOT NULL,'.
			'PRIMARY KEY (`id`, `key`),'.
			'INDEX (`value`(255))'
		));
	}

	public function normalize_array($array) {
		if (isset($array['search-content'])) {
			return $this->normalize_array($array['search-content']);
		} else {
			return @implode("\n", $array);
		}
	}

	public function normalize_key($key) {
		return strtolower(str_replace('_', '-', $key));
	}

	public function normalize_value($value) {
		return preg_replace_callback(
			'/([.]?)([0-9]+)/',
			function($m) {
				if ($m[1]) {
					return substr($m[0].'00000000000000000000', 0, 21);
				} else {
					return substr('00000000000000000000'.$m[0], -20);
				}
			},
			strtolower($value)
		);
	}

	public function add_value($id, $key, $value) {
		if (!$id || !$value) return;
		if (is_array($value)) {
			$this->add_value($id, $key, $this->normalize_array($value));
			foreach ($value as $k => $v) {
				$this->add_value($id, ($key ? ($key.':'.$k) : $k), $v);
			}
		} else {
			$key = $this->normalize_key($key);
			$value = $this->normalize_value($value);
			$stmt = $this->cm_db->connection->prepare(
				'INSERT INTO '.
				$this->cm_db->table_name($this->index_table_name).
				' SET `id` = ?, `key` = ?, `value` = ?'
			);
			$stmt->bind_param('iss', $id, $key, $value);
			$stmt->execute();
			$stmt->close();
		}
	}

	public function add_entity($entity) {
		$this->add_value($entity['id'], '', $entity);
	}

	public function remove_entity($id) {
		if (!$id) return;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM '.
			$this->cm_db->table_name($this->index_table_name).
			' WHERE `id` = ?'
		);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$stmt->close();
	}

	public function drop_index() {
		$this->cm_db->connection->query(
			'TRUNCATE TABLE '.
			$this->cm_db->table_name($this->index_table_name)
		);
	}

	private $sql_expr_id = 0;

	public function list_query_op_to_sql($key, $op, $value) {
		$exid = 'e' . $this->sql_expr_id++;
		$key = $this->normalize_key($key);
		$value = $this->normalize_value($value);
		switch ($op) {
			case ':':
				$value = str_replace('\\', '\\\\', $value);
				$value = str_replace('%', '\\%', $value);
				$value = str_replace('_', '\\_', $value);
				$value = '%' . $value . '%';
				$sqlquery = (
					'(EXISTS (SELECT 1 FROM '.
					$this->cm_db->table_name($this->index_table_name).' '.$exid.
					' WHERE '.$exid.'.`id` = i.`id`'.
					' AND '.$exid.'.`key` = ?'.
					' AND '.$exid.'.`value` LIKE ?'.
					' LIMIT 1))'
				);
				$bindtype = 'ss';
				$bindvalue = array($key, $value);
				break;
			case '<': case '>': case '<=': case '>=': case '=':
				$sqlquery = (
					'(EXISTS (SELECT 1 FROM '.
					$this->cm_db->table_name($this->index_table_name).' '.$exid.
					' WHERE '.$exid.'.`id` = i.`id`'.
					' AND '.$exid.'.`key` = ?'.
					' AND '.$exid.'.`value` '.$op.' ?'.
					' LIMIT 1))'
				);
				$bindtype = 'ss';
				$bindvalue = array($key, $value);
				break;
			default:
				$sqlquery = 'FALSE';
				$bindtype = '';
				$bindvalue = array();
				break;
		}
		return array($sqlquery, $bindtype, $bindvalue);
	}

	public function list_query_to_sql($listquery, $key = '', $op = ':') {
		if (!$listquery) {
			$sqlquery = 'TRUE';
			$bindtype = '';
			$bindvalue = array();
		} else if ($listquery[0] == '"') {
			$sql = $this->list_query_op_to_sql($key, $op, $listquery[1]);
			$sqlquery = $sql[0];
			$bindtype = $sql[1];
			$bindvalue = $sql[2];
		} else if ($listquery[0] == '-') {
			$sql = $this->list_query_to_sql($listquery[1], $key, $op);
			$sqlquery = '(NOT ' . $sql[0] . ')';
			$bindtype = $sql[1];
			$bindvalue = $sql[2];
		} else if ($listquery[0] == '&') {
			$sqlquery = array();
			$bindtype = '';
			$bindvalue = array();
			for ($i = 1, $n = count($listquery); $i < $n; $i++) {
				$sql = $this->list_query_to_sql($listquery[$i], $key, $op);
				$sqlquery[] = $sql[0];
				$bindtype .= $sql[1];
				$bindvalue = array_merge($bindvalue, $sql[2]);
			}
			$sqlquery = '(' . implode(' AND ', $sqlquery) . ')';
		} else if ($listquery[0] == '|') {
			$sqlquery = array();
			$bindtype = '';
			$bindvalue = array();
			for ($i = 1, $n = count($listquery); $i < $n; $i++) {
				$sql = $this->list_query_to_sql($listquery[$i], $key, $op);
				$sqlquery[] = $sql[0];
				$bindtype .= $sql[1];
				$bindvalue = array_merge($bindvalue, $sql[2]);
			}
			$sqlquery = '(' . implode(' OR ', $sqlquery) . ')';
		} else {
			$sql = $this->list_query_to_sql(
				$listquery[2],
				($key ? ($key.':'.$listquery[1]) : $listquery[1]),
				$listquery[0]
			);
			$sqlquery = $sql[0];
			$bindtype = $sql[1];
			$bindvalue = $sql[2];
		}
		return array($sqlquery, $bindtype, $bindvalue);
	}

	public function sort_order_to_sql(&$list_def, $sort_order) {
		$select = array();
		$bindtype = '';
		$bindvalue = array();
		$orderby = array();
		if ($sort_order) {
			foreach ($sort_order as $i => $column_index) {
				$descending = ($column_index < 0);
				if ($descending) $column_index = ~$column_index;
				$column = $list_def['columns'][$column_index];
				$key = $column['key'];
				$select[] = (
					'(SELECT i'.$i.'.`value`'.
					' FROM '.$this->cm_db->table_name($this->index_table_name).' i'.$i.
					' WHERE i'.$i.'.`id` = i.`id`'.
					' AND i'.$i.'.`key` = ?) o'.$i
				);
				$bindtype .= 's';
				$bindvalue[] = $key;
				$orderby[] = 'o'.$i.($descending ? ' DESC' : ' ASC');
			}
			$orderby = array_reverse($orderby);
		}
		return array($select, $bindtype, $bindvalue, $orderby);
	}

	public function construct_sql(&$list_def, $query, $sort_order) {
		list($query_sqlquery, $query_bindtype, $query_bindvalue) = $this->list_query_to_sql($query);
		list($order_select, $order_bindtype, $order_bindvalue, $order_orderby) = $this->sort_order_to_sql($list_def, $sort_order);
		$sqlquery = 'SELECT DISTINCT i.`id`';
		$bindtype = '';
		$bindvalue = array();
		if ($order_select) {
			$sqlquery .= ', ' . implode(', ', $order_select);
			$bindtype .= $order_bindtype;
			$bindvalue = array_merge($bindvalue, $order_bindvalue);
		}
		$sqlquery .= ' FROM ' . $this->cm_db->table_name($this->index_table_name) . ' i';
		if ($query_sqlquery) {
			$sqlquery .= ' WHERE i.`key` = \'\' AND ' . $query_sqlquery;
			$bindtype .= $query_bindtype;
			$bindvalue = array_merge($bindvalue, $query_bindvalue);
		}
		if ($order_orderby) {
			$sqlquery .= ' ORDER BY ' . implode(', ', $order_orderby);
		}
		return array($sqlquery, $bindtype, $bindvalue);
	}

	public function list_indexes(&$list_def, $query = null, $sort_order = null, $offset = null, $length = null) {
		if (is_null($query)) $query = json_decode($_POST['cm-list-search-query'], true);
		if (is_null($sort_order)) $sort_order = json_decode($_POST['cm-list-sort-order'], true);
		if (is_null($offset)) $offset = (int)$_POST['cm-list-page-offset'];
		if (is_null($length)) $length = (int)$_POST['cm-list-page-length'];

		list($sqlquery, $bindtype, $bindvalue) = $this->construct_sql($list_def, $query, $sort_order);
		$stmt = $this->cm_db->connection->prepare($sqlquery);

		$bindparam = array($bindtype);
		if ($bindvalue) {
			for ($i = 0, $n = count($bindvalue); $i < $n; $i++) {
				$bindparam[] = &$bindvalue[$i];
			}
		}
		call_user_func_array(array($stmt, 'bind_param'), $bindparam);

		$stmt->execute();

		$id = null;
		$x = array();
		$bindresult = array(&$id);
		if ($sort_order) {
			for ($i = 0, $n = count($sort_order); $i < $n; $i++) {
				$bindresult[] = &$x[$i];
			}
		}
		call_user_func_array(array($stmt, 'bind_result'), $bindresult);

		$ids = array();
		while ($stmt->fetch()) $ids[] = $id;
		$stmt->close();

		$match_count = count($ids);
		if ($length) {
			$ids = array_slice($ids, $offset, $length);
		}
		return array(
			'ok' => true,
			'ids' => $ids,
			'match-count' => $match_count,
			'sql-query' => $sqlquery
		);
	}

}
