<?php

namespace application\controllers;

use application\core\Controller;
use application\lib\Pagination;


// контроллер для админ панели
class AdminController extends Controller {

	public function __construct($route) {
		parent::__construct($route);
		$this->view->layout = 'admin';
	}

	// вход в админку
	public function loginAction() {
		if (isset($_SESSION['admin'])) {
			$this->view->redirect('admin/withdraw');
		}
		if (!empty($_POST)) {
			if (!$this->model->loginValidate($_POST)) {
				$this->view->message('error', $this->model->error);
			}
			$_SESSION['admin'] = true;
			$this->view->location('admin/withdraw');
		}
		$this->view->render('Вход');
	}

	// работа с запросами на вывод средств
	public function withdrawAction() {
		if (!empty($_POST)) {
			if ($_POST['type'] == 'ref') {
				$result = $this->model->withdrawRefComplete($_POST['id']);
				if ($result) {
					$this->view->location('admin/withdraw');
				}
				else {
					$this->view->message('error', 'Ошибка обработки запроса');
				}
			}
			elseif ($_POST['type'] == 'tariff') {
				$result = $this->model->withdrawTariffsComplete($_POST['id']);
				if ($result) {
					$this->view->location('admin/withdraw');
				}
				else {
					$this->view->message('error', 'Ошибка обработки запроса');
				}
			}
		}
		$vars = [
			'listRef' => $this->model->withdrawRefList(),
			'listTariffs' => $this->model->withdrawTariffsList(),
		];
		$this->view->render('Заказы на вывод средств', $vars);
	}

	// работа с историей
	public function historyAction() {
		$pagination = new Pagination($this->route, $this->model->historyCount());
		$vars = [
			'pagination' => $pagination->get(),
			'list' => $this->model->historyList($this->route),
		];
		$this->view->render('История', $vars);
	}

	// список текущих инвестиций в тарифы
	public function tariffsAction() {
		$pagination = new Pagination($this->route, $this->model->tariffsCount());
		$vars = [
			'pagination' => $pagination->get(),
			'list' => $this->model->tariffsList($this->route),
		];
		$this->view->render('Список инвестиций', $vars);
	}

	// выход из админки
	public function logoutAction() {
		unset($_SESSION['admin']);
		$this->view->redirect('admin/login');
	}

}