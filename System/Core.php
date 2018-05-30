<?php
namespace System;
use System\Component\Packet;

abstract class Core{

  private $rootPath = '';

  private $directorySep = '';

  /*
   * @desc : http服务器实例
   */
  private $httpServer = null; 


  /*
   * @desc : tcp服务器实例
   */
  private $tcpServer = null; 
  
  /*
   * @desc : 处理完毕的数据会暂时存储到这里
   */
  protected static $taskData = array();

  /*
   * @desc : swoole各个进程角色的title
   */
  private $processTitle = array(
    'master' => 'Ti service master process',
    'manager' => 'Ti service manager process',
    'worker' => 'Ti service worker process',
    'tasker' => 'Ti service tasker process',
  );

  /*
   * @desc : swoole http服务的配置
   */
  private $httpSetting = array(
    'max_conn' => 100,
    'max_request' => 100,
    'reactor_num' => 2,
    'worker_num' => 2,
    'task_worker_num' => 8,
    'daemonize' => FALSE,
    'package_max_length' => 1024,
    'buffer_output_size' => 1024,
    //'log_file' => $this->rootPath.'System/Log/Ti.log',
    //'pidFile' => $this->rootPath.'Ti.pid',
    //'workerPidFile' => '/home/elarity/ti-rpc/Worker.pid',
    //'taskerPidFile' => '/home/elarity/ti-rpc/Tasker.pid',
    'log_level' => 2,
    'dispatch_mode' => 3,
    'task_ipc_mode' => 1,
    'backlog' => 2000,
    'task_max_request' => 5,
    'host' => '0.0.0.0',
    'port' => 9802,
  );  

  /*
   * @desc : swoole tcp服务的配置
   */
  private $tcpSetting = array(
    'open_length_check' => 1,
    'package_length_type' => 'N',
    'package_length_offset' => 0,
    'package_body_offset' => 4,
    'heartbeat_check_interval' => 60,
    'port' => 9801,
  );

	/*
	 * @desc : 用户自定义配置
	 */
	private $customSetting = array(
	  'tcpPack' => 'length',
	);


  /*
   * @desc : 暂时未开发完毕，服务发现
   */
  private $discoverySetting = array();


  /*
   * @desc : 初始化服务配置
   */
  public function initSetting( array $setting ){
		$this->httpSetting['host'] = $this->getLocalIp();
    $this->httpSetting['log_file'] = $this->rootPath.'System/Log/Ti.log';
    $this->httpSetting['pidFile'] = $this->rootPath.'Ti.pid';
    $this->httpSetting['workerPidFile'] = $this->rootPath.'Worker.pid';
    $this->httpSetting['taskerPidFile'] = $this->rootPath.'Tasker.pid';
    if( isset( $setting['http'] ) ){
      $this->httpSetting = array_merge( $this->httpSetting, $setting['http'] );
    }   
    if( isset( $setting['tcp'] ) ){
      $this->tcpSetting = array_merge( $this->tcpSetting, $setting['tcp'] );
    }   
		if( isset( $setting['discovery'] ) ){
			$this->discoverySetting = $setting['discovery'];
		}
		if( isset( $setting['custom'] ) ){
      $this->customSetting = array_merge( $this->customSetting, $setting['custom'] );
	  }
		// 查看tcp拆包方式
		if( 'eof' == $this->customSetting['tcpPack'] ){
			$this->tcpSetting['open_eof_check'] = true;	
			$this->tcpSetting['package_eof'] = '\r\n';	
			$this->tcpSetting['open_eof_split'] = true;
		}	else if( 'length' == $this->customSetting['tcpPack'] ){
			$this->tcpSetting['open_length_check'] = true;	
			$this->tcpSetting['package_length_type'] = 'N';
			$this->tcpSetting['package_length_offset'] = 0;
			$this->tcpSetting['package_body_offset'] = 4;
		}
  	Packet::setting( array(
			'tcpPack' => $this->customSetting['tcpPack'],
		) );
  }


  /*
   * @desc : worker进程被初始化的时候
   */
  abstract function initWorker( );


  /*
   * @desc : tasker进程被初始化的时候
   */
  abstract function initTasker( \swoole_server $server, $workerId );


  //abstract function initTimer();


  /*
   * @desc : 保留给用户自己实现逻辑
   */
  abstract function process( $server, $param ); 


  /*
   * @desc : 开启服务
   */
	public function run(){
    global $argc, $argv;
		if( $argc <= 1 || $argc > 3 ){
		  $this->_usageUI();
			exit();
		}
    $command = $argv[1];
		$option = isset( $argv[2] ) ? $argv[2] : null ;
		switch( $command ){
		  case 'start':
				if( '-d' === $option ){
					$this->httpSetting['daemonize'] = true;
					$this->_discovery();
				}
				$this->_run();
		  	break;
		  case 'reload':
        $idJson = file_get_contents( $this->httpSetting['pidFile'] );  
				$idArray = json_decode( $idJson, true );
        file_put_contents( $this->httpSetting['workerPidFile'], '' );
        file_put_contents( $this->httpSetting['taskerPidFile'], '' );
				posix_kill( $idArray['managerPid'], SIGUSR1 );
		  	break;
		  case 'status':
				$this->_statusUI();
        if( is_file( $this->httpSetting['workerPidFile'] ) && is_file( $this->httpSetting['taskerPidFile'] ) ){
          //读取所有进程，并列出来
          $idsJson = file_get_contents( $this->httpSetting['pidFile'] );
          $idsArr = json_decode( $idsJson, true );
          $workerPidString = rtrim( file_get_contents( $this->httpSetting['workerPidFile'] ), '|' );
          $taskerPidString = rtrim( file_get_contents( $this->httpSetting['taskerPidFile'] ), '|' );
          $workerPidArr = explode( '|', $workerPidString );
          $taskerPidArr = explode( '|', $taskerPidString );
          foreach( $workerPidArr as $workerPidItem ){
            $tempIdPid = explode( ':', $workerPidItem );
            echo str_pad( $idsArr['masterPid'], 22, ' ', STR_PAD_BOTH ),
                 str_pad( $idsArr['managerPid'], 14, ' ', STR_PAD_BOTH ),
                 str_pad( $tempIdPid[0], 5, ' ', STR_PAD_BOTH ),
                 str_pad( $tempIdPid[1], 12, ' ', STR_PAD_BOTH );
            echo PHP_EOL;
          }
          foreach( $taskerPidArr as $taskerPidItem ){
            $tempIdPid = explode( ':', $taskerPidItem );
            echo str_pad( $idsArr['masterPid'], 22, ' ', STR_PAD_BOTH ),
                 str_pad( $idsArr['managerPid'], 14, ' ', STR_PAD_BOTH ),
                 str_pad( $tempIdPid[0], 5, ' ', STR_PAD_BOTH ),
                 str_pad( $tempIdPid[1], 12, ' ', STR_PAD_BOTH );
            echo PHP_EOL;
          }
        }
		  	break;
		  case 'stop':
				// 删除redis中服务注册的信息
        //$redis = new \Redis();
				//$redis->connect( $this->discoverySetting['host'], $this->discoverySetting['port'] );
				//$redis->hdel( $this->discoverySetting['group'], $this->httpSetting['host'] );
				// 获取pid们
        $idJson = file_get_contents( $this->httpSetting['pidFile'] );  
				$idArray = json_decode( $idJson, true );
				@unlink( $this->httpSetting['pidFile'] );
				@unlink( $this->httpSetting['workerPidFile'] );
				@unlink( $this->httpSetting['taskerPidFile'] );
				posix_kill( $idArray['masterPid'], SIGTERM );
		  	break;
			default:
				$this->_usageUI();
		  	break;
		}
	}


  /*
   * @desc : 开启服务
   */
  private function _run(){
		$this->_statusUI();
    $this->httpServer = new \swoole_http_server( $this->httpSetting['host'], $this->httpSetting['port'] );
    $this->httpServer->set( $this->httpSetting );
    // 开启http服务器
    $this->httpServer->on( 'request', array( $this, 'request' ) );
    $this->httpServer->on( 'workerStart', array( $this, 'workerStart' ) );
    $this->httpServer->on( 'managerStart', array( $this, 'managerStart' ) );
    $this->httpServer->on( 'task', array( $this, 'task' ) );
    $this->httpServer->on( 'finish', array( $this, 'finish' ) );
    $this->httpServer->on( 'start', array( $this, 'start' ) );
    // 开启tcp服务器 
    $this->tcpServer = $this->httpServer->addListener( $this->httpSetting['host'], $this->tcpSetting['port'], SWOOLE_SOCK_TCP );
    $this->tcpServer->set( $this->tcpSetting );
    $this->tcpServer->on( 'connect', array( $this, 'connect' ) );
    $this->tcpServer->on( 'receive', array( $this, 'receive' ) );
    $this->httpServer->start();
  }


  /*
   * @desc : 当swoole服务启动后，回调这里
   */
	public function start( \swoole_server $server ){
    //如果是以daemon形式开启的服务，记录master和manager的进程id
    if( true === $this->httpSetting['daemonize'] ){
      file_put_contents( $this->httpSetting['pidFile'], json_encode(array(
        'masterPid' => $server->master_pid,
        'managerPid' => $server->manager_pid,
      )));
			// 创建tasker进程文件 和 worker进程文件
			// tasker和worker进程的pid将会在workerstart回调中写入到文件中
			touch( $this->httpSetting['workerPidFile'] );
			touch( $this->httpSetting['taskerPidFile'] );
    }
	}	


  public function connect( \swoole_server $server, $fd, $fromId ){
    /*
    当设置dispatch_mode = 1/3时会自动去掉onConnect/onClose事件回调。原因是：
    在此模式下onConnect/onReceive/onClose可能会被投递到不同的进程。连接相关的PHP对象
    数据，无法实现在onConnect回调初始化数据，onClose清理数据
    onConnect/onReceive/onClose 3种事件可能会并发执行，可能会带来异
    */
  }


  /*
   * @desc : 当worker进程被拉起来的时候
   */
  public function workerStart( \swoole_server $server, $workerId ){
    /*
    可以将公用的，不易变的php文件放置到onWorkerStart之前。
    这样虽然不能重载入代码，
    但所有worker是共享的，不需要额外的内存来保存这些数据。
    onWorkerStart之后的代码每个worker都需要在内存中保存一份
    workerId大于配置文件中worker_num的，
    则为task worker进程，反则是普通worker进程
    */
    if( $workerId >= $this->httpSetting['worker_num']){
      swoole_set_process_name( $this->processTitle['tasker'] );
      if( is_file( $this->httpSetting['taskerPidFile'] ) ){
        file_put_contents( $this->httpSetting['taskerPidFile'], $workerId.':'.$server->worker_pid.'|', FILE_APPEND );
      }
      $this->initTasker( $server, $workerId );
    } else {
      swoole_set_process_name( $this->processTitle['worker'] );
      if( is_file( $this->httpSetting['workerPidFile'] ) ){
        file_put_contents( $this->httpSetting['workerPidFile'], $workerId.':'.$server->worker_pid.'|', FILE_APPEND );
      }
      $this->initWorker();
    }
    echo str_pad( $server->master_pid, 22, ' ', STR_PAD_BOTH ),
	       str_pad( $server->manager_pid, 5, ' ', STR_PAD_BOTH ),
         str_pad( $server->worker_id, 14, ' ', STR_PAD_BOTH ),
	       str_pad( $server->worker_pid, 12, ' ', STR_PAD_BOTH );
         echo PHP_EOL;
  }


  /*
   * @desc : swoole manager进程拉起的时候
   */
  public function managerStart( \swoole_server $server ){
    swoole_set_process_name( $this->processTitle['manager'] );
  }


  /*
   * @desc : swoole http服务器收到的请求会打到这里来
   * @param : model 模型模型 
   * @param : method 方法名称
   * @param : param 参数列表
   * @param : requestId
   * @param : type
   */
  public function request( $request, $response ){

    $fd = $request->fd;

    //解析客户端发来的数据
		$rawContent = trim( $request->rawContent() );
    $rawContentArr = Packet::decode( $request->rawContent(), 'http' );
    if( false === $rawContentArr ){
      $response->end( Packet::encode( array(
        'code' => -1,
        'message' => 'Wrong Data Format',
      ), 'http' ) );
      return; 
		}
	   	
		// 组装数据包
		$data = $rawContentArr;
    $data['fd'] = $fd;
		$data['swoole']['header'] = $request->header;
		$data['swoole']['server'] = $request->server;
		$data['rawContent'] = $rawContent;

    /*
    SW : 单个请求,等待结果
    SN : 单个请求,不等待结果
    MW : 多个请求,等待结果
    MN : 多个请求,不等待结果 
    */
    switch( $data['type'] ){
	    // 单个请求,等待结果
      case 'SW':
        $taskId = $this->httpServer->task( $data, -1, function ( $server, $taskId, $resultData ) use ( $response ) {
          $this->onHttpFinished( $server, $taskId, $resultData, $response );
        } );
        self::$taskData[$data['requestId']]['taskKey'][$taskId] = 'single';
        break;
      case 'SN':
        $this->httpServer->task( $data );
        $response->end( Packet::encode( array(
          'code' => 0,
          'message' => '任务投递成功',
        ), 'http' ) );
        break;
	    // 多个请求,等待结果
      case 'MW':
        //$key是客户端自定义的数据name，$item则是具体需要映射的数据
        foreach( $data['param'] as $key => $item ){
          $taskData = array(
            'requestId' => $data['requestId'],
            'fd' => $fd,
            'type' => $data['type'],
            'name' => $key,
            'param' => $item,
          );
          $taskId = $this->httpServer->task( $taskData, -1, function ( $server, $taskId, $resultData ) use ( $response ) {
            $this->onHttpFinished( $server, $taskId, $resultData, $response );
          } );
		      self::$taskData[$data['requestId']]['taskKey'][$taskId] = $key;
        } 
        break;
      case 'MN':
        foreach( $data['param'] as $key=>$item ){
          $taskData = array(
            'requestId' => $data['requestId'],
            'fd' => $fd,
            'type' => $data['type'],
            'param' => $item,
          );
          $taskId = $this->httpServer->task( $taskData ); 
        } 
        $response->end( Packet::encode( array(
          'code' => 0,
          'message' => '任务投递成功',
        ), 'http' ) );
        break;
      default:
        $response->end( Packet::encode( array(
          'code' => -1,
          'message' => 'Wrong Request Type',
        ) ), 'http' );
        break;
    }
    //将fd作为data传给task进程
    return;
  } 


  /*
   * @desc : 当tcp服务器收到数据包后会回调这里
   */
  public function receive( \swoole_server $server, $fd, $fromId, $data ){
    //解析客户端发来的数据
    $data = Packet::decode( $data );
    if( null === $data || false === $data ){
      $server->send( $fd, Packet::encode( array(
        'code' => -1,
        'message' => 'Wrong Data Format',
      ) ) );
      return true; 
    }
    $data['fd'] = $fd;
    /*
    SW : 单个请求,等待结果
    SN : 单个请求,不等待结果
    MW : 多个请求,等待结果
    MN : 多个请求,不等待结果 
    */
    switch( $data['type'] ){
	    // 单个请求,等待结果
      case 'SW':
        $taskId = $server->task( $data );
        self::$taskData[$data['requestId']]['taskKey'][$taskId] = 'single';
        break;
      case 'SN':
        $server->task( $data );
        $server->send( $fd, Packet::encode(array(
          'code' => 0,
          'message' => '任务投递成功',
        ) ) );
        break;
	    // 多个请求,等待结果
      case 'MW':
        //$key是客户端自定义的数据name，$item则是具体需要映射的数据
        foreach( $data['param'] as $key => $item ){
          $taskData = array(
            'requestId' => $data['requestId'],
            'fd' => $fd,
            'type' => $data['type'],
            'name' => $key,
            'param' => $item,
          );
          $taskId = $server->task( $taskData ); 
		      self::$taskData[$data['requestId']]['taskKey'][$taskId] = $key;
        } 
        break;
      case 'MN':
        foreach( $data['param'] as $key=>$item ){
          $taskData=array(
            'requestId' => $data['requestId'],
            'fd' => $fd,
            'type' => $data['type'],
            'param' => $item,
          );
          $taskId = $server->task( $taskData ); 
        } 
        $server->send( $fd, Packet::encode( array(
          'code' => 0,
          'message' => '任务投递成功',
        ) ) );
        break;
      default:
        $server->send( $fd, Packet::encode( array(
          'code' => -1,
          'message' => 'Wrong Request Type',
        ) ) );
        break;
    }
    //将fd作为data传给task进程
    return;
  }


  /*
   * @desc : 异步的task进程池
   */
  public function task( \swoole_server $server, $taskId, $workerId, $data ){
    //data由 id fd type request四个部分组成
    //将处理完成的任务数据，放到taskData数组中，供onFinish函数发送给客户端 
    $send_data = $this->process( $server, $data );
    //使用return触发finish函数并在finish中将数据返回给客户端
    $data['result'] = $send_data;
    return $data;
  }


  /*
   * @desc:投递到task进程中的任务处理完毕的数据再返回给客户端
           是根据客户端投递的方式进行处理的，如果是sw，mw模式
           执行onFinish逻辑的worker进程与下发task任务的worker进程是同一个进程
           finish回调是由worker进程触发的
   */
  public function finish( \swoole_server $server, $taskId, $taskData ){
    // 判断是否为需要取回结果的任务
    if( !isset( self::$taskData[$taskData['requestId']] ) ){
      return;
    }
    $fd = $taskData['fd'];
    //taskData由 id fd type request result四个部分组成
    $taskKey = self::$taskData[$taskData['requestId']]['taskKey'][$taskId];
    unset( self::$taskData[$taskData['requestId']]['taskKey'][$taskId] );
    self::$taskData[$taskData['requestId']]['result'][$taskKey] = $taskData['result'];
    switch( $taskData['type'] ){
      //单个的
      case 'SW':
        $rs = $server->send( $fd, Packet::encode( self::$taskData[$taskData['requestId']]['result']['single'] ) );
        unset( self::$taskData[$taskData['requestId']] );
        return true;
        break;
      //多个的
      case 'MW':
        if( 0 === count( self::$taskData[$taskData['requestId']]['taskKey'] ) ){
          $server->send( $fd, Packet::encode( self::$taskData[$taskData['requestId']]['result'] ) );
          unset( self::$taskData[$taskData['requestId']] );
          return true;
        }else{
          // multi task还没有完成...
        }
        break;
    }
  }


  /*
   * @desc : 当在使用swoole http服务器的时候，异步task进程池执行完毕后，回调这里
   * @param : server
   * @param : taskId 
   * @param : data 
   * @param : response 
   */
  private function onHttpFinished( \swoole_server $server, $taskId, $taskData, $response ){

    // 判断是否为需要取回结果的任务
    if( !isset( self::$taskData[$taskData['requestId']] ) ){
      return;
    }
    $fd = $taskData['fd'];
    //taskData由 id fd type request result四个部分组成
    $taskKey = self::$taskData[$taskData['requestId']]['taskKey'][$taskId];
    unset( self::$taskData[$taskData['requestId']]['taskKey'][$taskId] );
    self::$taskData[$taskData['requestId']]['result'][$taskKey] = $taskData['result'];
    switch( $taskData['type'] ){
      //单个的
      case 'SW':
        $response->end( Packet::encode( self::$taskData[$taskData['requestId']]['result']['single'], 'http' ) );
        unset( self::$taskData[$taskData['requestId']] );
        return true;
        break;
      //多个的
      case 'MW':
        if( 0 === count( self::$taskData[$taskData['requestId']]['taskKey'] ) ){
          $response->end( Packet::encode( self::$taskData[$taskData['requestId']]['result'], 'http' ) );
          unset( self::$taskData[$taskData['requestId']] );
          return true;
        }else{
          // multi task还没有完成...
        }
        break;
    }
  }


  private function _discovery(){
		if( true === $this->httpSetting['daemonize'] ){
	    if( isset( $this->discoverySetting['host'] ) && isset( $this->discoverySetting['port'] ) ){
	      $redis = new \Redis();
	      if( false !== $redis->connect( $this->discoverySetting['host'], $this->discoverySetting['port'] ) ){
			  	$redis->hmset( $this->discoverySetting['group'], array(
			  		$this->httpSetting['host'] => $this->httpSetting['port'].':'.$this->tcpSetting['port'],
			  	) ); 
			  }else{
			    exit('Service Register Fail.'.PHP_EOL); 	
			  } 
			} 
		}else{
		}
  }


	/*
   * @desc : 获取IP地址	
	 */
	protected function getLocalIp(){
		if( '0.0.0.0' == $this->httpSetting['host'] || '127.0.0.1' == $this->httpSetting['host'] ){
			$localIps = swoole_get_local_ip();
			$pattern = array(
        '10\.',
				'172\.1[6-9]\.',
				'172\.2[0-9]\.',
				'172\.31\.',
				'192\.168\.'
			);
			foreach( $localIps as $ipItem ){
			  if(preg_match('#^' . implode('|', $pattern) . '#', $ipItem)) {
					return $ipItem;
				}
			}
		}
		return $this->httpSetting['host'];
	}


  /*
   * @desc : 显示服务状态UI
   */
  private function _statusUI(){
    echo PHP_EOL;
    //打印服务器字幕
    swoole_set_process_name("Ti Master Thread");
    echo PHP_EOL.PHP_EOL.PHP_EOL;
    echo "--------------------------------------------------------------------------".PHP_EOL;
    echo "|                   ------- *     |----  |----   ----                    |".PHP_EOL;
    echo "|                      |    |     |    | |    | |                        |".PHP_EOL;
    echo "|                      |    |     |----  |----  |                        |".PHP_EOL;
    echo "|                      |    |     | \    |      |                        |".PHP_EOL;
    echo "|                      |    |     |   \  |       ----                    |".PHP_EOL;
    echo "--------------------------------------------------------------------------".PHP_EOL;
    echo "\033[1A\n\033[K-----------------------\033[47;30m Ti Server \033[0m-----------------------------\n\033[0m";
    echo "    Version:0.2 Beta, PHP Version:".PHP_VERSION.PHP_EOL;
    echo "         The Server is running on TCP&HTTP".PHP_EOL.PHP_EOL;
    echo "--------------------------\033[47;30m PORT \033[0m---------------------------\n";
    echo "                   HTTP:".$this->httpSetting['port']."  TCP:".$this->tcpSetting['port']."\n\n";
    echo "------------------------\033[47;30m PROCESS \033[0m---------------------------\n";
    echo "      MasterPid---ManagerPid---WorkerId---WorkerPid".PHP_EOL;
  }


  /*
   * @desc : 显示使用方法UI
   */
  private function _usageUI(){
    echo PHP_EOL.PHP_EOL.PHP_EOL;
    echo "--------------------------------------------------------------------------".PHP_EOL;
    echo "|                   ------- *     |----  |----   ----                    |".PHP_EOL;
    echo "|                      |    |     |    | |    | |                        |".PHP_EOL;
    echo "|                      |    |     |----  |----  |                        |".PHP_EOL;
    echo "|                      |    |     | \    |      |                        |".PHP_EOL;
    echo "|                      |    |     |   \  |       ----                    |".PHP_EOL;
    echo "--------------------------------------------------------------------------".PHP_EOL;
    echo 'USAGE: php index.php commond'.PHP_EOL;
    echo '1. start,以debug模式开启服务，此时服务不会以daemon形式运行'.PHP_EOL;
    echo '2. start -d,以daemon模式开启服务'.PHP_EOL;
    echo '3. status,查看服务器的状态'.PHP_EOL;
    echo '4. stop,停止服务器'.PHP_EOL;
    echo '5. reload,热加载所有业务代码'.PHP_EOL.PHP_EOL.PHP_EOL.PHP_EOL;
    exit;
  }
}
