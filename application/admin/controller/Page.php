<?php
namespace app\admin\controller;

use think\Session;
use think\Cookie;
use think\Request;
use app\common\controller\Base;
use think\Config;

class Page extends Base {
	private $setting;
	private $userInfo;
	private $field;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
		$this->field 		=	Model('Pages')->getTableFields();
	}
	/*
		*	所有页面展示
	*/ 
	public function index(){
		$limit 			=	$this->setting['admin_list_num'];
		$page 			=	input('?page') ? intval(input('page')) : false;
		$status 		=	input('?status') ? intval(input('status')) : 1;
		$type 			=	input('?type') ? htmlspecialchars(input('type')) : false;

		if($page){
			$countAnn 	=	Model('Pages')->count();
			if(ceil($countAnn/$limit) < $page){
				$page 	=	1;
			}
		}else{
			$page 		=	1;
		}

		if(in_array($status, ['0','1','2','3'])){
			$listData 		=	Model('Pages')
									->order('id desc')
									->where('status',$status)
									->limit($limit)
									->page($page)
									->select();

			$pageData 		=	Model('Pages')->where('status',$status)->paginate($limit);
		}

		if($type == 'all'){
			$listData 		=	Model('Pages')->order('id desc')->limit($limit)->page($page)->select();
			$pageData 		=	Model('Pages')->paginate($limit);
			$status 		=	'all';
		}
		


		$boxTitle 			=	getStatusOfBoxTitle($status,'');
		
		

		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);
		$this->assign('boxTitle',$boxTitle);
		$this->assign('status',$status);
		return view();
	}
	/*
		*	添加/编辑新页面
	*/
	public function newpage(){
		$id 	=	input('?id') ? input('id') :false;
		if($id){
			$message 		=	Model('Pages')->get($id);
			if(!$message){
				return $this->error('获取页面信息失败');
			}else{
				$message 	=	$message->toArray();
				$message 	=	nosafeArray($message);
			}
			$boxTitle 		=	"页面编辑 - [ID:{$id}]";
		}else{
			foreach ($this->field as $key => $value) {
				$message[$value]	=	'';
 			}
 			$boxTitle 		=	'添加新页面';
		}

		$this->assign('message',$message);
		$this->assign('boxTitle',$boxTitle);
		return view();
	}
	/*
		*	编辑/添加页面处理
	*/
	public function newpage_out(){
		$data 	=	input('post.');
		if(!$data || empty($data)){
			return $this->error('获取信息失败！');
		}

		foreach ($data as $key => $value) {
			$data[$key] 	=	htmlspecialchars($data[$key]);
			if(empty($value)){
				unset($data[$key]);
			}
		}

		$id 	=	isset($data['id']) ? intval($data['id']) : false;
		// 添加会员ID
		$data['the_user'] 			=	$this->userInfo['id'];

		// 如果有id  则为更新文章 否则则为添加文章
		if($id){
			unset($data['id']);
			$data['last_modify'] 	=	@time();
			$up 					=	Model('Pages')->where('id',$id)->update($data);
			if($up){
				$version				=	Model('Pages')->where('id',$id)->setInc('resivion');
			}
			$oper 					=	"修改页面ID为{$id}的内容";
		}else{
			$data['addtime'] 		=	@time();
			$up 					=	Model('Pages')->insert($data);
			$oper 					=	"新添加1篇页面";
		}


		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('操作成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('操作失败!');
		}
	}
	/*
		*	删除方法
	*/
	public function del(){
		$id 	=	input('?id') ? intval(input('id')) : false;
		$del 	=	Model('Pages')->where('id',$id)->delete();
		$oper 	=	'删除ID为'.$id.'的页面';
		if($del){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('删除成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('删除失败！');
		}
	}
}