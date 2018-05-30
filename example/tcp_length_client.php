<?php
/*
利用swoole client封装的简单的tcp客户端演示案例
*/

// SW
$sw = array(
  'type' => 'SW',
  'requestId' => time(),
  'param' => array(
    'model' => 'Account',
    'method' => 'login',
    'param' => array(
      'username' => 'wahah',
      'password' => 'wahah',
    ),
  ),
);
// SN
$sn = array(
  'type' => 'SN',
  'requestId' => time(),
  'param' => array(
    'model' => 'Account',
    'method' => 'login',
    'param' => array(
      'username' => 'wahah',
      'password' => 'wahah',
    ),
  ),
);
// MW
$mw = array(
  'type' => 'MW',
  'requestId' => time(),
  'param' => array(
    'xas' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
    'xxx' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
  ),
);
// MN
$mn = array(
  'type' => 'MN',
  'requestId' => time(),
  'param' => array(
    'xas' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
    'xxx' => array(
      'model' => 'Account',
      'method' => 'login',
      'param' => array(
        'username' => 'wahah',
        'password' => 'wahah',
      ),
    ),
  ),
);



$host = '0.0.0.0';
$port = 9801;
$client = new swoole_client( SWOOLE_SOCK_TCP );
if ( !$client->connect( $host, $port, -1 ) ){
  exit( "connect failed. Error: {$client->errCode}\n" );
}
$client->set( array(
  'open_length_check' => 1,
  'package_length_type' => 'N',
  'package_length_offset' => 0,
  'package_body_offset' => 4,
) );

$data = json_encode( $sw );
$data = pack( 'N', strlen( $data ) ).$data;
$client->send( $data );
$jsonString = $client->recv();
$header = substr( $jsonString, 0, 4 );
$len = unpack( 'Nlen', $header );
$len = $len['len'];
$data = substr( $jsonString, 4, $len );
print_r( json_decode( $data, true ) );

$client->close();



