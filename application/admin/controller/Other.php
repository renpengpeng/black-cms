<?php
namespace app\admin\controller;

use think\Session;
use think\Cookie;
use think\Request;
use app\common\controller\Base;
// use think\Config;

class Other extends Base {
	private $setting;
	private $userInfo;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
	}

	/*
		*	错误页面 * 提示页面
	*/
	public function err($msg='未定义的错误类型'){
		$this->assign('msg',$msg);
		return view();
	}

}
?>