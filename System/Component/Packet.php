<?php
namespace System\Component;
class Packet{
  public static function encode( array $data ){
    return json_encode( $data ).'\r\n'; 
  }
  public static function decode( $jsonString ){
    $jsonString = str_replace( '\r\n', '', $jsonString ); 
    return json_decode( $jsonString, true );
  }
}

