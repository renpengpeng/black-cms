<?php
/*
	@author 		任鹏鹏
	@copyright 		http://www.black-cms.cn
*/
namespace app\admin\controller;
use app\common\controller\Base;
use think\Session;
use think\Cookie;

class User extends Base {
	// 定义setting
	private $setting;
	private $userInfo;
	private $field;
	private $fieldAdmin;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();
		
		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
		$this->field 		=	Model('Users')->getTableFields();
		$this->fieldAdmin 	=	Model('Admins')->getTableFields();
	}

	/*
		*	用户列表
	*/
	public function index(){
		$limit 	=	$this->setting['admin_list_num'];
		$type 	=	input('?type') ? htmlspecialchars(input('type')) : 'user';
		$page 	=	input('?page') ? intval(input('?page')) : 1;

		if(!in_array($type,['user','admin'])){
			return $this->error('找不到相应的请求类型！');
		}

		switch ($type) {
			case 'user':
				$table 		=	'Users';
				$boxTitle 	=	'会员列表';
			break;
			
			case 'admin':
				$table 		=	'Admins';
				$boxTitle 	=	'管理员列表';
			break;
		}

		$listData 	=	Model($table)->order('id desc')->limit($limit)->page($page)->select();
		$pageData 	=	Model($table)->paginate($limit);

		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);
		$this->assign('boxTitle',$boxTitle);
		return view();
	}
	/*
		*	添加 * 编辑前台会员  视图
	*/
	public function newuser(){
		$id 			=	input('?id') ? intval(input('id')) : false;
		if($id){
			$message 	=	Model('Users')->get($id);
			if(!$message){
				return $this->error('没有找到该用户');
			}

			$message 	=	$message->toArray();
			$boxTitle 	=	"编辑会员信息 - [ID:{$id}]";
		}else{
			foreach ($this->field as $key => $value) {
				$message[$value] 	=	'';
			}
			$boxTitle 	=	"添加新会员";
		}

		$this->assign('message',$message);
		$this->assign('boxTitle',$boxTitle);
		$this->assign('setting',$this->setting);
		return view();
	}	
	/*
		*	处理 添加 修改会员
	*/
	public function newuser_out(){
		$data 	=	input('post.');
		if(!$data || empty($data)){
			return $this->error('没有接收到任何数据');
		}else{
			$data 	=	safeArray($data);
		}

		$id 		=	isset($data['id']) ? intval($data['id']) : false;

		if(isset($data['password'])){
			$data['password'] 	=	md5($data['password']);
		}

		if($id){
			unset($data['id']);
			$up 	=	Model('Users')->where('id',$id)->update($data);
			$oper 	=	"修改ID为{$id}的用户信息";
		}else{
			$data['reg_time'] 	=	time();
			$up 				=	Model('Users')->insert($data);
			$oper 				=	"添加一个会员";
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
		*	删除一个会员
	*/
	public function deluser(){
		$id 	=	input('?id') ? intval(input('id')) : false;
		if(!$id){
			return $this->error('没有会员id信息');
		}

		$up 	=	Model('Users')->where('id',$id)->delete();
		$oper 	=	'删除ID为'.$id.'的会员';

		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('删除成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->success('删除失败！');
		}
	}
	/*
		管理员列表
	*/
	public function adminlist(){
		$limit 			=	$this->setting['admin_list_num'];
		$page 			=	input('?page') ? intval(input('page')) : 1;

		$listData 		=	Model('Admins')->order('id desc')->page($page)->limit($limit)->select();
		$pageData 		=	Model('Admins')->paginate($limit);

		// 获取权限组
		if(!empty($listData)){
			foreach ($listData as $key => $value) {
				$auth 			=	$listData[$key]['auth'];
				$findNAME 		=	Model('AuthAdmin')->where('id',$auth)->find();
				if($findNAME){
					$findNAME 	=	$findNAME->toArray();
					$listData[$key]['authname'] 	=	$findNAME['authname'];
				}else{
					$listData[$key]['authname'] 	=	'NULL';
				}
			}
		}else{
			$listData[$key]['authname'] 	=	'NULL';
		}


		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);

		return view();
	}
	/*
		*	添加新管理员
	*/
	public function newadmin(){
		$id 						=	input('id') ? intval(input('id')) : false;

		if($id){
			$message 				=	Model('Admins')->find($id);
			$boxTitle 				=	"编辑管理员信息-[ID:{$id}]";
		}else{
			foreach ($this->fieldAdmin as $key => $value) {
				$message[$value] 	=	'';
			}
			$boxTitle 				=	"添加新管理员";
		}

		// 获取权限列表
		$authData 		=	Model('AuthAdmin')->order('id asc')->select();


		$this->assign('message',$message);
		$this->assign('boxTitle',$boxTitle);
		$this->assign('authData',$authData);

		return view();
	}
	/*
		*	添加/编辑  处理
	*/
	public function newadmin_out(){
		$data 		=	input('post.');
		if(empty($data) || !$data){
			return $this->error('没有接收到任何数据');
		}else{
			$data 	=	safeArray($data);
		}

		$id 		=	isset($data['id']) ? intval($data['id']) : false;
		if($id){
			unset($data['id']);
			$up 	=	Model('Admins')->where('id',$id)->update($data);
			$oper 	=	"修改ID为{$id}的管理员信息";
		}else{
			$data['addtime'] 	=	time();
			$data['password'] 	=	passMD5($data['password']);
			$up 	=	Model('Admins')->insert($data);
			$oper 	=	"添加一个管理员";
		}

		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			if(isset($id) && $id  == $this->userInfo['id']){
				login_out('admin');
			}
			return $this->success('操作成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->error('操作失败！');
		}
	}
	/*
		*	删除一个管理员
	*/
	public function deladmin(){
		$id 	=	input('?id') ? intval(input('id')) : false;
		if(!$id){
			return $this->error('删除失败！');
		}	

		$up 	=	Model('Admins')->where('id',$id)->delete();
		$oper 	=	"删除ID为{$id}的管理员";

		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			if($id 	==	$this->userInfo['id']){
				login_out('admin');
			}
			return $this->success('删除成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('删除失败！');
		}
	}
	/*
		*	退出登录
	*/
	public function drop_out(){
		login_out('admin');
	}
}