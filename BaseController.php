<?php
namespace phpkit\core;
use Phalcon\Mvc\Controller;
use phpkit\backend\View as backendView;

class BaseController extends Controller {
	public function initialize() {
		//parent::initialize();
		$this->ControllerName = \phpkit\helper\convertUnderline($this->dispatcher->getControllerName());
		$this->ActionName = $this->dispatcher->getActionName();
	}

	protected function jump($msg = "", $url = "") {
		header("Content-type: text/html; charset=utf-8");
		$new_url = $url ? $url : $_SERVER['HTTP_REFERER'];
		if ($msg) {
			$msg = " alert('{$msg}');";
		}
		echo '<script language="javascript" type="text/javascript">' . $msg . ' window.location.href="' . $new_url . '"; </script>';

		exit();
	}

	public function fetch($controllerName = "", $actionName = "") {
		$controllerName = $controllerName ? $controllerName : $this->ControllerName;
		$actionName = $actionName ? $actionName : $this->ActionName;
		$content = $this->view->getRender($controllerName, $actionName);
		return $content;
	}

	public function display($controllerName = "", $actionName = "") {
		echo $this->fetch($controllerName, $actionName);
	}

	public function adminDisplay($controllerName = "", $actionName = "") {
		$content = $this->fetch($controllerName, $actionName);
		$backendView = new backendView();
		$backendView->display($content);

	}

}