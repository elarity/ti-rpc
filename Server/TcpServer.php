<?php
namespace Server;
/*
 * @description:ti-rpc basement
 */
class TcpServer{
  private $config=[];
  private $server=null;
  private $message=[];
  public function __construct(array $config){
    //载入配置文件
    $this->config=$config;
    //create tcp server
    $this->server=new \swoole_server($config['server']['host'],$config['server']['port']);
    $this->server->set($config['ti']);
    //worker进程开始
    $this->server->on('workerStart',array($this,'work_start'));
    //有连接
    $this->server->on('connect',array($this,'connect'));
    //接受到数据
    $this->server->on('receive',array($this,'receive'));
    //manager进程开始
    $this->server->on('managerStart',array($this,'manager_start'));
    //task进程开始
    $this->server->on('task',array($this,'task'));
    //task进程结束
    $this->server->on('finish',array($this,'finish'));
    //swoole内部启动完毕，master进程的主线程回调该函数
    $this->server->on('start',array($this,'swoole_start'));
  }
  /*
   * @description:manager process start
   */
  public function manager_start(\swoole_server $server){
    swoole_set_process_name("JIMU Manager Process");
  }
  /*
   * @description:work process start
   */
  public function work_start(\swoole_server $server,$worker_id){
    //重命名进程名
    $this->_rename_process($server,$worker_id);
    //添加定时器
    if(0===$worker_id){
      swoole_timer_tick(1000,function() use ($server){
        //echo count($server->connections).PHP_EOL;
      });
    }
    if($worker_id>=$server->setting['worker_num']){
      //mysql与redis连接池
      $this->pdo=new \Server\Library\Mysql($this->config['mysql']);
      $redis=new \Redis();
      $redis->connect($this->config['redis']['host'],$this->config['redis']['port']);
      $redis->select($this->config['redis']['host_number']);;
      $this->redis=$redis;
      $this->validator=new \Server\Library\Sirius\Validation\Validator;
    }
  }
  /*
   * @description:重命名进程
   */
  private function _rename_process(\swoole_server $server,$worker_id){
    $worker_num=isset($server->setting['worker_num'])?$server->setting['worker_num']:1; 
    $task_num=isset($server->setting['task_worker_num'])?$server->setting['task_worker_num']:0;
    if($worker_id>=$worker_num){
      swoole_set_process_name("JIMU Task Process");
    }else{
      swoole_set_process_name("JIMU Worker Process");
    }
    echo str_pad($server->master_pid,10,' ',STR_PAD_BOTH),
         str_pad($server->manager_pid,12,' ',STR_PAD_BOTH),
	 str_pad($server->worker_id,14,' ',STR_PAD_BOTH),
 	 str_pad($server->worker_pid,10,' ',STR_PAD_BOTH);
    echo PHP_EOL;
  }
  /*
   * @description:connect
   * @access:private
   */
  public function connect(\swoole_server $server,$fd,$from_id){
    //print_r($server->setting);
  }
  /*
   * @description:connect
   * @access:private
   */
  public function receive(\swoole_server $server,$fd,$from_id,$data){
    //将fd作为data传给task进程
    $task_data['data']=$data;
    $task_data['fd']=$fd;
    $server->task($task_data);
    return;
  }
  /*
   * @description:task process begin
   * @access:public
   */
  public function task(\swoole_server $server,$task_id,$from_id,$task_data){
    $fd=$task_data['fd'];
    $data=$task_data['data'];
    //解析客户端传来的参数,去掉结尾\r\n,然后将json转为php array
    $data=str_replace('\r\n','',$data);
    $data=json_decode(trim($data),TRUE);
    $data['fd']=$fd;
    //获取该tcp连接信息
    $connection=$server->connection_info($data['fd']);
    //data数组由 type request fd 三项数据组成
    if('single'===$data['type']){
      //验证请求方法是否存在
      if(FALSE===method_exists('\Server\Module\\'.$data['request']['model'],$data['request']['method'])){
        $send_data=array(
          'code'=>404,
          'message'=>'Method not exists.',
          'data'=>''
        );
        $send_data=json_encode($send_data).'\r\n';
        $send_result=$server->send($data['fd'],$send_data);
        return;
      }
      //将param抛给model中的method，并获得到处理完后的数据
      try{
        $param['param']=$data['request']['param'];
        $param['pdo']=$this->pdo;
        $param['redis']=$this->redis;
        $send_data=call_user_func_array(array('\Server\Module\\'.$data['request']['model'],$data['request']['method']),array($param));
        $send_data=json_encode($send_data).'\r\n';
        $send_result=$server->send($data['fd'],$send_data);
      }catch(Exception $e){
        $code=$e->getCode()?$e->getCode():500;
        $send_data=array(
          'code'=>$code,
          'message'=>'系统被刘波吃了',
        );
        $send_data=json_encode($send_data).'\r\n';
        $send_result=$server->send($data['fd'],$send_data);
      }
    }else if('multi'===$data['type']){
      foreach($data['request'] as $key=>$item){
        $param['param']=$item['param'];
        $param['pdo']=$this->pdo;
        $param['redis']=$this->redis;
        $send_data[$item['method']]=call_user_func_array(array('\Server\Module\\'.$item['model'],$item['method']),array($param));
      }  
      $send_data=json_encode($send_data).'\r\n';
      $send_result=$server->send($data['fd'],$send_data);
    }else{
      
    }
    return 'ok';
  }
  /*
   * @description:task process end 
   * @access:public
   */
  public function finish(\swoole_server $server,$task_id,$data){

  }
  /*
   * @description:定时器
   */
  public function timer($timer_id,$param){
    switch($timer_id){
      case 1:
        echo "Do something every 1000ms".PHP_EOL;
        break; 
      case 2:
        echo "Do something every 2000ms".PHP_EOL;
        break; 
    }
  }
  public function swoole_start(\swoole_server $server){
    file_put_contents($server->setting['pid_file'],$server->master_pid);
    file_put_contents($server->setting['pid_file'],','.$server->manager_pid,FILE_APPEND);
  }
  /*
   * @description:start
   * @access:public
   */
  public function start(){
    echo PHP_EOL;
    //打印服务器字幕
    swoole_set_process_name("JIMU Master Thread");
    echo "\033[1A\n\033[K-----------------------\033[47;30m JIMU Server \033[0m-----------------------------\n\033[0m";
    echo "Server Version:0.1 Alpha , PHP Version:".PHP_VERSION.PHP_EOL;
    echo "         The Server is running on TCP Socket".PHP_EOL;
    echo "------------------------\033[47;30m WORKERS \033[0m---------------------------\n";
    echo "MasterPid---ManagerPid---WorkerId---WorkerPid".PHP_EOL;
    //再监听一个tcp端口，为了管理后台数据
    //$this->server->addListener($this->host,9502,SWOOLE_SOCK_TCP);
    //开启服务
    $this->server->start();
  }
}
