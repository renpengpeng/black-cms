<?php
/*
	@author 	任鹏鹏
	@copyright 	www.black-cms.cn
*/
namespace app\admin\controller;

use app\common\controller\Base;
use think\Session;
use think\Cookie;

class Article extends Base {
	private $setting;
	private $userInfo;
	private $field;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
		$this->field 		=	Model('Articles')->getTableFields();
	}
	public function index(){
		$listNUM 		=	$this->setting['admin_list_num'];

		$page 			=  	input('?page') ? input('page') : 1;

		// 判断是否有type -> 文章状态
		$type 	=	input('?type') ? input('type') : '1';

		// 判断是否有wd
		$wd 	=	input('?wd') ? htmlspecialchars(input('wd')) : '';

		if($type == 'search'){
			$where 	=	" title like '%{$wd}%'";
		}else{
			$where 	=	['status'=>$type];
		}


		// 统计所有文章数量判断page是否超出
		$articleCount 	=	Model('Articles')->where($where)->count();

		if(ceil($articleCount/$listNUM) < $page){
			$page 	=	1;
		}

		// 查找文章
		$listData	 	=	Model('Articles')
								->where($where)
								->order('id desc')
								->page($page)
								->limit($listNUM)
								->select();

		// 循环获取分类名称
		if($listData){
			$listData 	=	$listData->toArray();
			if(count($listData) >= 1){
				foreach ($listData as $key => $value) {
					$catId 		=	$listData[$key]['the_cate'];
					// 查找分类
					$findCate 	=	Model('Cates')->field('id,title')->where('id',$catId)->find();
					if($findCate){
						$findCate 	=	$findCate->toArray();
						$listData[$key]['catename'] 	=	$findCate['title'];
					}else{
						$listData[$key]['catename'] 	=	'无分类';
					}
				}
			}
		}

		// 分页
		if($type 	==	'search'){
			$pageData 		=	Model('Articles')->where($where)->paginate([
									'list_rows'	=>	$listNUM,
									'query' 	=>	$wd
								]);
		}else{
			$pageData 		=	Model('Articles')->where($where)->paginate($listNUM);
		}
		

		$boxTitle 			=	getStatusOfBoxTitle($type,$wd);

		$this->assign('type',$type);
		$this->assign('boxTitle',$boxTitle);
		$this->assign('listData',$listData);
		$this->assign('pageData',$pageData);
		return view();
	}
	/*
		*	操作文章
	*/
	public function change(){
		$idArr 	=	input('?id') ? htmlspecialchars(input('id')) : false;
		$type 	=	input('?type') ? htmlspecialchars(input('type')) : false;

		// 返回false
		if(!$idArr || !$type){
			return $this->error('操作失败！');
		}else{
			// 统计操作文章数量
			$countOperArr 	=	explode( ',',$idArr);
			foreach ($countOperArr as $key => $value) {
				if(empty($value)){
					unset($countOperArr[$key]);
				}
			}
			$countOper 		=	count($countOperArr);
		}

		$id 	=	"id in (".$idArr.")";

		// 根据type来操作
		switch ($type) {
			// 删除
			case 'del':
				$change 	=	Model('Articles')->where($id)->delete();
				$oper 		=	"删除{$countOper}篇文章";
			break;
			// 转换为未审核
			case 'status':
				$change 	=	Model('Articles')->where($id)->update(['status'=>0]);
				$oper 		=	"将{$countOper}篇文章转换为审核";
			break;
			// 转换草稿
			case 'draft':
				$change 	=	Model('Articles')->where($id)->update(['status'=>2]);
				$oper 		=	"将{$countOper}篇文章转换为草稿";
			break;
			// 移动到回收站
			case 'recy':
				$change 	=	Model('Articles')->where($id)->update(['status'=>3]);
				$oper 		=	"将{$countOper}篇文章移动到回收站";
			break;
			// 恢复正常
			case 'normal':
				$change 	=	Model('Articles')->where($id)->update(['status'=>1]);
				$oper 		=	"将{$countOper}篇文章转换为正常";
			break;
			default:
				$change 	=	false;
			break;
		}

		if($change){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('操作成功');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('操作失败');
		}

	}
	/*
		*	添加文章 (视图)
	*/
	public function newarticle(){
		// 获取所有分类
		$allCate 		=	getAllCateForOptionAdmin();

		// 如果有id 则查找
		$id 			=	input('?id') ? intval(input('id')) : false;
		if($id){
			$message 	=	Model('Articles')->get($id);
			if(!$message){
				return $this->error('找不到相应的文章');
			}

			$message 	=	$message->toArray();
			$message 	=	nosafeArray($message);
		}else{
			foreach ($this->field as $value) {
				$message[$value] 	=	'';
			}
		}

		if(!empty($message['the_cate'])){
			$findCate 	=	Model('Cates')->field('id,title')->where('id',$message['the_cate'])->find();
			if(!$findCate){
				$message['catename'] 	=	'';
			}else{
				$findCate 				=	$findCate->toArray();
				$message['catename'] 	=	$findCate['title'];
			}
		}



		$this->assign('message',$message);
		$this->assign('allCate',$allCate);
		return view();
	}
	/*
		*	添加文章 (执行)
	*/
	public function newarticle_out(){
		$data 		=	input('post.');

		if(!$data || empty($data)){
			return $this->error('没有接收到数据');
		}

		$data 		=	safeArray($data);

		$id 		=	isset($data['id']) ? intval($data['id']) : false;
		// 添加会员ID
		$data['the_user'] 			=	$this->userInfo['id'];

		// 如果有id  则为更新文章 否则则为添加文章
		if($id){
			unset($data['id']);
			$data['last_modify'] 	=	@time();
			$up 					=	Model('Articles')->where('id',$id)->update($data);
			if($up){
				$version				=	Model('Articles')->where('id',$id)->setInc('resivion');
			}
			$oper 					=	"修改文章ID为{$id}的内容";
		}else{
			$data['addtime'] 		=	@time();
			$up 					=	Model('Articles')->insert($data);
			$oper 					=	"新添加一篇文章";
		}


		if($up){
			addOperData($this->userInfo['id'],'admin',$oper,1);
			return $this->success('操作成功！');
		}else{
			addOperData($this->userInfo['id'],'admin',$oper,0);
			return $this->error('操作失败!');
		}
	}
}
?>