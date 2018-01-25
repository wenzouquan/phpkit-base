<?php
namespace phpkit\base;
use Phalcon\Mvc\Controller;
use phpkit\backend\View as backendView;

class BaseController extends Controller {
	public function initialize() {
		//parent::initialize();
		$this->ControllerName = \phpkit\helper\convertUnderline($this->dispatcher->getControllerName());
		$this->ActionName = $this->dispatcher->getActionName();
	    $this->appSetting = $this->di->getSetting();
		$this->view->appSetting = json_decode(json_encode($this->appSetting)); 
	}

	protected function jump($msg = "", $url = "") {
		header("Content-type: text/html; charset=utf-8");
		$new_url = $url ? $url : $_SERVER['HTTP_REFERER'];
		if ($msg) {
			$msg = " alert('{$msg}');";
		}
		echo '<script language="javascript" type="text/javascript">' . $msg . ' window.location.href="' . $new_url . '"; </script>';

		die();
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
		if(isset($_SERVER["HTTP_X_REQUESTED_WITH"]) && strtolower($_SERVER["HTTP_X_REQUESTED_WITH"])=="xmlhttprequest"){
			echo $content;
		}else{
			$backendView = new backendView(['phpkitApp'=>$this]);
		    $backendView->display($content);
		}
		
	}

}
