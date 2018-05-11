<?php
namespace app\admin\controller;

use think\Session;
use think\Cookie;
use think\Request;
use app\common\controller\Base;
use think\Config;
 
class Link extends Base {
	private $setting;
	private $userInfo;
	private $field;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
		$this->field 		=	Model('Links')->getTableFields();
	}
	/*
		*	友情链接列表
	*/
	public function index(){
		$limit 		=	$this->setting['admin_list_num'];
		$page 		=	input('?page') ? intval('page') : 1;

		$listData 	=	Model('Links')
						->order('id desc')
						->page($page)
						->limit($limit)
						->select();

		$pageData 	=	Model('Links')->paginate($limit);

		$boxTitle 		=	'友情链接';


		if(@$listData->toArray()){
			foreach ($listData as $key => $value) {
				$userid =	$listData[$key]['the_user'];
				$findUsername 	=	Model('Admins')->where('id',$userid)->find();
				if(!$findUsername){
					$listData[$key]['addusername'] 	=	'未知';
					continue;
				}

				$findUsername 	=	$findUsername->toArray();
				$listData[$key]['addusername'] 		=	$findUsername['bbsname'];
			}
		}



		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);
		$this->assign('boxTitle',$boxTitle);

		return view();

	}
	/*
		*	添加新友情链接  视图
	*/
	public function newlink(){
		$id 	=	input('?id') ? intval(input('id')) :false;

		if(!$id){
			foreach ($this->field as $key => $value) {
				$message[$value] 	=	'';
			}
			$boxTitle 	=	'添加新链接';
		}else{
			$message 	=	Model('Links')->get($id);
			if(!$message){
				return $this->error('找不到该链接信息');
			}
			$boxTitle 	=	"编辑已有链接-[ID:{$id}]";
		}

		$this->assign('boxTitle',$boxTitle);
		$this->assign('message',$message);

		return view();
	}
	/*
		*	添加编辑 处理
	*/
	public function newlink_out(){
		$data 		=	input('post.');

		if(empty($data) || !$data){
			return $this->error('未接收到任何数据');
		}else{
			$data 	=	safeArray($data);
		}

		$id  		=	isset($data['id']) ? intval(input('id')) : false;

		if($id){
			unset($data['id']);
			$up 	=	Model('Links')->where('id',$id)->update($data);
			$oper 	=	"修改ID为{$id}的友情链接信息";
		}else{
			$data['addtime'] 	=	time();
			$data['the_user'] 	=	$this->userInfo['id'];
			$up 	=	Model('Links')->insert($data);
			$oper 	=	'添加1条友情链接';
		}

		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('操作成功');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('操作失败！');
		}
	}
	/*
		*	删除一条链接
	*/
	public function dellink(){
		$id 	=	input('?id') ? intval(input('id')) :false;

		if(!$id){
			return $this->error('没有ID信息');
		}

		$up 	=	Model('Links')->where('id',$id)->delete();
		$oper 	=	"删除ID为{$id}的友情链接";

		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('删除成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->error('删除失败！');
		}
	}
}