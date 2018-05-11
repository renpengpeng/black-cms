<?php
/*
	@Author 	任鹏鹏
	@Copyright	www.black-cms.cn
*/
namespace app\admin\controller;

use app\common\controller\Base;
use think\Session;
use think\Cookie;
use think\Requset;

class Cate extends Base {
	private $setting;
	private $userInfo;

	public function _initialize(){
		checkLogin();
		checkAuthAdmin();

		$this->setting 		=	getSetting();
		$this->userInfo 	=	Session::get('login_data');
	}
	public function index(){
		// 获取所有分类LI形式
		$allCate 			=	getAllCateForLiAdmin();

		// 获取所有分类option形式
		$allCateForOption 	=	getAllCateForOptionAdmin();

		$this->assign('allCate',$allCate);
		$this->assign('allCateOption',$allCateForOption);
		return view();
	}
	/*
		*	删除分类
	*/
	public function del(){
		$id 	=	input('?post.id') ? intval(input('post.id')) : false;
		if(!$id){
			return $this->error('id值不正确！');
		}

		// 如果为多个数组遍历查询子分类
		if(strripos($id, ',')){
			$idArr 		=	[];
			$id 		=	explode(',',$id);
			foreach ($id as $key => $value) {
				$idArr 	=	array_merge($idArr,findNextCateAll($value));
			}
			$idArr 		=	array_unique($idArr);
		}else{
			// 查询子分类
			$idArr 		=	[];
			$idArr 		=	array_merge($idArr,findNextCateAll($id));
			$idArr 		=	array_unique($idArr);
		}
		$idArr 			=	array_values($idArr);
		// $where			=	'('.implode(',', $idArr).')';
		$where 			=	$idArr;


		// 开始移动分类到回收站
		$del			=	Model('Cates')->where('id','in',$where)->fetchSql(true)->update(['status'=>'0']);
		if(!$del){
			return $this->error('删除分类失败');
		}
		// 统计文章数量
		$cateArticle 	=	Model('Articles')->where('the_cate','in',$where)->count();

		if($cateArticle !=0){
			$delArticle =	Model('Articles')->where('the_cate','in',$where)->update(['status'=>3]);
		}else{
			$delArticle =	true;
		}

		// 如果移动文章失败 则分类还原 否则分类
		if(!$delArticle){
			// 分类移动
			$redu 		=	Model('Cates')->where('id','in',$where)->update(['status'=>1]);

			if($redu){
				return $this->error('删除失败！因为分类下的文章没有成功移动到回收站。');
			}else{
				return $this->error('糟糕了，分类已经到回收站了，但是文章没有成功移动到回收站！请手动操作恢复分类');
			}
		}else{
			// 分类删除
			$redu 		=	Model('Cates')->where('id','in',$where)->delete();

			if($redu){
				return $this->success('分类删除成功！分类下的文章已经移动到回收站。');
			}else{
				return $this->error('糟糕，因为分类删除失败，造成分类和分类下的文章都进回收站了！请手动恢复！');
			}
		}
		
	}
	/*
		*	添加新分类
	*/
	public function newcate(){
		$data 	=	input('post.');

		foreach ($data as $key => $value) {
			$value 	=	htmlspecialchars($value);
			if(empty($value)) {
				unset($data[$key]);
			}
		}

		// 判断graden
		if(isset($data['pid'])){
			$pGraden 		=	Model('Cates')->field('id,grade')->where('id',$data['pid'])->find()->toArray();
			$pGraden 		=	$pGraden['grade'];
			$data['grade'] 	=	intval($pGraden)+1;
		}else{
			$data['pid'] 	=	0;
			$data['grade'] 	=	1;
		}

		// 开始添加
		$insert 	=	Model('Cates')->insert($data);

		if($insert){
			return $this->success('添加分类成功');
		}else{
			return $this->error('添加分类失败');
		}
	}
	/*
		*	get_one
		*	获取单个分类信息
	*/
	public function get_one(){
		$id 		=	input('?id') ? intval(input('id')) : false;

		if(!$id){
			return $this->error('ID传入错误');
		}

		$get 		=	Model('Cates')->get($id);
		if($get){
			$get 	=	$get->toArray();
			return $this->success($get);
		}else{
			return $this->error('获取分类信息失败');
		}
	}
	/*
		*	分类编辑提交
	*/
	public function edit(){
		$data 		=	input('post.');

		foreach ($data as $key => $value) {
			$value 	=	htmlspecialchars($value);
			if(empty($value)){
				unset($data[$key]);
			}
		}

		if(isset($data['id'])){
			$id 	=	$data['id'];
			unset($data['id']);
		}else{
			return $this->error('没有获取到相应ID');
		}

		$up 		=	Model('Cates')->where('id',$id)->update($data);

		if($up){
			return $this->success('更新分类成功');
		}else{
			return $this->error('更新分类失败');
		}
	}
}
?>