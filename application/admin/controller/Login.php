<?php
namespace app\admin\controller;

use app\common\controller\Base;
use think\Session;

class Login extends Base {
	private $setting;
	public function _initialize(){
		$this->setting = getSetting();
		if(Session::has('login_data')){
			$this->redirect(url('admin/index/index'));
		}
	}
	public function index(){
		$maxLogin 		=	$this->setting['login_max_error'];
		// 获取用户ip
		$ip 			=	Request()->ip();
		$now 			=	time();
		if(input('post.')){
			$loginError 	=	Session::has('login_error')?Session::get('login_error'):0;
			// 如果登录次数超过5则禁止登录
			if($loginError >= $maxLogin){
				// 准备
				return $this->error('登录错误次数过多');
			}
			// 判断用户名
			$username 		=	input('?post.username') ? htmlspecialchars(input('post.username')) :false;	
			// 密码
			$password 		=	input('?post.password') ? md5(input('post.password')):false;

			if(!$username || !$password){
				Session::set('login_error',$loginError+1);
				return $this->error('用户名与密码不能有一项为空');
			}

			// 开始查找
			$findUser 		=	Model('Admins')
									->where([
										'username' 	=>	$username,
										'password' 	=>	$password
									])
									->find();

			if($findUser){
				$findUser 	=	$findUser->toArray();

				// 检测用户状态
				if(!$findUser['status']){
					return $this->error('用户被锁定');
				}
				
				Session::set('login_data',$findUser);
				$login_num 	=	$findUser['login_num']+1;
				$up 		=	Model('Admins')->where('username',$username)->update([
					'last_login_ip' 	=>	$ip,
					'last_login_time' 	=>	$now,
					'login_num' 		=>	$login_num
				]);

				// 准备插入登录信息数据
				$loginData 	=	addLoginData('admin',1,$username);
				if($loginData || $up){
					return $this->success('登录成功');
				}else{
					return $this->error('登录失败，数据库错误');
				}
				
			}else{
				Session::set('login_error',$loginError+1);
				$hasError 	=	$maxLogin-Session::get('login_error');
				return $this->error("登录失败，还有{$hasError}次机会");
			}
		}

		return view();
	}
}
?>