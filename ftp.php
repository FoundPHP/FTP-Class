<?php
/*	(C)2005-2021 FoundPHP Framework.
*	   name: Ease Template
*	 weburl: http://www.FoundPHP.com
* 	   mail: master@FoundPHP.com
*	 author: 孟大川
*	version: 1.21.0222
*	  start: 2017-02-24
*	 update: 2021-02-22
*	payment: Free 免费
*	This is not a freeware, use is subject to license terms.
*	此软件为授权使用软件，请参考软件协议。
*	http://www.foundphp.com/?m=agreement

示例：

$set	= array(
		'host'			=> '127.0.0.1',			//服务器
		'port'			=> '21',				//端口
		'ssh'			=> 1,					//ssh 1启动，0关闭
		'username'		=> 'test',				//帐号
		'password'		=> '123123',			//密码
		'passive'		=> 1,					//被动模式：1启动，0关闭
		'timeout'		=> '3',					//超时时长
		'language'		=> 'cn',				//语言包
		'logs'			=> 'ftp_log.txt',		//日志
);
$ftp	= new FoundPHP_ftp($set);

//获取目录
$dirs	= $ftp->get_dir('/');
print_r($dirs);

//上传文件
$ftp->put('a1.jpg','113.jpg');

//下载文件
$ftp->get('113.jpg','a12.jpg');

//删除文件
$ftp->del('113.jpg');

//建立文件夹
$ftp->mk_dir('test/1111');

//删除目录
$ftp->rm_dir('test/1111');

//改名
//$ftp->name('112.jpg','a.jpg');

//关闭链接
$ftp->close();

*/

class FoundPHP_ftp{
	public $ssh			= 0;			//普通ftp:0，ssh ftp:1
	public $links 		= '';			//链接库
	public $host 		= '';			//ftp地址
	public $user 		= '';			//ftp帐号
	public $logs 		= 'logs.txt';	//日志文件
	public $language 	= 'cn';			//语言
	public $lang		= array();
	//构造快递方法
	function __construct($ary=array()) {
		//默认端口
		if ((int)$ary['port']==0) {
			$ary['port']	= 21;
		}
		//默认超时
		if ((int)$ary['timeout']==0) {
			$ary['timeout']	= 5;
		}
		//服务器
		$this->host		= $ary['host'];
		$this->user		= $ary['username'];
		//载入语言包
		if ($ary['language']!=''){
			$this->language	= $ary['language'];
		}
		//日志位置
		if ($ary['logs']!=''){
			$this->logs	= $ary['logs'];
		}
		//服务器的连接模式
		switch((int)$ary['ssh']){
			//ssh加密模式
			case 1:
				$methods = array(
					'hostkey'=>'ssh-rsa,ssh-dss',
					'client_to_server' => array(
						'crypt'		=> 'aes256-ctr,aes192-ctr,aes128-ctr,aes256-cbc,aes192-cbc,aes128-cbc,3des-cbc,blowfish-cbc',
						'comp'		=> 'none'
					),
					'server_to_client' => array(
						'crypt'		=> 'aes256-ctr,aes192-ctr,aes128-ctr,aes256-cbc,aes192-cbc,aes128-cbc,3des-cbc,blowfish-cbc',
						'comp'		=> 'none'
					)
				);
				//连接服务器
				$this->connect = ssh2_connect($ary['host'],$ary['port'],$methods);
				if (!$this->connect){
					$error	= $this->lang('host_not_existent1').$ary['host'].$this->lang('host_not_existent2');
					$this->logs($error);
					function_exists('found_shutdown')?found_shutdown($error):die($error);
				}
				$this->logs($ary['host'].$this->lang('host_connect'));
				//登录
				if (@ssh2_auth_password($this->connect, $ary['username'], $ary['password'])) {
					$this->logs($ary['username'].$this->lang('login_connect'));
					$this->links		=	ssh2_sftp($this->connect);
				} else {
					$error	= $ary['username'].$this->lang('login_error');
					$this->logs($error);
					function_exists('found_shutdown')?found_shutdown($error):die($error);
				}
				//连接模式
				$this->ssh		= 1;
			break;
			//标准FTP
			default:
				
				//连接服务器
				$this->links	= ftp_connect ($ary['host'],$ary['port'],$ary['timeout']);
				if (!$this->links){
					$error	= $this->lang('host_not_existent1').$ary['host'].$this->lang('host_not_existent2');
					$this->logs($error);
					function_exists('found_shutdown')?found_shutdown($error):die($error);
				}
				$this->logs($ary['host'].$this->lang('host_connect'));
				
				//被动模式
				if ($ary['passive']==1){
					ftp_pasv($this->links, true);
					$this->logs($this->lang('passive'));
				}
				
				//登录
				if (@ftp_login($this->links, $ary['username'], $ary['password'])) {
					$this->logs($ary['username'].$this->lang('login_connect'));
				} else {
					$error	= $ary['username'].$this->lang('login_error');
					$this->logs($error);
					function_exists('found_shutdown')?found_shutdown($error):die($error);
				}
				//连接模式
				$this->ssh		= 0;
		}
	}
	
	function lang($id=''){
			$ftplang	= dirname(__FILE__).'/language/ftp_'.$this->language.'.php';
			if (is_file($ftplang)){
				include_once($ftplang);
				$lang	= $GLOBALS['FoundPHP_FTP_Lang'];
			}else{
				$lang  		= array(
					'sorry'					=> '抱歉,',
					'host_connect'			=> ' 服务器连接成功。',
					'host_not_existent1'	=> '无法连接 ',
					'host_not_existent2'	=> '，请检查配置参数。',
					'login_connect'			=> ' 登录成功。',
					'login_error'			=> ' 登录失败或密码错误。',
					'close'					=> ' 服务器连接关闭。',
					'not_file'				=> '没有找到上传文件：',
					'put_end'				=> '上传成功：',
					'put_error'				=> '没有权限或上传失败：',
					'get_end'				=> '下载成功：',
					'get_error'				=> '没有权限、文件错误或下载失败：',
					'del_end'				=> '删除文件成功: ',
					'del_error'				=> '删除文件失败或不存在: ',
					'mkdir_end'				=> '目录建立成功：',
					'mkdir_error'			=> '目录建立失败：',
					'rmdir_end'				=> '目录删除成功：',
					'rmdir_error'			=> '目录删除失败或不存在：',
					'rmname_set'			=> '未设置旧名称与新名称',
					'rmname_end'			=> '改名成功：',
					'rmname_error'			=> '改名失败，目录或文件不存在：',
					'passive'				=> '开启被动模式',
				);
			}
		return $lang[$id];
	}
	
	/*获得指定目录
		dirs 目录地址，根目录/
	*/
	function get_dir($dirs='/'){
		if ($this->ssh==1){
			//获取根目录
			if ($dirs=='/'){
				$dirs	= './';
			}
			$dirHandle = opendir("ssh2.sftp://$this->links/$dirs");
			while (false !== ($file = readdir($dirHandle))) {
				$statinfo = ssh2_sftp_stat($this->links, $dirs.'/'.$file);
				if ($file != '.' && $file != '..' && !empty($file)) {
					if (!stristr($file,'.')){
						$info['name']		= iconv('gbk','UTF-8',$file);
						$result['dir'][]	= $info;
					}else{
						$info['name']		= iconv('gbk','UTF-8',$file);
						//$info['size']		= filesize("ssh2.sftp://$this->links/$dirs/$file");
						$result['file'][]	= $info;
					}
				}
			}
		}else{
			$get_list	= ftp_rawlist($this->links, $dirs);
			if ($get_list){
				foreach($get_list AS $k=>$v) {
					$fileinfo	= preg_split("/[\s]+/", $v, 9);
					if ($fileinfo[8] != '.' && $fileinfo[8] != '..' && !empty($fileinfo[8])) {
						//文件夹
						if (!stristr($fileinfo[8],'.')){
							$info['name']	= iconv('gbk','UTF-8',$fileinfo[8]);
							$info['chmod']	= $fileinfo[0];
							$info['owner']	= $fileinfo[2];
							$info['group']	= $fileinfo[3];
							$info['day']	= $fileinfo[5].' '.$fileinfo[6];
							$info['time']	= $fileinfo[7];
							$result['dir'][] = $info;
						}else{
						//文件
							$info['name']	= iconv('gbk','UTF-8',$fileinfo[8]);
							$info['chmod']	= $fileinfo[0];
							$info['owner']	= $fileinfo[2];
							$info['group']	= $fileinfo[3];
							$info['size']	= $fileinfo[4];
							$info['day']	= $fileinfo[5].' '.$fileinfo[6];
							$info['time']	= $fileinfo[7];
							$result['file'][] = $info;
						}
					}
				}
			}
		}
		return $result;
	}
	
	
	/*上传文件
		file_name	本地文件（目录配文件）
		save_name	远程文件（目录配文件）
	*/
	function put($file_name='',$save_name=''){
		if (!is_file($file_name)){
			$error	= $this->lang('sorry').$this->lang('not_file').$file_name;
			$this->logs($error);
			function_exists('found_shutdown')?found_shutdown($error):die($error);
		}
		
		if ($this->ssh==1){
			if (copy($save_name,"ssh2.sftp://{$this->links}".$file_name)) {
				$this->logs($this->lang('get_end').$file_name.' => '.$save_name);
				return array('code'=>1,'file_name'=>$save_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('put_error').$file_name.' => '.$save_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}else{
			
			if (ftp_put($this->links, $save_name, $file_name, FTP_BINARY)) {
				$this->logs($this->lang('put_end').$file_name.' => '.$save_name);
				return array('code'=>1,'file_name'=>$save_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('put_error').$file_name.' => '.$save_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}
	}
	
	/*下载文件
		file_name	远程文件（目录配文件）
		save_name	本地文件（目录配文件）
	*/
	function get($file_name='',$save_name=''){
		if ($save_name==''){
			$save_name	= $file_name;
		}
		
		if ($this->ssh==1){
			if (copy("ssh2.sftp://$this->links/$file_name", $save_name)) {
				$this->logs($this->lang('get_end').$file_name.' => '.$save_name);
				return array('code'=>1,'file_name'=>$save_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('get_error').$file_name.' => '.$save_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}else{
			if (ftp_get($this->links, $save_name, $file_name, FTP_BINARY)) {
				$this->logs($this->lang('get_end').$file_name.' => '.$save_name);
				return array('code'=>1,'file_name'=>$save_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('get_error').$file_name.' => '.$save_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}
	}
	
	/*删除文件
		file_name	远程文件（目录配文件）
	*/
	function del($file_name=''){
		if ($this->ssh==1){
			if (ssh2_sftp_unlink($this->links, $file_name)) {
				$this->logs($this->lang('del_end').$file_name);
				return array('code'=>1,'file_name'=>$file_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('del_error').$file_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}else{
			if (ftp_delete($this->links, $file_name)) {
				$this->logs($this->lang('del_end').$file_name);
				return array('code'=>1,'file_name'=>$file_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('del_error').$file_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}
	}
	
	/*建立文件夹
		name	文件夹名称
	*/
	function mk_dir($name=''){
		if ($this->ssh==1){
			if (ssh2_sftp_mkdir($this->links, $name,0777)) {
				$this->logs($this->lang('mkdir_end').$name);
				return array('code'=>1,'dir_name'=>$name);
			} else {
				$error	= $this->lang('sorry').$this->lang('mkdir_error').$name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}else{
			if (ftp_mkdir($this->links, $name)) {
				$this->logs($this->lang('mkdir_end').$name);
				return array('code'=>1,'dir_name'=>$name);
			} else {
				$error	= $this->lang('sorry').$this->lang('mkdir_error').$name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}
	}
	
	/*删除文件夹
		name	文件夹名称
	*/
	function rm_dir($name=''){
		if ($this->ssh==1){
			if (@ssh2_sftp_rmdir($this->links, $name)) {
				$this->logs($this->lang('rmdir_end').$name);
				return array('code'=>1,'dir_name'=>$name);
			} else {
				$error	= $this->lang('sorry').$this->lang('rmdir_error').$name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}else{
			if (@ftp_rmdir($this->links, $name)) {
				$this->logs($this->lang('rmdir_end').$name);
				return array('code'=>1,'dir_name'=>$name);
			} else {
				$error	= $this->lang('sorry').$this->lang('rmdir_error').$name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}
	}
	
	/*目录或文件改名
		name	文件夹、文件名称
	*/
	function name($old_name='',$new_name=''){
		if (trim($old_name)=='' || trim($new_name)==''){
			$error	= $this->lang('sorry').$this->lang('rmname_set');
			$this->logs($error);
			function_exists('found_shutdown')?found_shutdown($error):die($error);
		}
		if ($this->ssh==1){
			if (@ssh2_sftp_rename($this->links, $old_name,$new_name)) {
				$this->logs($this->lang('rmname_end').$old_name.' => '.$new_name);
				return array('code'=>1,'new_name'=>$new_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('rmname_error').$old_name.' => '.$new_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}else{
			if (@ftp_rename($this->links, $old_name,$new_name)) {
				$this->logs($this->lang('rmname_end').$old_name.' => '.$new_name);
				return array('code'=>1,'new_name'=>$new_name);
			} else {
				$error	= $this->lang('sorry').$this->lang('rmname_error').$old_name.' => '.$new_name;
				$this->logs($error);
				function_exists('found_shutdown')?found_shutdown($error):die($error);
			}
		}
	}
	
	//关闭FTP
	function close(){
		$this->logs($this->host.$this->lang('close')."\r\n----------------------------------------\r\n");
		if ($this->ssh==0){
			ftp_close($this->links);
		}
	}
	
	//日志
	function logs($msg=''){
		if ($msg){
			$data	= dates(time())."\t".$msg."\r\n";
			$GLOBALS['tpl']->writer($this->logs,$data,'a+');
		}
	}
}