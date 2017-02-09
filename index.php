<?php
//添加命令行检测
if('cli'!=php_sapi_name()){
  exit('The server must be run under CLI SAPI');
}
define('DS',DIRECTORY_SEPARATOR);
define('ROOT',__DIR__);
define('SERVER',ROOT.DS.'Server');
//载入常用函数
require_once SERVER.DS.'Library'.DS.'Function.php';
//ti-rpc config file
require_once SERVER.DS.'Config'.DS.'Ti.conf';
//使用spl autoload register注册自定义自动加载函数
spl_autoload_register('autoload');
//开启服务
if(!isset($argv[1])){
  exit('arguments:start|stop|status'.PHP_EOL);
}
//启动服务
$action=end($argv);
$argument_count=count($argv);
if('start'===$action){
  //判断有没有-d参数
  $config['ti']['daemonize']='-d'===$argv[$argument_count-2]?true:false;
  //开启TCP服务
  $server=new \Server\TcpServer($config);
  $server->start();
}else if('stop'===$action){
  $pid_string=file_get_contents($config['ti']['pid_file']);
  $pid_array=explode(',',$pid_string);
  $master_pid=$pid_array[0];
  $manager_pid=$pid_array[1];
  if(posix_kill($master_pid,SIGTERM)){
    echo '服务已停止'.PHP_EOL;
  }else{
    echo '服务停止失败'.PHP_EOL;
  }
}else if('reload'===$action){
  $pid_string=file_get_contents($config['ti']['pid_file']);
  $pid_array=explode(',',$pid_string);
  $master_pid=$pid_array[0];
  $manager_pid=$pid_array[1];
  if(posix_kill($master_pid,SIGUSR1)){
    echo '重启所有worker进程成功'.PHP_EOL;
  }else{
    echo '重启所有worker进程失败'.PHP_EOL;
  } 
}else{
  echo "arguments:start|stop|reload".PHP_EOL;
}
