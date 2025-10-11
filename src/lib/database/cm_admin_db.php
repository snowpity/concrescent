<?php

namespace App\Lib\Database;

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
				$stmt = $this->cm_db->prepare(
					'INSERT INTO `admin_users` (`name`, `username`, `password`, `active`, `permissions`) VALUES (:name, :username, :password, :active, :permissions)'
				);
				$stmt->execute([
					':name' => $config['name'],
					':username' => $config['username'],
					':password' => $password,
					':active' => 1,
					':permissions' => '*'
				]);
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
		$stmt = $this->cm_db->prepare(
			'SELECT `name`, `username`, `password`, `permissions`'.
			' FROM `admin_users`' .
			' WHERE `username` = :username AND `active` = 1 LIMIT 1'
		);
		$stmt->execute([':username' => $username]);
		$user = $stmt->fetch();

		if ($user && password_verify($password, $user['password'])) {
			return [
				'name' => $user['name'],
				'username' => $user['username'],
				'permissions' => explode(',', $user['permissions'])
			];
		}

		return false;
	}

	public function log_in($username, $password): false|array
    {
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
		$stmt = $this->cm_db->prepare(
			'INSERT INTO `admin_access_log` (`timestamp`, `username`, `remote_addr`, `remote_host`, `request_method`, `request_uri`, `http_referer`, `http_user_agent`) '.
			'VALUES (CURRENT_TIMESTAMP, :username, :remote_addr, :remote_host, :request_method, :request_uri, :http_referer, :http_user_agent)'
		);
		return $stmt->execute([
			':username' => $_SESSION['admin_username'] ?? '',
			':remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '',
			':remote_host' => $_SERVER['REMOTE_HOST'] ?? '',
			':request_method' => $_SERVER['REQUEST_METHOD'] ?? '',
			':request_uri' => $_SERVER['REQUEST_URI'] ?? '',
			':http_referer' => $_SERVER['HTTP_REFERER'] ?? '',
			':http_user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
		]);
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
		$stmt = $this->cm_db->prepare(
			'SELECT `name`, `username`, `active`, `permissions` FROM `admin_users`  WHERE `username` = :username LIMIT 1'
		);
		$stmt->execute([':username' => $username]);
		$user = $stmt->fetch();
		if ($user) {
			return [
				'name' => $user['name'],
				'username' => $user['username'],
				'active' => (bool)$user['active'],
				'permissions' => ($user['permissions'] ? explode(',', $user['permissions']) : []),
				'search-content' => array($user['name'], $user['username'])
			];
		}
		return false;
	}

	public function list_users()
	{
		$stmt = $this->cm_db->query(
            'SELECT `name`, `username`, `active`, `permissions` FROM `admin_users` ORDER BY `name`'
        );
		$users = $stmt->fetchAll();

		return array_map(function ($user) {
			return [
				'name' => $user['name'],
				'username' => $user['username'],
				'active' => (bool)$user['active'],
				'permissions' => !empty($user['permissions']) ? explode(',', $user['permissions']) : [],
				'search-content' => [$user['name'], $user['username']],
			];
		}, $users);
	}

	public function create_user($user)
	{
		if (empty($user) || empty($user['username']) || empty($user['password'])) {
			return false;
		}

		$stmt = $this->cm_db->prepare(
			'INSERT INTO `admin_users` (`name`, `username`, `password`, `active`, `permissions`) VALUES (:name, :username, :password, :active, :permissions)'
		);

		return $stmt->execute([
			':name' => $user['name'] ?? '',
			':username' => $user['username'],
			':password' => password_hash($user['password'], PASSWORD_DEFAULT),
			':active' => (int)($user['active'] ?? true),
			':permissions' => !empty($user['permissions']) ? implode(',', $user['permissions']) : '',
		]);
	}

	public function update_user($username, $user) {
		if (!$username || !$user) return false;

		$query_params = [];
		$params = [':username_where' => $username];

		if (isset($user['name'])) {
			$query_params[] = '`name` = :name';
			$params[':name'] = $user['name'];
		}
		if (!empty($user['username'])) {
			$query_params[] = '`username` = :username';
			$params[':username'] = $user['username'];
		}
		if (!empty($user['password'])) {
			$query_params[] = '`password` = :password';
			$params[':password'] = password_hash($user['password'], PASSWORD_DEFAULT);
		}
		if (isset($user['active'])) {
			$query_params[] = '`active` = :active';
			$params[':active'] = (int)(bool)$user['active'];
		}
		if (isset($user['permissions'])) {
			$query_params[] = '`permissions` = :permissions';
			$params[':permissions'] = implode(',', $user['permissions']);
		}

		if (empty($query_params)) {
			return true;
		}

        $querylist = implode(', ', $query_params);
		$stmt = $this->cm_db->prepare(
			"UPDATE `admin_users` SET $querylist WHERE `username` = :username_where LIMIT 1"
		);

		return $stmt->execute($params);
	}


	public function delete_user($username) {
		if (empty($username)) {
			return false;
		}
		$stmt = $this->cm_db->prepare(
			'DELETE FROM `admin_users` WHERE `username` = :username LIMIT 1'
		);
		return $stmt->execute([':username' => $username]);
	}

	public function activate_user($username, $active) {
		if (empty($username)) {
			return false;
		}
		$stmt = $this->cm_db->prepare(
			'UPDATE `admin_users` SET `active` = :active WHERE `username` = :username LIMIT 1'
		);
		return $stmt->execute([
			':active' => (int)(bool)$active,
			':username' => $username
		]);
	}
}
