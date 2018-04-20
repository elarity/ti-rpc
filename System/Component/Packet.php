<?php
namespace System\Component;
class Packet{
  public static function encode( array $data, $type = "tcp" ){
		if( 'tcp' == $type ){
      return json_encode( $data ).'\r\n'; 
		} else {
      return json_encode( $data );
		}
  }
  public static function decode( $jsonString, $type = "tcp" ){
		if( 'tcp' == $type ){
      $jsonString = str_replace( '\r\n', '', $jsonString ); 
      return json_decode( $jsonString, true );
		} else {
      return json_decode( $jsonString, true );
		}
  }
}

