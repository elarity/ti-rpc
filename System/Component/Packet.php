<?php
namespace System\Component;

class Packet{

	private static $tcpPack = 'length';  // length eof两种方式

	/*
	 * @desc : 配置packet的拆包方式
	 */
  public static function encode( array $data, $type = "tcp" ){
		if( 'tcp' == $type ){
			if( 'eof' == self::$tcpPack ){
        $data = json_encode( $data ).'\r\n';
			}else if( 'length' == self::$tcpPack ){
				$data = json_encode( $data );
				$data = pack( 'N', strlen( $data ) ).$data;
			}
      return $data;
		} else {
      return json_encode( $data );
		}
  }

	/*
	 * @desc : 配置packet的拆包方式
	 */
  public static function decode( $jsonString, $type = "tcp" ){
		if( 'tcp' == $type ){
			if( 'eof' == self::$tcpPack ){
        $jsonString = str_replace( '\r\n', '', $jsonString ); 
			}else if( 'length' == self::$tcpPack ){
		    $header = substr( $jsonString, 0, 4 );
	      $len = unpack( 'Nlen', $header );
		    $len = $len['len'];
			  $jsonString = substr( $jsonString, 4, $len );
			}
      return json_decode( $jsonString, true );
		} else {
      return json_decode( $jsonString, true );
		}
  }

	/*
	 * @desc : 配置packet的拆包方式
	 */
	public static function setting( array $setting ){
	  self::$tcpPack = isset( $setting['tcpPack'] ) ? $setting['tcpPack'] : 'length' ; 
	} 

}

