<?php
namespace phpkit\base;
class AdminController extends BaseController {
	public function initialize() {
		parent::initialize();
		//验证是否登录
		$this->checkLogin();
		//验证是否有权限
		$this->checkAuth();
		//$this->loginOut();
		$this->view->adminUserInfo = $this->adminUserInfo = $this->session->get('adminUserInfo');

	}
	//验证是否登录
	public function checkLogin() {
		//已经登录
		$adminUserInfo = $this->session->get('adminUserInfo');
		if (!empty($adminUserInfo['user_name'])) {
			return true;
		}
		$superUser = $this->getDi()->getConfig()->get('superUser', 'setIfNull');
		//提交登录
		if ($this->request->isPost() && $this->request->getPost('setAdminUserInfo') == 1) {

			$where = array(
				'user_name' => $this->request->getPost('user_name'),
				'password' => md5($this->request->getPost('password')),
			);
			if ($where['user_name'] == $superUser['user_name'] && $where['password'] == md5($superUser['password'])) {
				$adminUserInfo = array(
					'id' => '0',
					'type' => 'super',
					'user_name' => $where['user_name'],
				);
			} else {
				$model = new \phpkit\backend\models\SystemStoreUser();
				$storeUser = $model->where($where)->load();
				if ($storeUser == false) {
					$this->jump("用户名或者密码错误");
				}
				$adminUserInfo = $storeUser->toArray();
				$adminUserInfo['storeInfo'] = $storeUser->storeInfo;
			}

			$this->saveAdminLogin($adminUserInfo);
			$this->jump();
			exit();
		}

		//有设置登录地址，跳转登录
		if ($this->LoginUrl) {
			$this->jump("", $this->LoginUrl);
		}
		//直接显示登录页面
		$backEndViws = new \phpkit\backend\View();
		$backEndViws->login();
		exit();
	}

	//后台登录成功
	public function saveAdminLogin($adminUserInfo = array()) {
		//var_dump($this->session);
		$this->session->set('adminUserInfo', $adminUserInfo);
	}
//验证是否有权限
	public function checkAuth() {

	}

	public function loginOut() {
		$this->session->remove('adminUserInfo');
	}

}