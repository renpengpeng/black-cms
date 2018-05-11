<?php
/*
	@author 	任鹏鹏
	@copyright 	www.black-cms.cn
*/
namespace app\admin\controller;

use app\common\controller\Base;
use think\Session;
use think\Cookie;

class Auth extends Base {
	private $setting;
	private $userInfo;
	private $field;
	private $appfield;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
		$this->field 		=	Model('AuthAdmin')->getTableFields();
		$this->appfield 	=	Model('AuthAdminAppname')->getTableFields();
	}
	public function index(){
		$type 				=	input('?type') ? input('type') : 'admin';
		$limit 				=	$this->setting['admin_list_num'];
		$page 				=	input('?page') ? inrval('page') : 1;

		if(!in_array($type,['user','admin'])){
			errAdmin('没有指定的类型');
		}

		switch ($type) {
			case 'value':
				$table 		=	'';
				$boxTitle 	=	'会员权限列表';
			break;
			
			case 'admin':
				$table 		=	'AuthAdmin';
				$boxTitle 	=	'管理员权限列表';
			break;
		}

		$listData 			=	Model('AuthAdmin')->order('id asc')->limit($limit)->page($page)->select();
		$pageData 			=	Model('AuthAdmin')->paginate($limit);

		if($listData){
			if(!empty($listData)){
				// 查询可访问操作的控制器
				foreach ($listData as $key => $value) {
					$listData[$key]['authnames'] 	=	'';
					$auth 	=	$listData[$key]['auth'];
					$listData[$key]['authnames'] 	=	getAuthNameAdmin($auth);
				}
			}
		}

		$this->assign('boxTitle',$boxTitle);
		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);

		return view();
	}
	/*
		*	添加新权限  管理员组
	*/
	public function newauthadmin(){
		$id 				=	input('?id') ? intval(input('id')) : false;
		if($id){
			$message 		=	Model('AuthAdmin')->get($id);
			if(!$message){
				return $this->error('获取信息失败！');
			}else{
				$message 	=	$message->toArray();
			}
			$boxTitle 		=	"管理权限组编辑 - [ID:{$id}]";
		}else{
			foreach ($this->field as $key => $value) {
				$message[$value] 	=	'';
			}
			$boxTitle 		=	"添加管理权限组";
		}

		// 查询控制器
		$controller 		=	Model('AuthAdminAppname')->order('id asc')->select();

		$this->assign('message',$message);
		$this->assign('boxTitle',$boxTitle);
		$this->assign('controller',$controller);

		return view();
	}
	public function newauthadmin_out(){
		$data 		=	input('post.');
		if(!$data || empty($data)){
			return $this->error('没有数据可操作');
		}else{
			$data 	=	safeArray($data);
		}

		$id 		=	isset($data['id']) ? intval($data['id']) : false;
		if($id){
			unset($data['id']);
			$up 	=	Model('AuthAdmin')->where('id',$id)->update($data);
			$oper 	=	"修改管理员权限组ID为{$id}的信息";
		}else{
			$up 	=	Model('AuthAdmin')->insert($data);
			$oper 	=	"增加1个管理员权限组";
		}

		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('新增成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('新增失败！');
		}
	}
	/*
		*	删除管理组
	*/
	public function delauthadmin(){
		$id 	=	input('?id') ? intval(input('id')) : false;
		if(!$id){
			return $this->error('没有接收到任何数据');
		}

		$find 	=	Model('AuthAdmin')->where('id',$id)->delete();
		$oper 	=	"删除一条管理员权限组";

		if($find){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('删除成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('删除失败！');
		}
	}
	/*
		*	后台控制器名字  * 视图
	*/
	public function adminappname(){
		$limit 		=	$this->setting['admin_list_num'];
		$page 		=	input('?page') ? intval(input('page')) : 1;

		$listData 	=	Model('AuthAdminAppname')->order('id asc')->limit($limit)->page($page)->select();
		$pageData 	=	Model('AuthAdminAppname')->paginate($limit);

		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);
 		return view();
	}
	/*
		*	后台控制器名字  * 添加/编辑
	*/
	public function newadminappname(){
		$id 	=	input('?id') ? intval(input('id')) : false;

		if($id){
			$message 	=	Model('AuthAdminAppname')->find($id);
			if(!$message){
				return $this->error('获取信息失败！');
			}

			$message 	=	$message->toArray();
			$boxTitle 	=	"控制器名称编辑 - [ID:{$id}]";
		}else{
			foreach ($this->appfield as $key => $value) {
				$message[$value] 	=	'';
			}
			$boxTitle 	=	"添加新的控制器名称";
		}

		$this->assign('boxTitle',$boxTitle);
		$this->assign('message',$message);

		return view();
	}
	/*
		*	后台控制器名字添加* 编辑处理
	*/
	public function newadminappname_out(){
		$data 	=	input('post.');
		if(empty($data) || !$data){
			return $this->error('没有接收到任何数据');
		}else{
			$data 	=	safeArray($data);
		}

		$id 		=	isset($data['id']) ? intval(input('id')) : false;

		if($id){
			unset($data['id']);
			$up 	=	Model('AuthAdminAppname')->where('id',$id)->update($data);
			$oper 	=	"修改ID为的后台控制器信息";
		}else{
			$up 	=	Model('AuthAdminAppname')->insert($data);
			$oper 	=	"添加1条后台控制器";
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
		*	后台控制器名字 * 删除
	*/
	public function adminappname_del(){
		$id 	=	input('?id') ? intval('id') : false;
		if(!$id){
			return $this->error('没有ID信息');
		}

		$up 	=	Model('AuthAdminAppname')->where('id',$id)->delete();
		$oper 	=	"删除ID为{$id}的控制器";

		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('删除成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('删除失败！');
		}
	}
}