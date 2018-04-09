<?php
namespace System\Library;
/*
 weight 权重数值最大为10 
*/
class Thrift{
	private $serviceConfig = array(
		'account' => array(
			[ 'host' => '127.0.0.1', 'port' => 9901, 'weight' => 3, ],
			[ 'host' => '127.0.0.1', 'port' => 9901, 'weight' => 6, ],
			[ 'host' => '127.0.0.1', 'port' => 9901, 'weight' => 9, ],
		),	
		'feed' => array(
			[ 'host' => '127.0.0.1', 'port' => 9901, 'weight' => 4, ],
			[ 'host' => '127.0.0.1', 'port' => 9901, 'weight' => 8, ],
		),	
	);
	private $clientPool = [];
	public function __construct(){
		// 根据配置创建每个服务的客户端
		foreach( $this->serviceConfig as $serviceName => $serviceItem ){
			$this->clientPool[ $serviceName ] = [];
			foreach( $serviceItem as $item ){
        $client = new \swoole_client( SWOOLE_TCP, SWOOLE_SYNC ); 
		    $client->set(array(
          'open_eof_check' => true,
          'package_eof' => "\r\n",
          'package_max_length' => 1024 * 1024 * 2,
		    ));
				$client->connect( $item['host'], $item['port'] );
				$client->weight = $item['weight'];
			  $this->clientPool[ $serviceName ][] = $client;
			}
		}
	} 
	/*
	 $method : serviceName .
	 $arg : model method param
	*/
	public function __call( $serviceName, $arg ){
    // 首先挑选一个客户端	
		$serviceNumber = count( $this->clientPool[ $serviceName] );
		$serviceIndex = mt_rand( 0, ( $serviceNumber-1 ) );
		$tempClient = $this->clientPool[ $serviceName ][ $serviceIndex ];
    
	}
	private function _encode( array $config ){
		return json_encode( $config ).'\r\n';
	}
	private function _decode( $jsonString ){
    $jsonString = str_replace( '\r\n', '', $jsonString );
    return json_decode( $jsonString, true );
	}
}
