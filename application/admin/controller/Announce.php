<?php
namespace app\admin\controller;

use think\Session;
use think\Cookie;
use think\Request;
use app\common\controller\Base;
use think\Config;

class Announce extends Base {
	private $setting;
	private $userInfo;
	private $field;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();
		
		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
		$this->field 		=	Model('Announce')->getTableFields();
	}
	/*
		*	公告列表
	*/
	public function index(){
		$limit 			=	$this->setting['admin_list_num'];
		$page 			=	input('?page') ? intval(input('page')) : false;
		$status 		=	input('?status') ? intval(input('status')) : 1;
		$type 			=	input('?type') ? htmlspecialchars(input('type')) : false;

		if($page){
			$countAnn 	=	Model('Announce')->count();
			if(ceil($countAnn/$limit) < $page){
				$page 	=	1;
			}
		}else{ 
			$page 		=	1;
		}

		if(in_array($status, ['0','1','2','3'])){
			$listData 		=	Model('Announce')
									->order('id desc')
									->where('status',$status)
									->limit($limit)
									->page($page)
									->select();

			$pageData 		=	Model('Announce')->where('status',$status)->paginate($limit);
		}

		if($type == 'all'){
			$listData 		=	Model('Announce')->order('id desc')->limit($limit)->page($page)->select();
			$pageData 		=	Model('Announce')->paginate($limit);
			$status 		=	'all';
		}
		if($listData){
			if(!empty($listData)){
				// 循环查找过期
				foreach ($listData as $key => $value) {
					$beginTime 	=	$listData[$key]['begintime'];
					$endTime 	=	$listData[$key]['endtime'];
					if( time() > $endTime){
						$listData[$key]['exped'] 	=	1;
					}else{
						$listData[$key]['exped'] 	=	0;
					}
				}
			}
		
		}
		


		$boxTitle 			=	getStatusOfBoxTitle($status,'');
		
		

		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);
		$this->assign('boxTitle',$boxTitle);
		$this->assign('status',$status);
		return view();
	}
	/*
		*	添加 / 编辑公告模板
	*/
	public function newannounce(){
		$id 						=	input('?id') ? intval(input('id')) : false;

		if($id){
			$message 				=	Model('Announce')->get($id);
			if(!$message){
				return $this->error('获取公告信息失败！');
			}
			$message 				=	$message->toArray();
			$message 				=	nosafeArray($message);
			$boxTitle 				=	"公告编辑 - [ID:{$id}]";
		}else{
			foreach ($this->field as $key => $value) {
				if($value == 'begintime' || $value == 'endtime'){
					$message[$value]=	0;
				}else{
					$message[$value]	=	'';
				}
				
 			}
 			$boxTitle 				=	'添加新公告';
		}

		$this->assign('message',$message);
		$this->assign('boxTitle',$boxTitle);
		return view();
	}
	/*
		*	添加 / 编辑新公告处理
	*/
	public function newannounce_out(){
		$data 				=	input('post.');
		if(!$data || empty($data)){
			return $this->error('找不到相关信息！');
		}

		$data 				=	safeArray($data);

		$id 				=	isset($data['id']) ? intval($data['id']) : false;
		$data['the_user'] 	=	$this->userInfo['id'];

		// 格式化时间
		$data['begintime'] 	=	strtotime($data['begintime']);
		$data['endtime'] 	=	strtotime($data['endtime']);

		if($id){
			unset($data['id']);
			$up 					=	Model('Announce')->where('id',$id)->update($data);
			if($up){
				$version				=	Model('Announce')->where('id',$id)->setInc('resivion');
			}
			$oper 					=	"修改公告ID为{$id}的内容";
		}else{
			$data['addtime'] 		=	@time();
			$up 					=	Model('Announce')->insert($data);
			$oper 					=	"新添加1篇公告";
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
		*	删除
	*/
	public function del(){
		$id 	=	input('?id') ? intval(input('id')) : false;
		if(!$id){
			return $this->error('获取公告信息失败！');
		}

		$del 	=	Model('Announce')->where('id',$id)->delete();
		$oper 	=	"删除一篇ID为{$id}的公告";

		if($del){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('删除成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('删除失败！');
		}
	}
}