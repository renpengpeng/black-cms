<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

###################以上为thinkphp5版本的默认信息##########################
/*
	@author 		任鹏鹏
	@copyright 		http://www.black-cms.cn
*/
use think\Session;
use think\Cookie;
use think\Request;
use app\common\controller\Base;
use think\Config;


#-----------------------------------
/*
	*	获取setting系统设置
*/
#-----------------------------------
function getSetting(){
	$result 	=	Model('Setting')->get(1)->toArray();

	return $result;
}

#-----------------------------------
/*
	*	后台自动加载项
*/
#-----------------------------------
function adminLoad(){
	// 判断登录
	checkLogin();
	// 判断权限
	checkAuthAdmin();

}

#-----------------------------------
/*
	*	检测登录
*/
#-----------------------------------
function checkLogin(){
	// 判断是否登录
	if(!Session::has('login_data')){
		header("location:".url('admin/login/index'));die;
	}else{
		$userInfo 	=	Session::get('login_data');
		// 判断状态
		if(!$userInfo['status']){
			header("location:".url('admin/login/index'));die;
		}
	}

	// 获取管理员风格
	if(!Cookie::has('admin_style')){
		Cookie::set('admin_style',$userInfo['style']);
	}

}

#-----------------------------------
/*
	*	判断后台管理员权限
*/
#-----------------------------------
function checkAuthAdmin(){
	// 获取app  控制器 action
	$module 		=	strtolower(request()->module());
	$controller 	=	strtolower(request()->controller());
	$action 		=	strtolower(request()->action());



	// 如果权限为空 
	$auth 			=	Session::get('login_data')['auth'];

	// 通过关联id获取auth
	$find 			=	Model('AuthAdmin')->where('id',$auth)->find()->toArray();
	$auth 			=	intval($find['auth']);

	// 如果为-1  则除了 控制台 其他不允许
	if(!$auth){
		if($controller != 'other' && $controller != 'index'){
			errAdmin('职权已被撤销');die;
		}
		
	}

	if($auth != '0' && $auth != '-1'){

		dump(1);die;
		// 开始验证权限
		// 查找当前模块在数据库的id
		$findNowApp 	=	Model('AuthAdminAppname')->where('app',$controller)->fetchSql(true)->find();

		dump($findNowApp);die;
		if(!$findNowApp){
			errAdmin('找不到当前的模块信息');
		}

		dump($findNowApp);die;
		
		$findNowApp =	$findNowApp->toArray();
		$appId 		=	$findNowApp['id'];

		if(strripos($auth,',')){
			$appidArr 	=	explode(',', $auth);
			$appidArr 	=	safeArray($appidArr);
			if(!in_array($appId, $appidArr)){
				errAdmin('没有相应模块的权限');
			}
		}else{
			if($auth != $appId)	{
				errAdmin('没有相应模块的权限');
			}
		}

	}

}

#-----------------------------------
/*
	*	错误信息跳转
*/
#-----------------------------------
function errAdmin($msg){
	header('location:'.url('admin/other/err',['msg'=>$msg]));
}

#-----------------------------------
/*
	*	插入用户登录信息
*/
#-----------------------------------
function addLoginData($type='user',$error=1,$username='none'){
	$now 	=	time();
	$ip 	=	Request()->ip();
	// 整理数据
	$insertData 	=	[
		'type' 		=>	$type,
		'error' 	=>	$error,
		'username' 	=>	$username,
		'addtime' 	=>	$now,
		'ip' 		=>	$ip
	];
	// 开始插入
	$add 	=	Model('LoginData')->insert($insertData);
	if($add){
		return true;
	}else{
		return false;
	}
}


#-----------------------------------
/*
	*	获取所有分类 li形式 (后台使用)
*/
#-----------------------------------
function getAllCateForLiAdmin($pid=0){
	$result 	=	"<ul>";
	// 获取pid为当前pid的值
	$pidSelect 	=	Model('Cates')->where('pid',$pid)->select();
	if($pidSelect){
		$pidSelect 	=	$pidSelect->toArray();
		foreach ($pidSelect as $key => $value) {
			$result 	.=	'<li><input type="checkbox" value="'.$pidSelect[$key]['id'].'">'.str_repeat('&nbsp;&nbsp;&nbsp;', $pidSelect[$key]['grade']*2).$pidSelect[$key]['title'].'</li>';
			$pids 		=	$pidSelect[$key]['id'];
			$count 		=	Model('Cates')->where('pid',$pids)->count();
			if($count!=0){
				$result 	.=	getAllCateForLiAdmin($pids);
			}
		}

	}
	$result 	.=	'</ul>';
	return $result;
}
#-----------------------------------
/*
	*	获取所有数组的形式
*/
#-----------------------------------
function getAllCateForArray($pid=0){
	$pidSelect 			=	Model('Cates')->where('pid',$pid)->select();
	if($pidSelect){
		$pidSelect		=	$pidSelect->toArray();
		foreach ($pidSelect as $key => $value) {
			$pids 		=	$pidSelect[$key]['id'];
			$count 		=	Model('Cates')->where('pid',$pids)->select();
			if($count){
				$count 	=	$count->toArray();
				$pidSelect[$key] 	+= 	$count;
			}
		}
	}
	return $pidSelect;
}

#-----------------------------------
/*
	*	获取所有分类option 形式
*/
#-----------------------------------
function getAllCateForOptionAdmin($pid=0){
	$result 	=	"";
	// 获取pid为当前pid的值
	$pidSelect 	=	Model('Cates')->where('pid',$pid)->select();
	if($pidSelect){
		$pidSelect 	=	$pidSelect->toArray();
		foreach ($pidSelect as $key => $value) {
			$result 	.=	'<option value="'.$pidSelect[$key]['id'].'">'.str_repeat('&nbsp;&nbsp;&nbsp;', $pidSelect[$key]['grade']*2).$pidSelect[$key]['title'].'</option>';
			$pids 		=	$pidSelect[$key]['id'];
			$count 		=	Model('Cates')->where('pid',$pids)->count();
			if($count!=0){
				$result 	.=	getAllCateForOptionAdmin($pids);
			}
		}

	}
	return $result;
}

#-----------------------------------
/*
	*	递归查找分类的所有子分类
*/
#-----------------------------------
function findNextCateAll($id,$arr=[]){
	$select =	Model('Cates')->where('pid',$id)->select();
	if($select){
		$select 	=	$select->toArray();
		foreach ($select as $key => $value) {
			array_push($arr, $select[$key]['id']);
			$arr 	=	array_merge($arr,findNextCateAll($select[$key]['id']));
		}
	}

	array_push($arr, $id);
	$arr 	=	array_unique($arr);
	return array_unique($arr);
}


#-----------------------------------
/*
	*	获取所有菜单 li形式 (后台使用)
*/
#-----------------------------------
function getAllMenuForLiAdmin($pid=0){
	$result 	=	"<ul>";
	// 获取pid为当前pid的值
	$pidSelect 	=	Model('Menus')->where('pid',$pid)->select();
	if($pidSelect){
		$pidSelect 	=	$pidSelect->toArray();
		foreach ($pidSelect as $key => $value) {
			$result 	.=	'<li><input type="hidden" name="id" value="'.$pidSelect[$key]['id'].'">'.str_repeat('&nbsp;&nbsp;&nbsp;', $pidSelect[$key]['grade']*2).$pidSelect[$key]['title'].'</li>';
			$pids 		=	$pidSelect[$key]['id'];
			$count 		=	Model('Menus')->where('pid',$pids)->count();
			if($count!=0){
				$result 	.=	getAllMenuForLiAdmin($pids);
			}
		}

	}
	$result 	.=	'</ul>';
	return $result;
}

#-----------------------------------
/*
	*	获取所有数组的形式
*/
#-----------------------------------
function getAllMenuForArray($pid=0){
	$pidSelect 			=	Model('Menus')->where('pid',$pid)->select();
	if($pidSelect){
		$pidSelect		=	$pidSelect->toArray();
		foreach ($pidSelect as $key => $value) {
			$pids 		=	$pidSelect[$key]['id'];
			$count 		=	Model('Menus')->where('pid',$pids)->select();
			if($count){
				$count 	=	$count->toArray();
				$pidSelect[$key] 	+= 	$count;
			}
		}
	}
	return $pidSelect;
}

#-----------------------------------
/*
	*	获取所有分类option 形式
*/
#-----------------------------------
function getAllMenuForOptionAdmin($pid=0){
	$result 	=	"";
	// 获取pid为当前pid的值
	$pidSelect 	=	Model('Menus')->where('pid',$pid)->select();
	if($pidSelect){
		$pidSelect 	=	$pidSelect->toArray();
		foreach ($pidSelect as $key => $value) {
			$result 	.=	'<option value="'.$pidSelect[$key]['id'].'">'.str_repeat('&nbsp;&nbsp;&nbsp;', $pidSelect[$key]['grade']*2).$pidSelect[$key]['title'].'</option>';
			$pids 		=	$pidSelect[$key]['id'];
			$count 		=	Model('Menus')->where('pid',$pids)->count();
			if($count!=0){
				$result 	.=	getAllMenuForOptionAdmin($pids);
			}
		}

	}
	return $result;
}

#-----------------------------------
/*
	*	递归查找分类的所有子菜单
*/
#-----------------------------------
function findNextMenuAll($id,$arr=[]){
	$select =	Model('Menus')->where('pid',$id)->select();
	if($select){
		$select 	=	$select->toArray();
		foreach ($select as $key => $value) {
			array_push($arr, $select[$key]['id']);
			$arr 	=	array_merge($arr,findNextMenuAll($select[$key]['id']));
		}
	}

	array_push($arr, $id);
	$arr 	=	array_unique($arr);
	return array_values($arr);
}
#-----------------------------------
/*
	*	添加操作记录
*/
#-----------------------------------
function addOperData($id,$type,$oper,$status){
	$now 	= 	time();
	$data 	=	[
		'type' 				=>	$type,
		'user_id' 			=>	$id,
		'oper' 				=>	$oper,
		'oper_status' 		=>	$status,
		'addtime' 			=>	$now
	];

	Model('OperData')->insert($data);
}

#-----------------------------------
/*
	*	通过STATUS来决定状态 title 后台适用
*/
#-----------------------------------
function getStatusOfBoxTitle($status,$wd=''){
	if(is_numeric($status)){
		switch ($status) {
			case 0:
				$title 	=	'未审核';
			break;
			
			case 1:
				$title 	=	'正常的';
			break;

			case 2:
				$title 	=	'草稿';
			break;

			case 3:
				$title 	=	'回收站';
			break;

			default:
				$title 	=	'未知';
			break;
		}
	}else{
		switch ($status) {
			case 'search':
				$title 	=	$wd.'的搜索结果';
			break;

			case 'all':
				$title 	=	'所有的';
			break;
		}
	}
	return $title;
}

#-----------------------------------
/*
	*	安全的数组
	*	利用 htmlspecialchars过滤
*/
#-----------------------------------
function safeArray($arr){
	if(!is_array($arr)){
		return false;
	}

	foreach ($arr as $key => $value) {
		if(!is_array($value)){
			$arr[$key] 	=	htmlspecialchars($arr[$key]);
			if(strlen($value) == 0){
				unset($arr[$key]);
			}
		}else{
			$arr[$key] 	=	safeArray($arr[$key]);
		}
	}

	return $arr;
}

#-----------------------------------
/*
	*	反过滤安全的数组
	*	利用  htmlspecialchars_decode
*/
#-----------------------------------
function nosafeArray($arr){
	if(!is_array($arr)){
		return false;
	}

	foreach ($arr as $key => $value) {
		if(!is_array($value)){
			$arr[$key] 	=	htmlspecialchars_decode($arr[$key]);			
		}else{
			$arr[$key] 	=	safeArray($arr[$key]);
		}
	}

	return $arr;
}

#-----------------------------------
/*
	*	获取权限名称
*/
#-----------------------------------
function getAuthNameAdmin($auth){
	$authname 	=	'';
	if(strripos($auth, ',')){
		$authArr 					=	explode(',', $auth);
		$authArr 					=	safeArray($authArr);
		foreach ($authArr as $ke => $va) {
			$find 						=	Model('AuthAdminAppname')->where('id',$va)->find();
			if($find){
				$find 		 =	$find->toArray();
				$authname  	.=		$find['appname'].',';
			}else{
				if($va != '0'  || $va != '-1'){
					$listData[$key]['authnames']  	.=	 	'未知,';
				}else{
					if($va == 0){
						$authname 	=	'所有权限';
					}else{
						$authname 	=	'已撤销的职权';
					}
				}
			}
		}
	}else{
		$find 						=	Model('AuthAdminAppname')->where('id',$auth)->find();
		if($find){
			$find 		=	$find->toArray();
			$authname   	.=		$find['appname'].',';
		}else{
			if($auth == 0){
				$authname 	=	'已撤销的职权';
			}else{
				$authname  	=	'所有权限';
			}
		}
	}
	if(strripos($authname  , ',') == strlen($authname )-1){
		$authname 	=	substr($authname ,0, strlen($authname )-1);
	}
	return $authname;
}

#-----------------------------------
/*
	*	密码加密
	*	用于登录注册
*/
#-----------------------------------
function passMD5($pass){
	$str 	=	Config::get('bcms.password_str');
	return md5($str.$pass);
}

#-----------------------------------
/*
	*	登录  前台后台均可
*/
#-----------------------------------
function login($username,$password){
	return 1;
}

#-----------------------------------
/*
	*	退出登录
*/
#-----------------------------------
function login_out($type){
	switch ($type) {
		case 'admin':
			Session::delete('login_data');
			$url 	=	url('admin/login/index');
		break;
		
	}

	header("location:".$url);
}

