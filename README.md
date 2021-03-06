# FTP-CLASS
采用 PHP 开发的可以支持FTP与FTPS类，简单易用，可以实现上传、下载、建立目录、获取目录、删除及改名等。

```php

<?php

//示例：
$set	= array(
	'host'			=> '127.0.0.1',			//服务器
	'port'			=> '21',			//端口
	'ssh'			=> 1,				//ssh 1启动，0关闭
	'username'		=> 'test',			//帐号
	'password'		=> '123123',			//密码
	'passive'		=> 1,				//被动模式：1启动，0关闭
	'timeout'		=> '3',				//超时时长
	'language'		=> 'cn',			//语言包
	'logs'			=> 'ftp_log.txt',		//日志
);


$ftp	= new FoundPHP_ftp($set);
//获取目录
$dirs	= $ftp->get_dir('/');
print_r($dirs);

//上传文件
//put($file_name,$save_name);
//file_name	本地文件（目录配文件）
//save_name	远程文件（目录配文件）
$ftp->put('a1.jpg','113.jpg');

//下载文件
//get($file_name,$save_name);
//file_name	远程文件（目录配文件）
//save_name	本地文件（目录配文件）
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
```
