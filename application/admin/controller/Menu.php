<?php
namespace app\admin\controller;

use think\Session;
use think\Cookie;
use think\Request;
use app\common\controller\Base;
use think\Config;

class Menu extends Base {
	private $setting;
	private $userInfo;
	private $field;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
		$this->field 		=	Model('Menus')->getTableFields();
	}
	
	public function index(){
		// 查找所有的菜单  li显示
		$allMenu 			=	getAllMenuForLiAdmin();
		$allMenuOpation 	=	getAllMenuForOptionAdmin();

		// dump($allMenu);die;

		$this->assign('allMenu',$allMenu);
		$this->assign('allMenuOpation',$allMenuOpation);
		return view();
	}
	/*
		*	添加/编辑 新菜单
	*/
	public function newmenu_out(){
		$data 		=	input('post.');

		if(!$data){
			return $this->error('无法获取到数据');
		}else{
			$data 	=	safeArray($data);
		}

		$id 		=	isset($data['id']) ? intval($data['id']) : false;
		$pid 		=	isset($data['pid']) ? intval($data['pid']) :false;




		if($id){
			unset($data['id']);
			// 查询原信息
			$lastData 			=	Model('Menus')->get($id)->toArray();
			$lastGrade 			=	$lastData['grade'];

			// 计算grade
			if($pid){
				$findPid 		=	Model('Menus')->where('id',$pid)->find()->toArray();
				$pidGrade 		=	$findPid['grade'];
				$data['grade'] 	=	intval($pidGrade)+1;

				if($data['grade'] != $lastGrade){
					// 查找所有的子菜单ID
					$nextID 	=	findNextMenuAll($id);
					Model('Menus')->where('id','in',$nextID)->update(['grade'=>$data['grade']+1]);
				}
			}else{
				$data['grade'] 	=	1;
			}


			$up 	=	Model('Menus')->where('id',$id)->update($data);
			$oper 	=	"修改ID为{$id}的菜单信息";
		}else{
			$data['addtime'] 	=	time();
			// 计算grade
			if($pid){
				$findLast		=	Model('Menus')->get($pid)->toArray();
				$gradeLast 		=	$findLast['grade'];
				$data['grade'] 	=	$gradeLast;
			}
			$up 	=	Model('Menus')->insert($data);
			$oper 	=	"添加1个菜单";
		}


		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('操作成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('操作失败！');
		}

	}
	/*
		*	获取单个的信息
	*/
	public function get_one(){
		$id 	=	input('?id') ? intval(input('id')) : false;
		if(!$id){
			return $this->error('没有接收到ID');
		}

		$find 	=	Model('Menus')->get($id);
		if($find){
			return $this->success($find);
		}else{
			return $this->error('获取信息失败！');
		}
	}
}
