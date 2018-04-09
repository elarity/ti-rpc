<?php
namespace System\Library;
class SlideRedis{
	private $redis = null;
	public function __construct(){
		if( null === $this->redis ){
	    $redis = new \Redis();	
		  $redis->connect( '172.16.0.51', 6379 );
		  $redis->select(0);
			$this->redis = $redis;
		}
	}
	public function __call( $method, $args ){
		$result = call_user_func_array( array( $this->redis, $method ), $args );
		return $result;
	}
}
