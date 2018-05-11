<?php
namespace app\admin\controller;

use think\Session;
use think\Cookie;
use think\Request;
use app\common\controller\Base;
use think\Config;

class Index extends Base {
	private $setting;
	private $userInfo;

	public function _initialize(){
		// 后台自动加载项
		adminLoad();

		$this->userInfo 	=	Session::get('login_data');
		$this->setting 		=	getSetting();
	}
	/*
		*	后台管理中心
	*/
	public function index(){
		return view();
	}
	/*
		*	控制台
	*/
	public function console(){
		$limit 			=	$this->setting['admin_list_num'];
		// 获取文章数量
		$articleCount 	=	Model('Articles')->count();
		// 获取会员总数
		$userCount 		=	Model('Users')->count();
		// 获取会员登录次数
		$loginCount		=	Model('LoginData')
								->field('type,error')
								->where([
									'type' 		=> 	'user',
									'error' 	=>	1
								])
								->count();
		// 获取蜘蛛抓取次数
		$spiderCount 	=	Model('Spiders')->count();
		// 获取最新的管理员操作记录
		$adminOper 		=	Model('OperData')->order('id desc')->where('type','admin')->limit($limit)->select();
		if($adminOper){
			$adminOper 	=	$adminOper->toArray();
			foreach ($adminOper as $key => $value) {
				$adminid 	=	$adminOper[$key]['user_id'];
				$getUser 	=	Model('Admins')->field('id,bbsname')->where('id',$adminid)->find();
				if($getUser){
					$getUser						=	$getUser->toArray();
					$adminOper[$key]['bbsname'] 	=	$getUser['bbsname'];
				}else{
					$adminOper[$key]['bbsname']		=	'none';
				}
			}
		}

		$this->assign('articleCount',$articleCount);
		$this->assign('userCount',$userCount);
		$this->assign('loginCount',$loginCount);
		$this->assign('adminOper',$adminOper);
		$this->assign('spiderCount',$spiderCount);
		return view();
	}
	/*
		*	储存草稿
	*/
	public function draft(){
		$title 		=	input('?post.title') ? htmlentities(input('post.title')) : false;
		$content 	=	input('?post.content') 	? htmlspecialchars(input('?post.content')) : false;

		if(!$title || !$content){
			return $this->error('请输入数据');
		}

		// 组合数据
		$data 		=	[
			'title' 	=>	$title,
			'content' 	=> 	$content,
			'addtime' 	=>	time(),
			'status' 	=>	2,
			'the_user'  =>	$this->userInfo['id']
		];
		$add 	=	Model('Articles')->insert($data);
		if($add){
			return $this->success('添加草稿成功');
		}else{
			return $this->error('添加草稿失败');
		}
	}
	
}

?>