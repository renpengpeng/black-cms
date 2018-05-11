<?php
/*
	@author 		任鹏鹏
	@copyright 		http://www.black-cms.cn
*/
namespace app\admin\controller;
use app\common\controller\Base;
use think\Session;
use think\Cookie;

class My extends Base {
	// 定义setting
	private $setting;
	private $userInfo;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		
		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
	}
    /*
		*	后台用户中心
    */
	public function index(){
		if(!Session::has('login_data')){
			$this->redirect(url('admin/login/index'));
		}
		return view();
	}
	/*
		*	风格
	*/
	public function skin(){
		return view();
	}
	public function skin_change(){
		$userid 	=	$this->userInfo['id'];
		$bgcolor 	=	input('?bgcolor') ? htmlspecialchars(input('bgcolor')) : false;
		if(!$bgcolor){
			return $this->error('未获取到颜色值！');
		}

		$up 		=	Model('Admins')->where('id',$userid)->update(['style'=>$bgcolor]);
		$oper 		=	"自定义后台背景颜色值为{$bgcolor}";

		if($up){
			addOperData($userid,'admin',$oper,1);
			return $this->success('修改背景成功');
		}else{
			return $this->error('修改背景失败');
		}
	}
	/*
		*	退出登录
	*/
	public function drop_out(){
		Session::delete('login_data');
		$this->redirect(url('admin/login/index'));
	}
}
?>