<?php
namespace app\index\controller;

use app\common\controller\Base;
use think\Session;
use think\Cookie;
use think\Config;

class Upload extends Base{
	private $setting;
	private $suffix;
	private $size;
	private $encry;
	private $validate;
	private $uploadPATH;
	private $givePath;

	public function _initialize(){
		$this->setting 		=	getSetting();

		$this->suffix 		=	empty($this->setting['upload_suffix']) ? 'jpg,png,gif' : $this->setting['upload_suffix'];
		$this->encry 		=	empty($this->setting['upload_crypt'])  ? 'md5' 	: $this->setting['upload_crypt'];
		$this->size 		=	empty($this->setting['upload_size'])   ? 1048576 : $this->setting['upload_size'];

		$this->validate 	=	['size'=>$this->size,'ext'=>$this->suffix];
		$this->uploadPATH 	=	Config::get('bcms.upload_path');
		$this->givePath 	=	Config::get('bcms.upload_give_path');

		@chmod($this->uploadPATH, 0666);
	}
	/*
		*	上传函数
	*/
	public function upload(){

		$files 		= 	request()->file();

		$count 		=	count($files);

		if($count <= 0){
			return json(['errno'=>1]);
		}


		// if($count >= 2){
		$result 	=	[];
		foreach($files as $file){
			$info = $file->rule($this->encry)->validate($this->validate)->move($this->uploadPATH);
			if($info){
				array_push($result, $this->givePath.$info->getSaveName());
			}else{
				array_push($result, $this->givePath.'error.jpg');
			}

		}
		$jresult 	=	['errno'=>0,'data'=>$result];
		return json($jresult);

		// }else{
		// 	// 移动到框架应用根目录/public/uploads/ 目录下
		// 	$info = $files->rule($this->encry)->validate($this->validate)->move($this->uploadPATH);
		// 	if($info){
				
		// 		$result 	=	[
		// 			'errno' 	=>	0,
		// 			'data' 		=>	[
		// 				'/static/upload'.DS.$info->getSaveName()
		// 			],
		// 		];
				
		// 	}else{
		// 		$result 	=	[
		// 			'errno' 	=>	1
		// 		];
		// 	}

		// 	return $result;
		// }	
	}
}
?>