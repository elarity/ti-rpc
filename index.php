<?php

if( 'cli' !== php_sapi_name() ){	
	exit( '服务只能运行在cli sapi模式下'.PHP_EOL );
}

// 定义系统常量
define( 'DS', DIRECTORY_SEPARATOR );
define( 'ROOT', __DIR__.DS );

// 载入系统函数库
require_once ROOT."System".DS."Library".DS."Function.php";

require_once ROOT.'vendor'.DS.'autoload.php';

// 自定义autoload方法
/*
function autoload( $class ){
  $includePath = str_replace( '\\', DS, $class );
  $targetFile = ROOT.$includePath.'.php';
  require_once( $targetFile );
}
spl_autoload_register( 'autoload' );
 */

// 继承Core父类
class Gmu extends System\Core{

  // 拉起worker进程前需要做的初始化工作
  public function initWorker(){}

  // 拉起tasker进程前需要做的初始化工作
  // 比如初始化数据库类库
  // 比如初始化其他类库
  public function initTasker( \swoole_server $server, $workerId ){
		$di = \System\Component\Di::getInstance();
		$di->set( 'mysql', \System\Library\Mysql::class, [] );
		$di->set( 'cacheRedis', \System\Library\CacheRedis::class, [] );
		//$di->set( 'aes', \System\Library\Aes::class, [] );
		//$di->set( 'validator', \System\Library\Valitron\Validator::class, [] );
  }

  // 具体业务逻辑
  public function process( $server, $param ){

    // 将param抛给model中的method，并获得到处理完后的数据
		$targetModel = '\Controller\\'.ucfirst( $param['param']['model'] );
    $targetModel = new $targetModel; 
		$targetConfig['param'] = $param['param']['param'];
    $sendData = call_user_func_array( array( $targetModel, $param['param']['method'] ), array( $targetConfig ) );
		return $sendData;

  }

}

$gmu = new Gmu;

// 开启一些配置项
$gmu->initSetting(array(
	'http' => array(
		'host' => '0.0.0.0',
	),
	'tcp' => array(
	),
));
$gmu->run();
