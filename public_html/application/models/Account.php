<?php

namespace application\models;

use application\core\Model;


// Модель для работы с аккаунтом
class Account extends Model {

    // проверка на валидацию данных
	public function validate($input, $post) {

	    // паттерны для регулярных выражений и ошибки
		$rules = [
			'email' => [
				'pattern' => '#^([a-z0-9_.-]{1,20}+)@([a-z0-9_.-]+)\.([a-z\.]{2,10})$#',
				'message' => 'E-mail адрес указан неверно',
			],
			'login' => [
				'pattern' => '#^[a-z0-9]{3,15}$#',
				'message' => 'Логин указан неверно (разрешены только латинские буквы и цифры от 3 до 15 символов',
			],
			'ref' => [
				'pattern' => '#^[a-z0-9]{3,15}$#',
				'message' => 'Логин пригласившего указан неверно',
			],
			'wallet' => [
				'pattern' => '#^[A-z0-9]{3,15}$#',
				'message' => 'Кошелек Fitlent Money указан неверно',
			],
			'password' => [
				'pattern' => '#^[a-z0-9]{10,20}$#',
				'message' => 'Пароль указан неверно (разрешены только латинские буквы и цифры от 10 до 20 символов',

			],
		];

        // проверяем данные используя заготовленные паттерны для регулярок
		foreach ($input as $val) {
			if (!isset($post[$val]) or !preg_match($rules[$val]['pattern'], $post[$val])) {
				$this->error = $rules[$val]['message'];
				return false;
			}
		}

		// если наш логин совпадает с логином реферала
		if (isset($post['ref'])) {
			if ($post['login'] == $post['ref']) {
				$this->error = 'Регистрация невозможна';
				return false;
			}
		}

		return true;
	}


    // проверка на существование такой почты
	public function checkEmailExists($email) {
		$params = [
			'email' => $email,
		];
		return $this->db->column('SELECT id FROM accounts WHERE email = :email', $params);
	}


    // проверка на существование такого логина
	public function checkLoginExists($login) {
		$params = [
			'login' => $login,
		];
		if ($this->db->column('SELECT id FROM accounts WHERE login = :login', $params)) {
			$this->error = 'Этот логин уже используется';
			return false;
		}
		return true;
	}

	// проверка на существования токена
	public function checkTokenExists($token) {
		$params = [
			'token' => $token,
		];
		return $this->db->column('SELECT id FROM accounts WHERE token = :token', $params);
	}

	// обновляем токен, чтобы аккаунт стал подтвержденным
	public function activate($token) {
		$params = [
			'token' => $token,
		];
		$this->db->query('UPDATE accounts SET status = 1, token = "" WHERE token = :token', $params);
	}

	// возвращает id пользователя по его логину для реферала
	public function checkRefExists($login) {
		$params = [
			'login' => $login,
		];
		return $this->db->column('SELECT id FROM accounts WHERE login = :login', $params);
	}

	// рандомно создаем токен для аккаунта
	public function createToken() {
		return substr(str_shuffle(str_repeat('0123456789abcdefghijklmnopqrstuvwxyz', 30)), 0, 30);
	}

	public function register($post) {

		$token = $this->createToken();

		// если есть реферал, то проверяем на его существование
		if ($post['ref'] == 'none') {
			$ref = 0;
		} else {
		    // id реферала
			$ref = $this->checkRefExists($post['ref']);
			if (!$ref) {
				$ref = 0;
			}
		}

		$params = [
			'id' => NULL,
			'email' => $post['email'],
			'login' => $post['login'],
			'wallet' => $post['wallet'],
			'password' => password_hash($post['password'], PASSWORD_BCRYPT),
			'ref' => $ref,
			'refBalance' => 0,
			'token' => $token,
			'status' => 0,
		];

		$this->db->query('INSERT INTO accounts VALUES (:id, :email, :login, :wallet, :password, :ref, :refBalance, :token, :status)', $params);
		mail($post['email'], 'Register', 'Confirm: '.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/account/confirm/'.$token);

	}

	public function checkData($login, $password) {
		$params = [
			'login' => $login,
		];
		$hash = $this->db->column('SELECT password FROM accounts WHERE login = :login', $params);
		if (!$hash or !password_verify($password, $hash)) {
			return false;
		}
		return true;
	}

	// проверка на подтвержденный аккаунт
	public function checkStatus($type, $data) {
		$params = [
			$type => $data,
		];
		$status = $this->db->column('SELECT status FROM accounts WHERE '.$type.' = :'.$type, $params);
		if ($status != 1) {
			$this->error = 'Аккаунт ожидает подтверждения по E-mail';
			return false;
		}
		return true;
	}

	// вход в аккаунт
	public function login($login) {
		$params = [
			'login' => $login,
		];
		$data = $this->db->row('SELECT * FROM accounts WHERE login = :login', $params);
		$_SESSION['account'] = $data[0];
	}


	public function recovery($post) {
		$token = $this->createToken();
		$params = [
			'email' => $post['email'],
			'token' => $token,
		];
		$this->db->query('UPDATE accounts SET token = :token WHERE email = :email', $params);
		mail($post['email'], 'Recovery', 'Confirm: '.$_SERVER['REQUEST_SCHEME'].'://'.$_SERVER['HTTP_HOST'].'/account/reset/'.$token);
	}


	public function reset($token) {
		$new_password = $this->createToken();
		$params = [
			'token' => $token,
			'password' => password_hash($new_password, PASSWORD_BCRYPT),
		];
		$this->db->query('UPDATE accounts SET status = 1, token = "", password = :password WHERE token = :token', $params);
		return $new_password;
	}

	// обновление данных аккаунта
	public function save($post) {
		$params = [
			'id' => $_SESSION['account']['id'],
			'email' => $post['email'],
			'wallet' => $post['wallet'],
		];
		if (!empty($post['password'])) {
			$params['password'] = password_hash($post['password'], PASSWORD_BCRYPT);
			$sql = ',password = :password';
		}
		else {
			$sql = '';
		}
		foreach ($params as $key => $val) {
			$_SESSION['account'][$key] = $val;
		}
		$this->db->query('UPDATE accounts SET email = :email, wallet = :wallet'.$sql.' WHERE id = :id', $params);
	}


}