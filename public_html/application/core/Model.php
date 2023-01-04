<?php

namespace application\core;

use application\lib\Db;



// Базовый класс для моделей
abstract class Model {

	public $db;
	
	public function __construct() {
		$this->db = new Db;
	}

}