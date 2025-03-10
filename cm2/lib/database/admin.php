<?php

require_once __DIR__ .'/../../config/config.php';
require_once __DIR__ .'/database.php';

class cm_admin_db {

	public cm_db $cm_db;

	public function __construct(cm_db $cm_db) {
		$this->cm_db = $cm_db;
		$this->cm_db->table_def('admin_users', (
			'`name` VARCHAR(255) NOT NULL,'.
			'`username` VARCHAR(255) NOT NULL PRIMARY KEY,'.
			'`password` VARCHAR(255) NOT NULL,'.
			'`active` BOOLEAN NOT NULL,'.
			'`permissions` TEXT NOT NULL'
		));
		$this->cm_db->table_def('admin_access_log', (
			'`timestamp` DATETIME NOT NULL,'.
			'`username` VARCHAR(255) NOT NULL,'.
			'`remote_addr` VARCHAR(255) NOT NULL,'.
			'`remote_host` VARCHAR(255) NOT NULL,'.
			'`request_method` VARCHAR(255) NOT NULL,'.
			'`request_uri` VARCHAR(255) NOT NULL,'.
			'`http_referer` VARCHAR(255) NOT NULL,'.
			'`http_user_agent` VARCHAR(255) NOT NULL'
		));
		if ($this->cm_db->table_is_empty('admin_users')) {
			$config = $GLOBALS['cm_config']['default_admin'];
			if ($config['name'] && $config['username'] && $config['password']) {
				$password = password_hash($config['password'], PASSWORD_DEFAULT);
				$active = 1;
				$permissions = '*';
				$stmt = $this->cm_db->connection->prepare(
					'INSERT INTO `admin_users` SET '.
					'`name` = ?, `username` = ?, `password` = ?, `active` = ?, `permissions` = ?'
				);
				$stmt->bind_param(
					'sssis',
					$config['name'],
					$config['username'],
					$password,
					$active,
					$permissions
				);
				$stmt->execute();
			}
		}
	}

	public function logged_in_user() {
		$username = isset($_SESSION['admin_username']);
		$password = isset($_SESSION['admin_password']);
		if (!$username || !$password) return false;
		$username = $_SESSION['admin_username'];
		$password = $_SESSION['admin_password'];
		if (!$username || !$password) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `name`, `username`, `password`, `permissions`'.
			' FROM `admin_users`' .
			' WHERE `username` = ? AND `active` LIMIT 1'
		);
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$stmt->bind_result($name, $username, $hash, $permissions);
		if ($stmt->fetch()) {
			if (password_verify($password, $hash)) {
				return [
					'name' => $name,
					'username' => $username,
					'permissions' => explode(',', $permissions)
				];
			}
		}
		return false;
	}

	public function log_in($username, $password) {
		$_SESSION['admin_username'] = $username;
		$_SESSION['admin_password'] = $password;
		return $this->logged_in_user();
	}

	public function log_out() {
		unset($_SESSION['admin_username']);
		unset($_SESSION['admin_password']);
		session_destroy();
	}

	public function log_access() {
		$username = $_SESSION['admin_username'] ?? '';
		$remote_addr = $_SERVER['REMOTE_ADDR'] ?? '';
		$remote_host = $_SERVER['REMOTE_HOST'] ?? '';
		$request_method = $_SERVER['REQUEST_METHOD'] ?? '';
		$request_uri = $_SERVER['REQUEST_URI'] ?? '';
		$http_referer = $_SERVER['HTTP_REFERER'] ?? '';
		$http_user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO `admin_access_log` SET '.
			'`timestamp` = NOW(), `username` = ?, '.
			'`remote_addr` = ?, `remote_host` = ?, '.
			'`request_method` = ?, `request_uri` = ?, '.
			'`http_referer` = ?, `http_user_agent` = ?'
		);
		$stmt->bind_param(
			'sssssss',
			$username, $remote_addr, $remote_host,
			$request_method, $request_uri,
			$http_referer, $http_user_agent
		);
		$success = $stmt->execute();
		return $success;
	}

	public function user_has_permission($user, $permission) {
		if (is_array($permission)) {
			switch ($permission[0]) {
				case '|': case '||':
					for ($i = 1, $n = count($permission); $i < $n; $i++) {
						if ($this->user_has_permission($user, $permission[$i])) {
							return true;
						}
					}
					return false;
				case '!': case '!!':
					for ($i = 1, $n = count($permission); $i < $n; $i++) {
						if ($this->user_has_permission($user, $permission[$i])) {
							return false;
						}
					}
					return true;
				case '&': case '&&':
					for ($i = 1, $n = count($permission); $i < $n; $i++) {
						if (!$this->user_has_permission($user, $permission[$i])) {
							return false;
						}
					}
					return true;
				default:
					return false;
			}
		} else {
			return ($user && $user['permissions'] && (
				in_array('*', $user['permissions']) ||
				in_array($permission, $user['permissions'])
			));
		}
	}

	public function get_user($username) {
		if (!$username) return false;
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `name`, `username`, `active`, `permissions`'.
			' FROM `admin_users`' .
			' WHERE `username` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $username);
		$stmt->execute();
		$stmt->bind_result($name, $username, $active, $permissions);
		if ($stmt->fetch()) {
			return [
				'name' => $name,
				'username' => $username,
				'active' => !!$active,
				'permissions' => ($permissions ? explode(',', $permissions) : array()),
				'search-content' => array($name, $username)
			];
		}
		return false;
	}

	public function list_users() {
		$users = array();
		$stmt = $this->cm_db->connection->prepare(
			'SELECT `name`, `username`, `active`, `permissions`'.
			' FROM `admin_users`' .
			' ORDER BY `name`'
		);
		$stmt->execute();
		$stmt->bind_result($name, $username, $active, $permissions);
		while ($stmt->fetch()) {
			$users[] = array(
				'name' => $name,
				'username' => $username,
				'active' => !!$active,
				'permissions' => ($permissions ? explode(',', $permissions) : array()),
				'search-content' => array($name, $username)
			);
		}
		return $users;
	}

	public function create_user($user) {
		if (!$user) return false;
		if (!isset($user['username']) || !$user['username']) return false;
		if (!isset($user['password']) || !$user['password']) return false;
		/* Get field values */
		$name = $user['name'] ?? '';
		$username = $user['username'];
		$password = password_hash($user['password'], PASSWORD_DEFAULT);
		$active = (isset($user['active']) ? ($user['active'] ? 1 : 0) : 1);
		$permissions = (
			(isset($user['permissions']) && $user['permissions']) ?
			implode(',', $user['permissions']) : ''
		);
		/* Create and execute query */
		$stmt = $this->cm_db->connection->prepare(
			'INSERT INTO `admin_users` SET '.
			'`name` = ?, `username` = ?, `password` = ?, `active` = ?, `permissions` = ?'
		);
		$stmt->bind_param(
			'sssis',
			$name,
			$username,
			$password,
			$active,
			$permissions
		);
		$success = $stmt->execute();
		return $success;
	}

	public function update_user($username, $user) {
		if (!$username || !$user) return false;
		/* Get field values */
		$new_password = '';
		$new_active = 1;
		$new_permissions = '';
		$query_params = array();
		$bind_params = array('');
		if (isset($user['name']) && $user['name']) {
			$query_params[] = '`name` = ?';
			$bind_params[0] .= 's';
			$bind_params[] = &$user['name'];
		}
		if (isset($user['username']) && $user['username']) {
			$query_params[] = '`username` = ?';
			$bind_params[0] .= 's';
			$bind_params[] = &$user['username'];
		}
		if (isset($user['password']) && $user['password']) {
			$new_password = password_hash($user['password'], PASSWORD_DEFAULT);
			$query_params[] = '`password` = ?';
			$bind_params[0] .= 's';
			$bind_params[] = &$new_password;
		}
		if (isset($user['active'])) {
			$new_active = ($user['active'] ? 1 : 0);
			$query_params[] = '`active` = ?';
			$bind_params[0] .= 'i';
			$bind_params[] = &$new_active;
		}
		if (isset($user['permissions']) && $user['permissions']) {
			$new_permissions = implode(',', $user['permissions']);
			$query_params[] = '`permissions` = ?';
			$bind_params[0] .= 's';
			$bind_params[] = &$new_permissions;
		}
		$bind_params[0] .= 's';
		$bind_params[] = &$username;
		/* Create and execute query */
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE `admin_users` SET '.
			implode(', ', $query_params).' WHERE `username` = ? LIMIT 1'
		);
		call_user_func_array(array($stmt, 'bind_param'), $bind_params);
		$success = $stmt->execute();
		return $success;
	}

	public function delete_user($username) {
		if (!$username) return false;
		$stmt = $this->cm_db->connection->prepare(
			'DELETE FROM `admin_users`' .
			' WHERE `username` = ? LIMIT 1'
		);
		$stmt->bind_param('s', $username);
		$success = $stmt->execute();
		return $success;
	}

	public function activate_user($username, $active) {
		if (!$username) return false;
		$active = $active ? 1 : 0;
		$stmt = $this->cm_db->connection->prepare(
			'UPDATE `admin_users`' .
			' SET `active` = ? WHERE `username` = ? LIMIT 1'
		);
		$stmt->bind_param('is', $active, $username);
		$success = $stmt->execute();
		return $success;
	}
}
